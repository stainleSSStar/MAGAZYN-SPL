# Panel Magazynu

Panel administracyjny do bazy danych magazynu (drukarki, modele, materiały, pracownicy, wymiany).
Napisany w **PHP + MariaDB/MySQL**, bez frameworka i bez zależności zewnętrznych — działa na
zwykłym hostingu.

Panel **nie zmienia struktury bazy**. Odczytuje ją automatycznie z `INFORMATION_SCHEMA`,
więc ta sama wersja panelu obsłuży Twoją obecną bazę, jak i jej kopię na produkcji.

**Wdrożenie w skrócie:** rozpakuj paczkę w dowolnym podkatalogu serwera (np. `twojapaczka/`),
otwórz `https://serwerip/twojapaczka/` i w kreatorze wpisz dane bazy oraz hasło administratora.
Ścieżka wykrywa się sama — nie trzeba nic ustawiać w konfiguracji serwera.

**Gotowe komendy dla Debiana/Ubuntu** (Apache i Nginx, import danych, kopia zapasowa,
aktualizacja) są w pliku **`INSTALACJA-DEBIAN.txt`** w tej paczce.

**Kompletny przewodnik** — instrukcja, komendy, checklista, rozwiązywanie problemów
i notatki techniczne (jak rozpoznawać uszkodzone kodowanie, dlaczego konta są
w plikach, jak bezpiecznie weryfikować bazę) — jest w pliku **`PRZEWODNIK.md`**.

---

## Podgląd

Repozytorium zawiera **kod panelu i pustą bazę** — bez danych i bez zrzutów ekranu
z realnymi danymi. Poniżej lista ekranów; po wypełnieniu bazy wyglądają jak opisano.

- **Pulpit** — podsumowanie stanu magazynu i urządzeń, wykres wymian z ostatnich
  12 miesięcy (liczba nad słupkiem, oś z siatką, maksimum wyróżnione osobnym kolorem,
  podsumowanie: łącznie / średnia / najwięcej / zmiana, klikalne słupki otwierające
  wymiany z danego miesiąca), materiały do zamówienia z przyciskiem „⛔ Pomiń"
- **Moduł Wymiany** — filtry: urządzenie, model, pracownik, materiał, zakres dat;
  presety okresu; zaznaczanie wielu wierszy i usuwanie masowe; eksport CSV
- **Formularz dodawania** — opisy pól (ikona ⓘ), kalendarz daty, walidacja
- **Wybór materiału** — po wybraniu urządzenia model ustawia się sam, a lista materiałów
  zawęża się do pozycji pasujących do tego modelu
- **Tabela każdej bazy** — paginacja, sortowanie, filtry z zakresami dat, wyszukiwarka,
  podsumowania, eksport CSV
- **Struktura bazy** — wszystkie wykryte tabele, kolumny, typy i klucze obce

---

## 1. Gdzie umieścić pliki

Paczka ZIP ma **pliki w swoim katalogu głównym** (bez dodatkowego folderu w środku), więc
wystarczy utworzyć na serwerze katalog i rozpakować do niego:

| Serwer / hosting | Ścieżka docelowa | Adres po wgraniu |
|---|---|---|
| Debian/Ubuntu + Apache | `/var/www/html/twojapaczka/` | `http://twoj-serwer.pl/twojapaczka/` |
| Debian/Ubuntu + Apache (katalog domeny) | `/var/www/domena.pl/twojapaczka/` | `https://domena.pl/twojapaczka/` |
| home.pl / Cyberduck / FTP (hosting www) | `public_html/twojapaczka/` | `https://twojadomena.pl/twojapaczka/` |
| OVH (mutualisé) | `www/twojapaczka/` (w katalogu domeny) | `https://twojadomena.pl/twojapaczka/` |
| VPS / własny serwer + Nginx | `/var/www/twojapaczka/` | `https://twojadomena.pl/twojapaczka/` |

**Jak sprawdzić, gdzie to wgrywać:** zaloguj się na serwer przez FTP/Panel i wejdź do katalogu,
w którym mas już plik `index.html` albo `index.php` Twojej strony — to jest katalog główny witryny.
Utwórz w nim podkatalog (np. `twojapaczka`) i wgraj do niego zawartość paczki.

### Ścieżka — wykrywa się sama

Panel sam odczytuje swoją ścieżkę z adresu skryptu, więc **nie musisz ustawiać `base_path`** —
działa i w katalogu głównym (`https://domena.pl/`), i w podkatalogu (`https://domena.pl/twojapaczka/`).
Jeśli chcesz wymusić ścieżkę ręcznie, ustaw w `config/config.php`:

```php
'app' => ['base_path' => '/twojapaczka'],
```

Nie musisz niczego zmieniać w Apache ani w `.htaccess` — panel nie korzysta z `mod_rewrite`,
wszystkie odnośniki mają postać `index.php?r=...`.

---

## 2. Wymagania

- PHP **8.0** lub nowszy z rozszerzeniami `pdo_mysql`, `mbstring` i `iconv`
- MariaDB albo MySQL (baza w kodowaniu `utf8mb4`)
- Apache z `mod_php` / PHP-FPM albo Nginx + PHP-FPM
- **Apache:** włączone `AllowOverride All` — inaczej pliki `.htaccess` są
  ignorowane i katalogi z kodem oraz konfiguracją staną się dostępne publicznie
  (patrz krok 1a niżej)
- katalog `config/` **zapisywalny** dla PHP — tam panel zapisuje dane bazy i konta
- zapisywalny katalog sesji PHP (domyślny na serwerze zwykle jest)

**Nie jest potrzebne:** Composer, npm, internet, CDN, `mod_rewrite`, dostęp do
panelu hostingu. Panel nie ma żadnych zależności zewnętrznych.

**Jak sprawdzić, czy serwer jest gotowy:**

```bash
php -v                                # musi być 8.0 lub nowszy
php -m | grep -E 'pdo_mysql|mbstring|iconv'   # trzy linijki
php -r 'echo ini_get("session.save_path"), "\n";'
```

Panel nie pokazuje w interfejsie wersji PHP ani bazy — sprawdza je sam w razie
potrzeby i komunikuje jedynie, czy połączenie działa.

---

## 2A. Debian / Ubuntu — komendy gotowe do skopiowania

Pełny, ponumerowany przewodnik (16 kroków, Apache i Nginx, import danych,
kopia zapasowa, aktualizacja) jest w pliku **`INSTALACJA-DEBIAN.txt`** w tej samej
paczce. Skrót najważniejszych kroków:

```bash
# 1. pakiety
sudo apt update
sudo apt install -y apache2 mariadb-server libapache2-mod-php \
    php-mysql php-mbstring php-curl php-xml unzip

# 2. baza i użytkownik (w konsoli mysql: ...)
sudo mysql -e "CREATE DATABASE magazyn CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
sudo mysql -e "CREATE USER 'magazyn_app'@'localhost' IDENTIFIED BY '<SILNE_HASLO>';"
sudo mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE ON magazyn.* TO 'magazyn_app'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 3. pliki panelu
sudo mkdir -p /var/www/html/twojapaczka
cd /var/www/html/twojapaczka && sudo unzip -o /sciezka/do/magazyn-panel.zip

# 4. uprawnienia (config/ musi być zapisywalny dla PHP)
sudo chown -R www-data:www-data /var/www/html/twojapaczka
sudo chmod -R 750 /var/www/html/twojapaczka
sudo chmod 755 /var/www/html/twojapaczka/config

# 5. Apache: BEZ tego pliki .htaccess są ignorowane
sudo a2enmod headers rewrite
sudo sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
sudo systemctl restart apache2

# 6. dane (tylko jeśli baza jest pusta) + naprawa kodowania
sudo mysql --default-character-set=utf8mb4 magazyn < sql/schema.sql
sudo -u www-data php bin/repair-encoding.php
sudo -u www-data php bin/repair-encoding.php --write
```

Potem otwórz `http://twojadomena.pl/twojapaczka/` i przejdź kreatorem.

---

## 3. Wdrożenie krok po kroku

### Krok 1 — wgraj pliki

Rozpakuj `magazyn-panel.zip` i wgraj całą zawartość do katalogu `magazyn/` (patrz sekcja 1).
Pliki leżą **w katalogu głównym archiwum** — nie trzeba tworzyć dodatkowego folderu.

```bash
sudo mkdir -p /var/www/html/twojapaczka
cd /var/www/html/twojapaczka
sudo unzip -o /ścieżka/do/magazyn-panel.zip
```

### Krok 1a — zabezpiecz katalogi (Apache)

Katalogi `config/`, `app/`, `sql/`, `bin/` i `docs/` są zamknięte plikami `.htaccess`.
**Działają tylko wtedy, gdy Apache ma włączone `AllowOverride`** — a domyślnie na
Debianie jest wyłączone. Bez tego katalog z hasłem do bazy i hashami kont
jest dostępny publicznie pod adresem przeglądarki.

```bash
sudo a2enmod headers rewrite
sudo sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
sudo systemctl restart apache2
```

W **Nginx** pliki `.htaccess` są ignorowane — blokady musisz zrobić w konfiguracji
serwera (pełny blok `server` znajdziesz w `INSTALACJA-DEBIAN.txt`, punkt 7):

```nginx
location ~* ^/(?:[^/]+/)?(?:config|app|sql|bin|docs)/ { deny all; }
```

### Krok 2 — utwórz bazę i użytkownika

Zaloguj się do MySQL/MariaDB (SSH: `mysql -u root -p`) i wykonaj:

```sql
CREATE DATABASE magazyn CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- użytkownik panelu - tylko do odczytu i zapisu w bazie magazyn
CREATE USER 'magazyn_app'@'localhost' IDENTIFIED BY 'TUTAJ_SILNE_HASLO';
GRANT SELECT, INSERT, UPDATE, DELETE ON magazyn.* TO 'magazyn_app'@'localhost';
FLUSH PRIVILEGES;
```

> Panel **nie potrzebuje** praw `CREATE`/`DROP`/`ALTER` — nie zmienia struktury bazy.
> Użytkownik z samymi `SELECT, INSERT, UPDATE, DELETE` wystarczy.

### Krok 3 — utwórz bazę

Jeżeli na serwerze mas już bazę `magazyn` z danymi — **pomiń ten krok**.

Jeżeli bazę tworzysz od zera, wgraj **pustą strukturę**:

```bash
mysql -u root -p magazyn < sql/schema.sql
```

`sql/schema.sql` zawiera wyłącznie strukturę (CREATE TABLE) — **bez żadnych danych**.
Tabele startują od ID = 1, więc możesz zacząć wypełniać je od zera: przez panel
(menu *Tabele* → dodawanie rekordów) albo importem własnego dumpa:

```bash
mysql -u root -p --default-character-set=utf8mb4 magazyn < twoj-dump.sql
```

### Krok 4 — napraw kodowanie znaków (opcjonalnie, ale zalecane)

Jeśli wgrywasz **własny dump** wykonany z połączenia w kodowaniu `latin1`, część polskich
znaków może być zniekształcona (np. `trzeba odebraÄ‡` zamiast `trzeba odebrać`).
Napraw to jednym poleceniem:

```bash
php bin/repair-encoding.php --write
```

Narzędzie najpierw pokaże raport — sprawdź listę, a dopiero potem uruchom z `--write`.
Naprawia wyłącznie wartości, które jednoznacznie wyglądają na podwójnie zakodowane.

### Krok 5 — otwórz panel i przejdź kreatorem

Po wgraniu plików wejdź na adres panelu (np. `https://twojadomena.pl/twojapaczka/`).
Panel **sam wykryje, czy baza działa** i otworzy kreator z dwoma krokami:

**Krok 1 — połączenie z bazą.** Wpisz dane z kroku 2 (host, port, nazwa bazy, użytkownik,
hasło). Panel **sprawdza połączenie** i dopiero po udanym teście zapisuje je
w pliku `config/db.local.php` — nie zapisuje ślepo.

**Krok 2 — konto administratora.** Wpisz login (domyślnie `ADMIN`), nazwę wyświetlaną
i hasło (min. 8 znaków). Konta zapisują się w pliku `config/users.php`.

Po tym możesz się zalogować. Kreator działa też później — jeśli baza przestanie odpowiadać,
panel sam skieruje Cię do niego zamiast pokazywać pustą stronę.

Żeby panel mógł zapisywać pliki, katalog `config/` musi być zapisywalny dla PHP:

```bash
chmod 755 /var/www/html/twojapaczka/config
```

> **Ścieżka jest wykrywana automatycznie** — w podkatalogu `twojapaczka/` nie musisz
> ustawiać `base_path`. Jeśli jednak chcesz wpisać dane bazy ręcznie, sekcja `db`
> w `config/config.php` też działa — kreator zapisuje do `config/db.local.php`,
> który ma wyższy priorytet.

### Krok 6 — hasła i konta

**Panel nie ma żadnego domyślnego hasła** — hasło ustawiasz w kreatorze.

- **Ty i każdy inny użytkownik** może zmienić **własne** hasło: kliknij w panelu na blok
  z nazwą zalogowanego użytkownika na dole menu bocznego.
- **Tylko administrator** może zarządzać kontami (menu **Konta użytkowników**):
  dodać konto, zmienić login, nazwę, **rolę** (*Administrator* / *Tylko odczyt*),
  wyłączyć konto, zresetować hasło albo je usunąć.
- Konta z rolą *Tylko odczyt* przeglądają dane i eksportują do CSV, ale **nie mogą
  zapisywać ani usuwać** — przyciski zapisu są dla nich ukryte, a próba zapisu zostaje
  odrzucona przez serwer, nie tylko przez interfejs.

Jeśli wolisz ustawić hasło z terminala (albo nie masz dostępu do panelu), użyj komendy:

```bash
php bin/set-password.php "TwojeNoweSilneHaslo" --write
```

Ta komenda zapisuje konto administratora w tym samym pliku `config/users.php`.

**Zmiana hasła w późniejszym czasie:** kliknij w panelu na blok z nazwą zalogowanego
użytkownika na dole menu bocznego (`ADMIN · zalogowany · zmień hasło`) — otworzy się
formularz zmiany hasła.

### Krok 7 — sprawdź uprawnienia

```bash
chown -R www-data:www-data /var/www/html/twojapaczka      # Debian/Ubuntu
chmod -R 750 /var/www/html/twojapaczka
chmod 755 /var/www/html/twojapaczka/config                # zapisywalny dla panelu
```

Jeśli serwer nie może odczytać `config/config.php`, panel pokaże czytelny komunikat
i skieruje do kreatora zamiast białego ekranu.

### Krok 8 — otwórz panel

Wejdź na `https://twojadomena.pl/twojapaczka/` i zaloguj się.

### Krok 9 — sprawdź, czy wszystko działa

- [ ] panel otwiera się i **nie** prosi ponownie o kreator
- [ ] logowanie działa, pod loginem widać rolę
- [ ] pulpit pokazuje 25 modeli / 67 drukarek / 114 materiałów / 5 pracowników / 644 wymiany
- [ ] menu **Struktura bazy** pokazuje 5 tabel z kluczami obcymi
- [ ] katalogi `config/`, `app/`, `sql/` **nie otwierają się** w przeglądarce
      (`http://twojadomena.pl/twojapaczka/config/db.local.php` musi dać 403)
- [ ] konto z rolą *Tylko odczyt* nie ma przycisków zapisu
- [ ] eksport CSV działa
- [ ] `show_errors` jest `false` — kreator to ustawia; jeśli wgrywałeś ręcznie,
      popraw `config/config.php`

### Krok 10 — HTTPS

Panel działa bez HTTPS, ale logowanie przesyła hasło — na produkcji włącz certyfikat:

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d twojadomena.pl
```

---

## 3A. Aktualizacja panelu

Paczka **nie zawiera** `config/db.local.php`, `config/users.php` ani
`config/panel-data.json` — dlatego nową wersję można bezpiecznie rozpakować
**na wierzch** istniejącej. Konta, dane bazy i lista pomijanych materiałów
zostaną nietknięte.

```bash
cd /var/www/html/twojapaczka
sudo unzip -o /ścieżka/do/magazyn-panel.zip
sudo chown -R www-data:www-data /var/www/html/twojapaczka
sudo chmod 755 /var/www/html/twojapaczka/config
```

Przed aktualizacją zrób kopię (poniżej).

## 3B. Kopia zapasowa

Panel trzyma wrażliwe rzeczy **poza bazą** — hasła kont i dane do połączenia
z bazą leżą w katalogu `config/`. Backup to dwie rzeczy:

```bash
mysqldump -u root -p magazyn > backup-magazyn-$(date +%F).sql
sudo tar czf backup-panel-config-$(date +%F).tar.gz /var/www/html/twojapaczka/config
```

## 3C. Szybki podgląd na własnym komputerze (bez serwera)

Żeby obejrzeć panel bez konfigurowania Apache i bazy:

```bash
mkdir -p podglad/twojapaczka
unzip magazyn-panel.zip -d podglad/twojapaczka/
mysql -e "CREATE DATABASE magazyn_podglad CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql --default-character-set=utf8mb4 magazyn_podglad < podglad/twojapaczka/sql/schema.sql
cd podglad && php -S 127.0.0.1:8950
```

Otwórz `http://127.0.0.1:8950/twojapaczka/` i przejdź kreatorem.

> **Uwaga:** `php -S` to serwer deweloperski — **nie respektuje plików `.htaccess`**,
> więc katalogi `sql/` i `config/` są w nim dostępne jako pliki. Nigdy nie wystawiaj
> tak uruchomionego panelu publicznie. Na produkcji obowiązuje Apache/Nginx
> z regułami z kroku 1a.

---

## 4. Co potrafi panel

### Pulpit
Karty podsumowujące (liczba urządzeń, wymian, materiałów, modeli), ostatnie wymiany,
najczęściej serwisowane modele, materiały do zamówienia (stan ≤ 0), obciążenie pracowników,
skróty do szybkiego dodawania rekordów oraz **wykres wymian w ostatnich 12 miesiącach**
z wartościami na osi, skalą, podsumowaniem (łącznie / średnio / najwięcej / zmiana
miesiąc do miesiąca) i rozwijanym podglądem w liczbach. **Każdy słupek jest klikalny** —
otwiera listę wymian z danego miesiąca.


### Moduł Wymiany (`Wymiany` w menu)
- **Wszystkie wymiany** albo **tylko dla jednego urządzenia** wybranego z listy rozwijanej
- filtry: urządzenie, model, pracownik, materiał, zakres dat (od/do) oraz dowolny tekst w uwagach
- szybkie presety okresu: *Wszystkie · 7 dni · 30 dni · 90 dni · Ten miesiąc · Ten rok · Ostatnie 10*
- podsumowanie zbioru: liczba wymian, najstarsza i najnowsza data, suma i średnia liczników
- zaznaczanie wielu wierszy i **usuwanie masowe** jednym kliknięciem
- eksport do CSV (z filtrem, który jest aktualnie ustawiony)

### Urządzenia
Rejestr drukarek z wyszukiwaniem po lokalizacji, adresie IP, modelu i uwagach.
Po kliknięciu w lokalizację otwiera się **karta urządzenia**: dane, statystyki, lista materiałów
eksploatacyjnych pasujących do modelu i pełna historia wymian z filtrowaniem po pracowniku.

**Zmiana modelu urządzenia.** Historia wymian jest powiązana z urządzeniem, nie z modelem —
sama zmiana modelu nie kasuje ani jednego wpisu. Przy edycji urządzenia pojawia się checkbox
*„Przepisz Model także w N wymianach tego urządzenia”*:

- **bez checkboxa** — model urządzenia się zmienia, a stare wymiany zachowują model z chwili wymiany,
- **z checkboxem** — model zostaje przepisany także we wszystkich wymianach tego urządzenia
  (przydatne przy korekcie pomyłki we wpisie).

Panel sam pokazuje w komunikacie, ile wymian zostało zaktualizowanych.

### Powiązane rekordy i bezpieczne usuwanie

Z każdego rekordu (ikona 👁 w tabeli) znajdziesz sekcję **Powiązane rekordy** — kliknięcie
otwiera listę **od razu przefiltrowaną** do tego rekordu (np. wszystkie wymiany danej drukarki),
więc nie musisz niczego wpisywać w wyszukiwarce.

**Usuwanie chroni spójność bazy.** Jeśli rekord jest używany w innych tabelach, panel
**nie usuwa go od razu** — pokazuje, gdzie jest używany, i pyta o potwierdzenie:

- powiązane rekordy wskażą na niego kluczem obcym z regułą `RESTRICT` → **baza i tak nie pozwoli**
  usunąć; panel mówi wprost, żeby najpierw usunąć lub zmienić powiązane rekordy,
- reguła `SET NULL` → powiązane rekordy zostaną bez odniesienia; panel ostrzega i dopiero
  po kliknięciu *„Usuń mimo to”* kasuje rekord,
- `CASCADE` → baza usunie razem z nim rekordy potomne; również wymaga potwierdzenia.

Tak samo działa **usuwanie masowe** — najpierw pokazuje podsumowanie skutków.

### Materiały pomijane przy zamówieniu

Materiał o wartości archiwalnej może mieć stan ujemny, a mimo to **nie powinien być zamawiany**.
Przy każdym takim materiale (i na karcie materiału) jest przycisk **„⛔ Pomiń przy zamówieniu”** —
materiał znika z listy *Materiały do zamówienia* i z licznika na pulpicie, ale zostaje w bazie
i jest widoczny na liście pominiętych. Flaga jest ustawieniem panelu (plik `config/panel-data.json`),
**nie zmienia schematu bazy**.

### Konta użytkowników
Menu **Konta użytkowników** (tylko administrator): lista kont, dodawanie, zmiana loginu
i nazwy, **przełączanie roli** (*Administrator* / *Tylko odczyt*), wyłączanie konta,
reset hasła i usuwanie. Każdy użytkownik może zmienić własne hasło w menu bocznym.

### Tabele (każda tabela w bazie)
- lista z **paginacją** (25 / 50 / 100 / 200 / maks. na stronę), sortowaniem po każdej kolumnie
- filtry na poziomie kolumn, w tym **zakresy dat** (od/do)
- wyszukiwarka pełnotekstowa w treści pól
- podsumowanie: liczba rekordów, zakres dat, suma i średnia kolumn liczbowych oraz liczba
  różnych wartości w kolumnach kluczy obcych (np. *Różne: Urządzenie = 62*)
- dodawanie, edycja i usuwanie rekordów (pojedynczo lub masowo)
- eksport do CSV

### Formularze
Każde pole ma **opis** (ikona ⓘ przy etykiecie + podpowiedź pod polem). Walidacja sprawdza
wymagane pola, typy (data, liczba, adres IP), długość tekstu oraz poprawność kluczy obcych.
Daty wybierasz z **kalendarza** (domyślnie podpowiada się dzisiejsza data).

**Wybór materiału (toneru).** W bazie nazwy materiałów się powtarzają (6× „Toner czarny”),
a każdy materiał należy do konkretnego modelu drukarki. Dlatego w formularzu wymiany:
wybór **urządzenia** automatycznie ustawia **model**, a lista **materiałów** zawęża się
do pozycji pasujących do tego modelu (`data-filter-by` / `data-set-model` w `views/form.php`,
obsługa w `assets/js/app.js` → `initSelectFilters()`). Etykieta materiału zawiera symbol
w nawiasie i nazwę modelu, np. `Toner czarny (45807111) - OKI B432`.

### Wyszukiwarka globalna
Przeszukuje wszystkie tekstowe pola wszystkich tabel jednocześnie.

### Struktura bazy
Pokazuje wszystkie wykryte tabele i kolumny wraz z typami, kluczami i opisami.

---

## 5. Dodanie nowej tabeli

Nie musisz nic zmieniać w kodzie — panel wykryje ją automatycznie i będzie można ją
przeglądać, filtrować, dodawać i edytować.

Jeśli chcesz, żeby nowa tabela miała **polskie nazwy pól i opisy**, dopisz ją w
`app/Schema.php` do tablicy `STATIC`:

```php
'moja_tabela' => [
    'label'       => 'Moja tabela',
    'label_one'   => 'Rekord',
    'icon'        => '📦',
    'description' => 'Opis tabeli widoczny w panelu.',
    'columns' => [
        'IDmoja' => ['label' => 'ID', 'desc' => 'Numer rekordu.'],
        'Nazwa' => ['label' => 'Nazwa', 'desc' => 'Nazwa rekordu.', 'required' => true],
    ],
],
```

---

## 6. Bezpieczeństwo

- wszystkie zapytania SQL idą przez **PDO z parametrami** (bez sklejania SQL)
- nazwy tabel i kolumn są weryfikowane wobec schematu — nazwa z adresu URL nie trafia do SQL
- **CSRF**: każdy formularz ma token, niezgodność blokuje zapis
- **XSS**: wszystkie dane z bazy są escapowane przy wyświetlaniu
- **hasła zapisane jako bcrypt**, bez jawnej postaci w kodzie
- **panel nie ma hasła domyślnego** — hasło ustawiasz w kreatorze albo komendą
  `php bin/set-password.php "Hasło" --write`; hashe leżą w `config/users.php`, poza bazą
- **konta i role**: administrator (pełne prawa) albo tylko odczyt; blokada zapisów
  działa po stronie serwera, nie tylko w interfejsie
- **usuwanie chroni spójność bazy** — rekord używany w innych tabelach wymaga potwierdzenia,
  a blokowany przez klucz obcy `RESTRICT` nie znika wcale
- blokada po 5 nieudanych próbach logowania (5 minut)
- katalogi `config/`, `app/`, `sql/`, `bin/`, `docs/` są zamknięte plikami `.htaccess` (Apache)
- katalog główny ma `.htaccess` wyłączający listowanie zawartości i dostęp do plików
  `.sql`, `.json`, `.log`, `.bak`
- **katalog `config/` zawiera hasło do bazy w postaci jawnej** (`config/db.local.php`)
  i hashe haseł (`config/users.php`) — dlatego musi być niedostępny z przeglądarki
  i dostępny do zapisu tylko dla użytkownika serwera WWW

Dla Nginx zablokuj dostęp do tych katalogów w konfiguracji serwera:

```nginx
location ~* ^/(?:[^/]+/)?(?:config|app|sql|bin|docs)/ { deny all; }
```

**Na produkcji ustaw w `config/config.php`:**

```php
'db' => [
    ...
    'show_errors' => false,   // nie pokazuj szczegółów błędów użytkownikowi
],
```

---

## 7. Struktura projektu

```
magazyn-panel/
├── index.php              # kontroler frontowy - wszystkie trasy
├── .htaccess              # ochrona katalogu glownego (Apache)
├── INSTALACJA-DEBIAN.txt # komendy instalacji na Debian/Ubuntu
├── PRZEWODNIK.md         # kompletny przewodnik + notatki techniczne
├── config/
│   ├── config.php         # ← dane bazy + nazwa administratora (jedyny plik do edycji)
│   ├── config.sample.php  # wzorzec do wypełnienia
│   ├── config.local.php   # opcjonalne nadpisania lokalne (testy)
│   ├── db.local.php       # dane bazy z kreatora (tworzy się samo, NIE jest w paczce)
│   ├── users.php          # konta użytkowników i hashe (tworzy się samo, NIE jest w paczce)
│   ├── panel-data.json    # ustawienia panelu (np. materiały pomijane przy zamówieniu)
│   └── .htaccess
├── app/
│   ├── bootstrap.php      # konfiguracja, sesja, autoload, obsługa błędów
│   ├── Database.php       # warstwa PDO
│   ├── Schema.php         # metadane tabel + opisy pól (po polsku)
│   ├── Repository.php     # generyczny CRUD, filtry, paginacja, agregaty
│   ├── Validator.php      # walidacja formularzy
│   ├── FkResolver.php     # klucze obce -> czytelne etykiety
│   ├── Stats.php          # statystyki pulpitu i urządzeń
│   ├── Auth.php           # logowanie, sesje, role, zmiana hasła, blokada po błędach
│   ├── Users.php          # konta użytkowników (plik config/users.php)
│   ├── PanelData.php      # ustawienia panelu niepasujące do bazy
│   ├── PasswordStore.php  # zapis hasła administratora w config/auth.local.php
│   ├── View.php           # renderowanie widoków
│   └── Helpers.php
├── views/                 # szablony stron
├── assets/
│   ├── css/app.css        # motyw granatowy / błękitny / biały
│   ├── js/app.js          # kalendarz, filtrowanie list, zaznaczanie masowe
│   └── img/logo.svg       # herb powiatu łańcuckiego
├── sql/
│   └── schema.sql         # sama struktura tabel (BEZ danych)
├── bin/
│   ├── set-password.php   # zmiana hasła administratora
│   └── repair-encoding.php# naprawa kodowania polskich znaków
└── docs/
```

---

## 8. Rozwiązywanie problemów

| Objaw | Przyczyna / rozwiązanie |
|---|---|
| „Brak połączenia z bazą danych" | Złe dane w `config/config.php` albo MariaDB nie działa. Sprawdź `mysql -u magazyn_app -p -h localhost magazyn`. |
| `config/db.local.php` otwiera się w przeglądarce | Apache ignoruje `.htaccess` — włącz `AllowOverride All` (krok 1a) albo dodaj `Require all denied` w vhoście. |
| Pliki z `sql/` są pobieralne | j.w. — Nginx nie czyta `.htaccess`, potrzebna reguła `location ~* ... deny all`. |
| Panel nie chce zapisywać kont albo ustawień | Katalog `config/` nie jest zapisywalny dla PHP — `chmod 755 config`. |
| Panel prosi o login w pętli | Usuń `config/config.local.php` (może nadpisywać ustawienia) i wyczyść cookies przeglądarki. |
| „Hasło administratora nie zostało jeszcze ustawione” | Panel czeka na ustawienie hasła — wejdź na `index.php?r=setup`. Jeśli hasło już jest, sprawdź `config/users.php`. |
| „Nie udało się zapisać pliku config/…” | Katalog `config/` nie jest zapisywalny dla PHP — `chmod 755 config`. |
| „Nie znaleziono rekordu” po kliknięciu w powiązane rekordy | Rekord został usunięty w międzyczasie. |
| „Nie udało się zapisać hasła” | Katalog `config/` nie jest zapisywalny dla PHP. Nadaj prawa: `chmod 755 config`. |
| „Nie znaleziono strony” | Zły `base_path` w `config/config.php`. Dla podkatalogu `/magazyn` wpisz `'/magazyn'`. |
| Pusta lista tabel | Brak uprawnień użytkownika do bazy — nadaj `GRANT SELECT, INSERT, UPDATE, DELETE ON magazyn.*`. |
| Krzaczki w polskich znakach | Uruchom `php bin/repair-encoding.php --write` (patrz krok 4). |
| Nie działa kalendarz | Za stary przeglądarka — wymagany ES6. Alternatywnie pole daty działa jako natywny wybór daty. |
| Biały ekran | Włącz `show_errors` w `config/config.php` i sprawdź log serwera (`/var/log/php/error.log` lub `journalctl -u php-fpm`). |

---

## 9. Dane w bazie — do czego służyą

| Tabela | Zawartość | Klucz |
|---|---|---|
| `model` | Modele urządzeń (np. „OKI B431”) | `IDmodel` |
| `drukarki` | Rejestr urządzeń: lokalizacja, adres IP, model | `IDdrukarki` |
| `asortyment` | Materiały eksploatacyjne: nazwa, symbol, stan, model | `IDasortyment` |
| `pracownik` | Lista pracowników | `IDpracownik` |
| `wymiany` | Historia wymian: data, licznik, model, urządzenie, materiał, pracownik, uwagi | `IDwymiany` |

Panel obsługuje też **każdą inną tabelę**, którą dodaszz do tej bazy.
