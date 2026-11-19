<?php
/**
 * Statystyki i agregaty dla pulpitu oraz karty urządzenia.
 */

declare(strict_types=1);

final class Stats
{
    /** @return array<string,mixed> */
    public static function overview(): array
    {
        $out = [];

        // --- Podstawowe liczniki -------------------------------------------
        $out['counts'] = [];
        foreach (Schema::tables() as $name => $info) {
            $out['counts'][$name] = $info['count'];
        }

        // --- Wymiany w czasie ------------------------------------------------
        $out['wymiany_total'] = (int) ($out['counts']['wymiany'] ?? 0);

        $out['wymiany_30d'] = (int) Database::value(
            'SELECT COUNT(*) FROM wymiany WHERE Data >= (CURDATE() - INTERVAL 30 DAY)'
        );

        $out['wymiany_rok'] = (int) Database::value(
            'SELECT COUNT(*) FROM wymiany WHERE YEAR(Data) = YEAR(CURDATE())'
        );

        $out['ostatnia_wymiana'] = Database::value(
            'SELECT MAX(Data) FROM wymiany'
        );

        // --- Ostatnie wymiany -------------------------------------------------
        $out['ostatnie'] = Database::all(
            'SELECT w.IDwymiany, w.Data, w.Licznik, w.Info,
                    w.IDdrukarki,
                    m.Nazwa AS ModelNazwa,
                    d.Lokalizacja, d.Adres_IP,
                    a.Nazwa AS MaterialNazwa,
                    p.Imie, p.Nazwisko
             FROM wymiany w
             LEFT JOIN model m     ON m.IDmodel = w.IDmodel
             LEFT JOIN drukarki d  ON d.IDdrukarki = w.IDdrukarki
             LEFT JOIN asortyment a ON a.IDasortyment = w.IDasortyment
             LEFT JOIN pracownik p ON p.IDpracownik = w.IDpracownik
             ORDER BY w.Data DESC, w.IDwymiany DESC
             LIMIT 8'
        );

        // --- Najczęściej serwisowane modele ----------------------------------
        $out['top_modele'] = Database::all(
            'SELECT m.IDmodel, m.Nazwa, COUNT(*) AS ile
             FROM wymiany w
             JOIN model m ON m.IDmodel = w.IDmodel
             GROUP BY m.IDmodel, m.Nazwa
             ORDER BY ile DESC
             LIMIT 8'
        );

        // --- Modele według liczby wymian (pasek postępu na pulpicie) ---------
        $out['modele_stats'] = Database::all(
            'SELECT m.Nazwa, COUNT(w.IDwymiany) AS ile
             FROM model m
             LEFT JOIN wymiany w ON w.IDmodel = m.IDmodel
             GROUP BY m.IDmodel, m.Nazwa
             ORDER BY ile DESC, m.Nazwa
             LIMIT 10'
        );
        $maxModele = 1;
        foreach ($out['modele_stats'] as $row) {
            $maxModele = max($maxModele, (int) $row['ile']);
        }
        $out['modele_max'] = $maxModele;

        // --- Materiały do zamówienia (stan <= 0) --------------------------------
        // Materiały oznaczone jako „pomiń przy zamówieniu” (wartość archiwalna)
        // są pomijane - flaga jest trzymana w pliku config/panel-data.json.
        $ignorowane = PanelData::ignoredPurchase();
        $ph         = $ignorowane === [] ? '' : ' AND a.IDasortyment NOT IN (' . implode(',', array_map('intval', $ignorowane)) . ')';

        $out['do_zamowienia'] = Database::all(
            'SELECT a.IDasortyment, a.Nazwa, a.Symbol, a.Ilosc, m.Nazwa AS ModelNazwa
             FROM asortyment a
             LEFT JOIN model m ON m.IDmodel = a.IDmodel
             WHERE (a.Ilosc IS NULL OR a.Ilosc <= 0)' . $ph . '
             ORDER BY a.Ilosc ASC, a.Nazwa
             LIMIT 12'
        );
        $out['do_zamowienia_ilosc'] = (int) Database::value(
            'SELECT COUNT(*) FROM asortyment a
             WHERE (a.Ilosc IS NULL OR a.Ilosc <= 0)' . $ph
        );

        // Materiały pominięte przy zamówieniu - pokazujemy je osobno.
        $out['pominiete_zakup'] = [];
        if ($ignorowane !== []) {
            $out['pominiete_zakup'] = Database::all(
                'SELECT a.IDasortyment, a.Nazwa, a.Symbol, a.Ilosc, m.Nazwa AS ModelNazwa
                 FROM asortyment a
                 LEFT JOIN model m ON m.IDmodel = a.IDmodel
                 WHERE a.IDasortyment IN (' . implode(',', array_map('intval', $ignorowane)) . ')
                 ORDER BY a.Nazwa'
            );
        }

        // --- Pracownicy -------------------------------------------------------
        $out['pracownicy'] = Database::all(
            'SELECT p.IDpracownik, p.Imie, p.Nazwisko, COUNT(w.IDwymiany) AS ile
             FROM pracownik p
             LEFT JOIN wymiany w ON w.IDpracownik = p.IDpracownik
             GROUP BY p.IDpracownik, p.Imie, p.Nazwisko
             ORDER BY ile DESC'
        );

        // --- Wymiany w ostatnich 12 miesiącach (wykres) ------------------------
        $out['miesiace'] = Database::all(
            "SELECT DATE_FORMAT(Data, '%Y-%m') AS mies, COUNT(*) AS ile
             FROM wymiany
             WHERE Data >= (CURDATE() - INTERVAL 12 MONTH)
             GROUP BY mies
             ORDER BY mies ASC"
        );

        // --- Urządzenia wymagające uwagi ---------------------------------------
        $out['bez_ip'] = (int) Database::value(
            "SELECT COUNT(*) FROM drukarki WHERE Adres_IP IS NULL OR Adres_IP = '' OR Adres_IP = '0'"
        );
        $out['bez_modelu'] = (int) Database::value(
            'SELECT COUNT(*) FROM drukarki WHERE IDmodel IS NULL'
        );
        $out['wymiany_bez_urz'] = (int) Database::value(
            'SELECT COUNT(*) FROM wymiany WHERE IDdrukarki IS NULL'
        );

        // --- Top lokalizacji --------------------------------------------------
        $out['top_lokalizacje'] = Database::all(
            'SELECT d.Lokalizacja, COUNT(w.IDwymiany) AS ile
             FROM wymiany w
             JOIN drukarki d ON d.IDdrukarki = w.IDdrukarki
             GROUP BY d.Lokalizacja
             ORDER BY ile DESC
             LIMIT 8'
        );

        return $out;
    }

    /**
     * Statystyki dla konkretnego urządzenia.
     *
     * @return array<string,mixed>
     */
    public static function forDevice(int|string $id): array
    {
        $out = [];

        $out['wymiany_count'] = (int) Database::value(
            'SELECT COUNT(*) FROM wymiany WHERE IDdrukarki = :id',
            ['id' => $id]
        );

        $out['ostatnia_data'] = Database::value(
            'SELECT MAX(Data) FROM wymiany WHERE IDdrukarki = :id',
            ['id' => $id]
        );

        $out['ostatni_licznik'] = Database::value(
            'SELECT Licznik FROM wymiany WHERE IDdrukarki = :id AND Licznik IS NOT NULL
             ORDER BY Data DESC, IDwymiany DESC LIMIT 1',
            ['id' => $id]
        );

        $out['materialy_count'] = (int) Database::value(
            'SELECT COUNT(*) FROM asortyment WHERE IDmodel = (SELECT IDmodel FROM drukarki WHERE IDdrukarki = :id)',
            ['id' => $id]
        );

        $out['pracownicy'] = Database::all(
            'SELECT p.Imie, p.Nazwisko, COUNT(*) AS ile
             FROM wymiany w
             JOIN pracownik p ON p.IDpracownik = w.IDpracownik
             WHERE w.IDdrukarki = :id
             GROUP BY p.IDpracownik, p.Imie, p.Nazwisko
             ORDER BY ile DESC',
            ['id' => $id]
        );

        return $out;
    }
}
