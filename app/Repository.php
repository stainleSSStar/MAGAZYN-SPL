<?php
/**
 * Generyczne repozytorium CRUD - działa dla KAŻDEJ tabeli w bazie.
 *
 *  Listowanie: filtry (dokładne / zawierające / zakresy dat), wyszukiwarka,
 *  sortowanie, paginacja (LIMIT/OFFSET) oraz agregaty.
 */

declare(strict_types=1);

final class Repository
{
    /**
     * Zwraca stronę rekordów wraz z metadanymi paginacji.
     *
     * @param array{
     *   q?: string,
     *   filters?: array<string,mixed>,
     *   sort?: string,
     *   dir?: string,
     *   page?: int,
     *   per_page?: int,
     *   where_extra?: array{0:string,1:array}
     * } $opts
     */
    public static function paginate(string $table, array $opts = []): array
    {
        $meta    = Schema::meta($table);
        $columns = $meta['columns'];

        $q       = trim((string) ($opts['q'] ?? ''));
        $filters = is_array($opts['filters'] ?? null) ? $opts['filters'] : [];
        $sort    = (string) ($opts['sort'] ?? '');
        $dir     = strtoupper((string) ($opts['dir'] ?? 'asc')) === 'DESC' ? 'DESC' : 'ASC';
        $page    = max(1, (int) ($opts['page'] ?? 1));
        $perPage = max(1, (int) ($opts['per_page'] ?? 50));

        if (!isset($columns[$sort])) {
            $sort = $meta['pk'] !== '' ? $meta['pk'] : (array_key_first($columns) ?? '');
        }
        $sortSql = $sort !== '' ? Schema::qi($sort) . ' ' . $dir : '';

        [$whereSql, $params] = self::buildWhere($table, $columns, $q, $filters);

        if (isset($opts['where_extra'])) {
            [$extraSql, $extraParams] = $opts['where_extra'];
            if (trim((string) $extraSql) !== '') {
                $whereSql = trim($whereSql) === '' ? $extraSql : '(' . $whereSql . ') AND (' . $extraSql . ')';
                $params   = array_merge($params, $extraParams);
            }
        }

        $whereClause = $whereSql === '' ? '' : ' WHERE ' . $whereSql;

        $total = (int) Database::value('SELECT COUNT(*) FROM ' . Schema::qi($table) . $whereClause, $params);

        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT * FROM ' . Schema::qi($table) . $whereClause
             . ($sortSql !== '' ? ' ORDER BY ' . $sortSql : '')
             . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        $rows = Database::all($sql, $params);

        return [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pages'    => $pages,
            'per_page' => $perPage,
            'offset'   => $offset,
            'from'     => $total > 0 ? $offset + 1 : 0,
            'to'       => min($total, $offset + $perPage),
            'sort'     => $sort,
            'dir'      => $dir,
        ];
    }

    /**
     * Buduje warunek WHERE na podstawie wyszukiwarki i filtrów.
     *
     * Format filtra:
     *   f[Nazwa]        = "tekst"        -> LIKE %tekst%
     *   f[IDdrukarki]   = "12"           -> dokładne dopasowanie (klucz obcy)
     *   f[Data][from]   = "2024-01-01"   -> >=
     *   f[Data][to]     = "2024-12-31"   -> <=
     *   f[Ilosc][gte]   = "0"            -> >= (kolumny liczbowe)
     *   f[Ilosc][lte]   = "10"           -> <=
     *
     * @param array<string,array<string,mixed>> $columns
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array}
     */
    private static function buildWhere(string $table, array $columns, string $q, array $filters): array
    {
        $clauses = [];
        $params  = [];
        $i       = 0;

        // --- Wyszukiwarka globalna -----------------------------------------
        if ($q !== '') {
            $searchCols = Schema::searchableColumns($table);
            if ($searchCols !== []) {
                $or = [];
                foreach ($searchCols as $col) {
                    $key            = 'q' . $i++;
                    $or[]           = 'CAST(' . Schema::qi($col) . ' AS CHAR) LIKE :' . $key;
                    $params[$key]   = '%' . $q . '%';
                }
                $clauses[] = '(' . implode(' OR ', $or) . ')';
            }
        }

        // --- Filtry ----------------------------------------------------------
        foreach ($filters as $col => $value) {
            if (!isset($columns[$col])) {
                continue;
            }
            $meta = $columns[$col];

            // Filtr z zakresem: f[Kolumna][from|to|gte|lte]
            if (is_array($value)) {
                foreach (['from', 'to', 'gte', 'lte', 'eq'] as $op) {
                    if (!isset($value[$op]) || trim((string) $value[$op]) === '') {
                        continue;
                    }
                    $val  = trim((string) $value[$op]);
                    $key  = 'f' . $i++;
                    $sqlOp = match ($op) {
                        'from', 'gte' => '>=',
                        'to', 'lte'   => '<=',
                        default       => '=',
                    };
                    $clauses[] = Schema::qi($col) . ' ' . $sqlOp . ' :' . $key;
                    $params[$key] = in_array($meta['type'], ['int', 'float'], true) ? (int) $val : $val;
                }
                continue;
            }

            $val = trim((string) $value);
            if ($val === '') {
                continue;
            }

            $key = 'f' . $i++;
            if ($meta['type'] === 'int' || $meta['type'] === 'float') {
                $clauses[]   = Schema::qi($col) . ' = :' . $key;
                $params[$key] = (int) $val;
            } elseif ($meta['type'] === 'date') {
                $clauses[]   = Schema::qi($col) . ' = :' . $key;
                $params[$key] = $val;
            } elseif (!empty($meta['ref'])) {
                // Klucz obcy - dopasowanie dokładne
                $clauses[]   = Schema::qi($col) . ' = :' . $key;
                $params[$key] = (int) $val;
            } else {
                $clauses[]   = Schema::qi($col) . ' LIKE :' . $key;
                $params[$key] = '%' . $val . '%';
            }
        }

        return [implode(' AND ', $clauses), $params];
    }

    /* ---------------------------------------------------------------------
     |  Operacje na pojedynczych rekordach
     | -------------------------------------------------------------------- */

    /** @return array<string,mixed>|null */
    public static function find(string $table, int|string $id): ?array
    {
        $meta = Schema::meta($table);
        $pk   = $meta['pk'];
        if ($pk === '') {
            return null;
        }

        return Database::one(
            'SELECT * FROM ' . Schema::qi($table) . ' WHERE ' . Schema::qi($pk) . ' = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** @param array<string,mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        if ($cols === []) {
            throw new InvalidArgumentException('Brak danych do zapisu.');
        }

        $placeholders = [];
        $params       = [];
        foreach ($cols as $col) {
            $placeholders[] = ':' . $col;
            $params[$col]   = $data[$col];
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            Schema::qi($table),
            implode(', ', array_map(static fn (string $c): string => Schema::qi($c), $cols)),
            implode(', ', $placeholders)
        );

        Database::execute($sql, $params);
        return Database::lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(string $table, int|string $id, array $data): int
    {
        $meta = Schema::meta($table);
        $pk   = $meta['pk'];
        $cols = array_keys($data);

        if ($cols === []) {
            return 0;
        }

        $sets   = [];
        $params = [];
        foreach ($cols as $col) {
            $sets[]       = Schema::qi($col) . ' = :' . $col;
            $params[$col] = $data[$col];
        }
        $params['__id'] = $id;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :__id',
            Schema::qi($table),
            implode(', ', $sets),
            Schema::qi($pk)
        );

        return Database::execute($sql, $params);
    }

    public static function delete(string $table, int|string $id): int
    {
        $meta = Schema::meta($table);
        $pk   = $meta['pk'];

        return Database::execute(
            'DELETE FROM ' . Schema::qi($table) . ' WHERE ' . Schema::qi($pk) . ' = :id',
            ['id' => $id]
        );
    }

    /**
     * Usunięcie wielu rekordów po ID. Zwraca liczbę usuniętych.
     *
     * @param array<int,string> $ids
     */
    public static function deleteMany(string $table, array $ids): int
    {
        $meta = Schema::meta($table);
        $pk   = $meta['pk'];
        if ($pk === '' || $ids === []) {
            return 0;
        }

        $ids = array_values(array_unique(array_map('strval', $ids)));
        if ($ids === []) {
            return 0;
        }

        $placeholders = [];
        $params       = [];
        foreach ($ids as $n => $id) {
            $placeholders[] = ':id' . $n;
            $params['id' . $n] = $id;
        }

        return Database::execute(
            'DELETE FROM ' . Schema::qi($table) . ' WHERE ' . Schema::qi($pk) . ' IN (' . implode(', ', $placeholders) . ')',
            $params
        );
    }

    /** Następny wolny numer dla tabel bez AUTO_INCREMENT. */
    public static function nextId(string $table): int
    {
        $meta = Schema::meta($table);
        $pk   = $meta['pk'];

        if ($pk === '') {
            return 1;
        }

        $max = Database::value('SELECT MAX(' . Schema::qi($pk) . ') FROM ' . Schema::qi($table));
        return (int) $max + 1;
    }

    /**
     * Powiązane rekordy: [tabela => ['column' => 'IDdrukarki', 'count' => 12]].
     *
     * @return array<string,array{column:string,count:int}>
     */
    public static function relatedCounts(string $table, int|string $id): array
    {
        $out = [];
        foreach (self::references($table, [$id]) as $childTable => $info) {
            $out[$childTable] = ['column' => $info['column'], 'count' => $info['count']];
        }
        return $out;
    }

    /**
     * Rekordy innych tabel wskazujące na usuwane rekordy.
     *
     * Zwraca dla każdej tabeli: kolumnę klucza obcego, liczbę powiązanych
     * rekordów oraz regułę usuwania z bazy (SET NULL / RESTRICT / CASCADE).
     *
     * @param array<int,string|int> $ids
     * @return array<string,array{column:string,count:int,on_delete:string,blocked:bool,label:string}>
     */
    public static function references(string $table, array $ids): array
    {
        $out = [];
        $ids = array_values(array_filter(array_map('strval', $ids), static fn($v) => $v !== ''));
        if ($ids === [] || !Schema::exists($table)) {
            return $out;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        foreach (array_keys(Schema::tables()) as $childTable) {
            if ($childTable === $table) {
                continue;
            }
            foreach (Schema::columns($childTable) as $colName => $col) {
                if (($col['ref'] ?? null) !== $table) {
                    continue;
                }

                $onDelete = strtoupper((string) ($col['on_delete'] ?? 'RESTRICT'));

                try {
                    $count = (int) Database::value(
                        'SELECT COUNT(*) FROM ' . Schema::qi($childTable)
                        . ' WHERE ' . Schema::qi($colName) . ' IN (' . $placeholders . ')',
                        $ids
                    );
                } catch (Throwable $e) {
                    continue;
                }

                if ($count > 0) {
                    $out[$childTable] = [
                        'column'    => $colName,
                        'count'     => $count,
                        'on_delete' => $onDelete,
                        // RESTRICT / NO ACTION - baza nie pozwoli usunąć rekordu
                        'blocked'   => in_array($onDelete, ['RESTRICT', 'NO ACTION'], true),
                        'label'     => Schema::label($childTable),
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * Agregaty dla filtrowanego zbioru (podsumowanie widoku listy).
     *
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public static function summary(string $table, array $filters = [], string $q = ''): array
    {
        $meta    = Schema::meta($table);
        $columns = $meta['columns'];

        [$whereSql, $params] = self::buildWhere($table, $columns, trim((string) $q), $filters);
        $where = $whereSql === '' ? '' : ' WHERE ' . $whereSql;

        $out = [
            'count' => (int) Database::value('SELECT COUNT(*) FROM ' . Schema::qi($table) . $where, $params),
        ];

        // Zakres dat (kolumna typu date)
        foreach ($columns as $colName => $col) {
            if ($col['type'] === 'date') {
                $out['date_from'] = Database::value(
                    'SELECT MIN(' . Schema::qi($colName) . ') FROM ' . Schema::qi($table) . $where,
                    $params
                );
                $out['date_to'] = Database::value(
                    'SELECT MAX(' . Schema::qi($colName) . ') FROM ' . Schema::qi($table) . $where,
                    $params
                );
            }
        }

        // Suma i średnia dla kolumn liczbowych (Licznik, Ilosc).
        // UWAGA: kolumny kluczy obcych (IDmodel, IDdrukarki, ...) NIE są sumowane -
        // suma identyfikatorów nie ma sensu; dla nich liczymy liczbę różnych wartości.
        foreach ($columns as $colName => $col) {
            if (!in_array($col['type'], ['int', 'float'], true) || $col['pk'] || $col['auto_increment']) {
                continue;
            }
            if (!empty($col['ref'])) {
                $out['distinct_' . $colName] = (int) Database::value(
                    'SELECT COUNT(DISTINCT ' . Schema::qi($colName) . ') FROM ' . Schema::qi($table) . $where,
                    $params
                );
                continue;
            }
            $out['sum_' . $colName] = (float) Database::value(
                'SELECT COALESCE(SUM(' . Schema::qi($colName) . '),0) FROM ' . Schema::qi($table) . $where,
                $params
            );
            $out['avg_' . $colName] = (float) Database::value(
                'SELECT COALESCE(AVG(' . Schema::qi($colName) . '),0) FROM ' . Schema::qi($table) . $where,
                $params
            );
        }

        return $out;
    }
}
