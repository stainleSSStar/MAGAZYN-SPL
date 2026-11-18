# Przewodnik Panelu Magazynu — kompletny

> **Wersja panelu:** 1.3.1
> **Dokument:** ten plik (zawiera wszystko — instrukcję, komendy i notatki techniczne)
> **Dodatkowe dokumenty w paczce:** `README.md` (skrót), `INSTALACJA-DEBIAN.txt` (same komendy w wersji terminalowej), `docs/instrukcja-uzytkownika.md` (obsługa panelu przez użytkownika końcowego)

---

## Spis treści

| # | Rozdział |
|---|---|
| 0 | [TL;DR — co zrobić w 10 minut](#0-tldr--co-zrobić-w-10-minut) |
| 1 | [Co to jest i co dostajesz](#1-co-to-jest-i-co-dostajesz) |
| 2 | [Co serwer produkcyjny musi mieć](#2-co-serwer-produkcyjny-musi-mieć) |
| 3 | [Instalacja na Debianie/Ubuntu — Apache](#3-instalacja-na-debianieubuntuu--apache) |
| 4 | [Instalacja na Debianie/Ubuntu — Nginx](#4-instalacja-na-debianieubuntuu--nginx) |
| 5 | [Instalacja na innych hostingach](#5-instalacja-na-innych-hostingach) |
| 6 | [Gdzie wpisać hasło bazy](#6-gdzie-wpisać-hasło-bazy) |
| 7 | [Dane: import, kodowanie, naprawa](#7-dane-import-kodowanie-naprawa) |
| 8 | [Konta użytkowników i role](#8-konta-użytkowników-i-role) |
| 9 | [Przegląd funkcji — co warto kliknąć](#9-przegląd-funkcji--co-warto-kliknąć) |
| 10 | [Aktualizacja panelu](#10-aktualizacja-panelu) |
| 11 | [Kopia zapasowa i odtworzenie](#11-kopia-zapasowa-i-odtworzenie) |
| 12 | [Podgląd na własnym komputerze](#12-podgląd-na-własnym-komputerze) |
| 13 | [Checklista po instalacji](#13-checklista-po-instalacji) |
| 14 | [Rozwiązywanie problemów](#14-rozwiązywanie-problemów) |
| 15 | [HOWTO — typowe zadania](#15-howto--typowe-zadania) |
| 16 | [Notatki techniczne — przemyślenia i decyzje](#16-notatki-techniczne--przemyślenia-i-decyzje) |
| 17 | [Referencja danych](#17-referencja-danych) |
| 18 | [Bezpieczeństwo — checklist](#18-bezpieczeństwo--checklist) |
| 19 | [Historia zmian](#19-historia-zmian) |

---

## 0. TL;DR — co zrobić w 10 minut

Jeśli masz serwer Debian/Ubuntu i bazę `magazyn` już z danymi:

```bash
# 1. pakiety
sudo apt update
sudo apt install -y apache2 mariadb-server libapache2-mod-php \
    php-mysql php-mbstring php-curl php-xml unzip

# 2. użytkownik bazy (w konsoli mysql: ...)
sudo mysql -e "CREATE USER 'magazyn_app'@'localhost' IDENTIFIED BY '<TWOJE_SILNE_HASLO>';"
sudo mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE ON magazyn.* TO 'magazyn_app'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 3. pliki panelu
sudo mkdir -p /var/www/html/twojapaczka
cd /var/www/html/twojapaczka && sudo unzip -o /sciezka/do/magazyn-panel.zip

# 4. uprawnienia
sudo chown -R www-data:www-data /var/www/html/twojapaczka
sudo chmod -R 750 /var/www/html/twojapaczka
sudo chmod 755 /var/www/html/twojapaczka/config

# 5. Apache — BEZ tego pliki .htaccess są ignorowane
sudo a2enmod headers rewrite
sudo sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
sudo systemctl restart apache2

# 6. pusta baza (tylko gdy jej jeszcze nie ma)
sudo mysql --default-character-set=utf8mb4 magazyn < sql/schema.sql
```

Potem otwórz `https://twojadomena.pl/twojapaczka/`, wpisz dane bazy w kreatorze
i załóż konto administratora. Gotowe.

> **Najczęstsze dwa błędy:** brak `AllowOverride All` w Apache (katalog `config/` staje się publiczny)
> oraz hasło bazy różniące się od tego z `CREATE USER`.

---

## 1. Co to jest i co dostajesz

Panel administracyjny do bazy danych magazynu (drukarki, modele, materiały,
pracownicy, wymiany). Czysty **PHP + MariaDB/MySQL**, bez frameworka i **bez
żadnych zależności zewnętrznych** — nie potrzebuje Composera, npm, internetu
ani CDN-a. Routing: `index.php?r=...` (bez `mod_rewrite`).

**Panel nie zmienia struktury bazy.** Wykrywa tabele automatycznie z
`INFORMATION_SCHEMA`, więc ta sama wersja obsłuży Twoją obecną bazę, jak i jej
kopię na produkcji. Użytkownik bazy potrzebuje wyłącznie praw
`SELECT, INSERT, UPDATE, DELETE`.

**Co jest w repozytorium (bez danych i bez zrzutów ekranu):**

```
magazyn-panel/
├── index.php                  # kontroler frontowy - wszystkie trasy
├── .htaccess                  # ochrona katalogu glownego (Apache)
├── INSTALACJA-DEBIAN.txt      # komendy instalacji - wersja terminalowa
├── PRZEWODNIK.md              # ten plik
├── README.md                  # skrot instrukcji
├── config/                    # konfiguracja (patrz rozdzial 6)
│   ├── config.php             # dane bazy - EDYTUJESZ lub zostawiasz dla kreatora
│   ├── config.sample.php      # wzorzec do wypelnienia
│   ├── config.local.php.example
│   ├── .htaccess              # blokada dostepu z webu
│   └── (po instalacji tworzy sie) db.local.php, users.php, panel-data.json
├── app/                       # logika (13 klas)
├── views/                     # 15 szablonow stron
├── assets/                    # css, js, herb powiatu
├── sql/                       # schema.sql + dump z danymi
├── bin/                       # set-password.php, repair-encoding.php
└── docs/                      # instrukcja uzytkownika
```

**Trzy pliki, które tworzą się same po instalacji i NIE są w paczce** (celowo —
to dane wrażliwe i ustawienia):

| Plik | Zawartość | Powstaje |
|---|---|---|
| `config/db.local.php` | dane do połączenia z bazą (hasło **jawnym tekstem**) | kreator |
| `config/users.php` | konta użytkowników i hashe bcrypt | kreator / `bin/set-password.php` |
| `config/panel-data.json` | ustawienia panelu (materiały pomijane przy zamówieniu) | przyciski „⛔ Pomiń" |

---

## 2. Co serwer produkcyjny musi mieć

| Element | Wymaganie | Uwagi |
|---|---|---|
| PHP | **8.0 lub nowszy** | czysty PHP, bez frameworka |
| Rozszerzenia PHP | `pdo_mysql`, `mbstring`, `iconv` | `json`, `session`, `openssl` są w standardzie PHP 8 |
| Baza | MariaDB 10.x albo MySQL 5.7+ | **musi być `utf8mb4`** |
| Serwer WWW | Apache (mod_php / PHP-FPM) albo Nginx + PHP-FPM | Nginx wymaga jednej dodatkowej reguły |
| Uprawnienia | `config/` **zapisywalny** dla PHP | tam lądują dane bazy i konta |
| Sesje PHP | zapisywalny `session.save_path` | domyślnie jest na serwerze |
| Certyfikat | HTTPS | zalecany — logowanie przesyła hasło |

**Nie jest potrzebne:** Composer, npm, internet, CDN, `mod_rewrite`, dostęp do
panelu hostingu, konta root na bazie.

### Sprawdzenie gotowości serwera

```bash
php -v                                # musi być >= 8.0
php -m | grep -E 'pdo_mysql|mbstring|iconv'   # trzy linijki
php -r 'echo ini_get("session.save_path"), "\n";'
ls -ld /var/lib/php/sessions          # katalog sesji musi istniec
```

Jeśli brakuje rozszerzeń:

```bash
sudo apt install -y php-mysql php-mbstring php-iconv
```

Na starszych Debianach (PHP 7.4) pakiety mają w nazwie numer wersji:
`php7.4-mysql`, `php7.4-mbstring`, `php7.4-iconv`.

---

## 3. Instalacja na Debianie/Ubuntu — Apache

### 3.1 Pakiety

```bash
sudo apt update
sudo apt install -y apache2 mariadb-server libapache2-mod-php \
    php-mysql php-mbstring php-curl php-xml unzip
```

### 3.2 Baza danych i użytkownik

```bash
sudo mysql
```

```sql
CREATE DATABASE magazyn CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE USER 'magazyn_app'@'localhost' IDENTIFIED BY '<TWOJE_SILNE_HASLO>';
GRANT SELECT, INSERT, UPDATE, DELETE ON magazyn.* TO 'magazyn_app'@'localhost';
FLUSH PRIVILEGES;

exit
```

Uwagi:
- jeśli baza `magazyn` **już istnieje z danymi** — nie twórz jej drugi raz, wystarczy `CREATE USER` + `GRANT`
- jeśli baza ma być na innym hoście: `CREATE USER 'magazyn_app'@'%'` i dopisz do `GRANT`: `REQUIRE SSL;`
- hasło bazy to hasło do tabel, **nie** hasło do logowania do panelu

### 3.3 Pliki panelu

```bash
sudo mkdir -p /var/www/html/twojapaczka
cd /var/www/html/twojapaczka
sudo unzip -o /ścieżka/do/magazyn-panel.zip
ls -l index.php          # sprawdzenie, że są pliki
```

Pliki leżą **w katalogu głównym archiwum** — nie trzeba tworzyć dodatkowego folderu.

### 3.4 Uprawnienia

```bash
sudo chown -R www-data:www-data /var/www/html/twojapaczka
sudo chmod -R 750 /var/www/html/twojapaczka
sudo chmod 755 /var/www/html/twojapaczka/config
```

Katalog `config/` **musi** być zapisywalny dla PHP — tam panel zapisuje dane bazy
i konta. Na hostingu www, gdzie PHP działa jako Twój użytkownik, wystarczy
`chmod 755 config`.

### 3.5 Apache — włącz `AllowOverride` (krytyczne!)

Pliki `.htaccess` chroniące `config/`, `app/`, `sql/`, `bin/`, `docs/`
działają **tylko** gdy Apache ma włączone `AllowOverride`. Na Debianie jest
domyślnie wyłączone — bez tego katalog z hasłem do bazy i hashami kont jest
**dostępny publicznie** pod adresem przeglądarki.

```bash
sudo a2enmod headers rewrite
sudo sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
sudo systemctl restart apache2
```

Weryfikacja:

```bash
grep -n "AllowOverride" /etc/apache2/apache2.conf
```

Alternatywa — zamknąć katalogi w vhoście (wydajniej, `AllowOverride` niepotrzebne):

```apache
# /etc/apache2/sites-available/twojapaczka.conf
<Directory /var/www/html/twojapaczka/config>
    Require all denied
</Directory>
<Directory /var/www/html/twojapaczka/app>
    Require all denied
</Directory>
<Directory /var/www/html/twojapaczka/sql>
    Require all denied
</Directory>
<Directory /var/www/html/twojapaczka/bin>
    Require all denied
</Directory>
<Directory /var/www/html/twojapaczka/docs>
    Require all denied
</Directory>
```

```bash
sudo a2ensite twojapaczka && sudo systemctl reload apache2
```

### 3.6 Dane (tylko gdy baza jest pusta)

```bash
cd /var/www/html/twojapaczka
sudo mysql --default-character-set=utf8mb4 magazyn < sql/schema.sql
```

To tworzy **pustą bazę** — tabele startują od ID = 1. Dane wprowadzasz później:
przez panel albo importem własnego dumpa (zawsze z `--default-character-set=utf8mb4`).

**Zawsze podawaj `--default-character-set=utf8mb4`** — bez tego import
przekształca polskie znaki (patrz [rozdział 16.8](#168-trzy-pułapki-weryfikacji-bazy)).

### 3.7 Naprawa kodowania znaków

```bash
cd /var/www/html/twojapaczka
sudo -u www-data php bin/repair-encoding.php            # raport - sprawdź listę
sudo -u www-data php bin/repair-encoding.php --write    # zapis
```

Powinno znaleźć dokładnie **1 wiersz** do poprawy (patrz
[16.9](#169-diagnoza-uszkodzonych-polskich-znaków)).

### 3.8 Pierwsze uruchomienie — kreator

Otwórz `https://twojadomena.pl/twojapaczka/` i przejdź kreatorem:

**Krok 1 — połączenie z bazą**

| Pole | Wartość |
|---|---|
| Host | `localhost` |
| Port | `3306` |
| Nazwa bazy | `magazyn` |
| Użytkownik | `magazyn_app` |
| Hasło | hasło z punktu 3.2 |
| Socket | puste (chyba że MariaDB słucha tylko na gnieździe) |

Panel **testuje połączenie** i zapisuje ustawienia dopiero po sukcesie.

**Krok 2 — konto administratora:** login, nazwa wyświetlana, hasło (min. 8 znaków).

### 3.9 HTTPS

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d twojadomena.pl
```

---

## 4. Instalacja na Debianie/Ubuntu — Nginx

Różnica względem Apache: **Nginx ignoruje pliki `.htaccess`**, więc blokady
musisz zrobić w konfiguracji serwera.

```bash
sudo apt update
sudo apt install -y nginx mariadb-server php-fpm php-mysql php-mbstring \
    php-curl php-xml unzip
```

Wykryj gniazdo PHP-FPM (numer wersji zależy od systemu):

```bash
ls /run/php/php*-fpm.sock
systemctl list-units 'php*-fpm.service'
```

Konfiguracja `/etc/nginx/sites-available/magazyn`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name twojadomena.pl;

    root /var/www/html;
    index index.php;

    # --- blokada katalogow z kodem i konfiguracja ---------------------
    # Regula dziala takze dla panelu w podkatalogu
    # (/twojapaczka/config/, /twojapaczka/app/ itd.)
    location ~* ^/(?:[^/]+/)?(?:config|app|sql|bin|docs)/ {
        deny all;
    }

    # --- PHP ----------------------------------------------------------
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;   # <-- wstaw swoje gniazdo
    }

    # --- panel w podkatalogu -----------------------------------------
    location /twojapaczka/ {
        try_files $uri $uri/ /twojapaczka/index.php?$query_string;
    }

    # --- reszta witryny (opcjonalnie) --------------------------------
    location / {
        try_files $uri $uri/ =404;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/magazyn /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Jeśli panel ma być w **katalogu głównym** domeny: zmień `root` na
`/var/www/html/twojapaczka` i usuń blok `location /twojapaczka/`.

Reszta kroków (baza, pliki, uprawnienia, import, kreator) — identycznie jak
w rozdziale 3, od punktu 3.2.

---

## 5. Instalacja na innych hostingach

| Hosting | Ścieżka docelowa | Adres po wgraniu |
|---|---|---|
| Debian/Ubuntu + Apache | `/var/www/html/twojapaczka/` | `http://twoj-serwer.pl/twojapaczka/` |
| Debian/Ubuntu + Apache (domena) | `/var/www/domena.pl/twojapaczka/` | `https://domena.pl/twojapaczka/` |
| home.pl / Cyberduck / FTP | `public_html/twojapaczka/` | `https://twojadomena.pl/twojapaczka/` |
| OVH mutualisé | `www/twojapaczka/` | `https://twojadomena.pl/twojapaczka/` |
| VPS / własny serwer + Nginx | `/var/www/twojapaczka/` | `https://twojadomena.pl/twojapaczka/` |

**Jak znaleźć właściwy katalog:** zaloguj się przez FTP/Panel i wejdź tam,
gdzie mas już `index.html` albo `index.php` swojej strony — to katalog
główny witryny. Utwórz w nim podkatalog `twojapaczka` i wgraj do niego
zawartość paczki.

Na hostingu www zazwyczaj wystarczy:
- wgranie plików przez FTP
- `chmod 755 config` (albo 777, jeśli kreator zgłosi brak zapisu)
- utworzenie użytkownika bazy przez panel hostingu (nazwa bazy, login, hasło)
- wejście na adres i kreator

---

## 6. Gdzie wpisać hasło bazy

**Sposób 1 — kreator (zalecany).** Otwórz `https://twojadomena.pl/twojapaczka/`.
Kreator poprosi o host, port, nazwę bazy, użytkownika, hasło i socket.
**Testuje połączenie przed zapisem** i zapisuje dane w `config/db.local.php`.
Nie trzeba nic edytować ręcznie.

**Sposób 2 — ręcznie w `config/config.php`:**

```php
'db' => [
    'host'    => 'localhost',
    'port'    => 3306,
    'name'    => 'magazyn',
    'user'    => 'magazyn_app',
    'pass'    => 'TUTAJ_WPISZ_SWOJE_HASLO',   // <-- to zmień
    'charset' => 'utf8mb4',
    'socket'  => '',
    'show_errors' => true,
],
```

**Uwaga na priorytet plików.** Panel nakłada konfiguracje w kolejności:

```
config/config.php  →  config/config.local.php  →  config/db.local.php  →  config/auth.local.php
```

Każdy kolejny plik **nadpisuje** poprzedni. Konsekwencje:

- jeśli `config/db.local.php` **istnieje** (kreator już był użyty), to **on** decyduje
  — edycja `config/config.php` nie zadziała. Edytuj `config/db.local.php`
  albo usuń go i przejdź kreatorem ponownie
- `config/config.local.php` służy do nadpisań lokalnych (np. przy testach);
  na produkcji zwykle go nie ma
- `config/config.local.php` **nie jest** w paczce, więc przy aktualizacji
  pozostaje nietknięty

**Czym się różnią hasła:**

| Hasło | Gdzie | Jak ustawić |
|---|---|---|
| hasło **bazy** | `CREATE USER` + `config/db.local.php` lub `config.php` | kreator albo edycja pliku |
| hasło **logowania do panelu** | `config/users.php` (bcrypt) | kreator albo `php bin/set-password.php "Haslo" --write` |

`bin/set-password.php` ustawia hasło **administratora panelu**, nie bazy.
Nie pomyl tych dwóch.

**Sprawdzenie przed otwarciem panelu:**

```bash
mysql -u magazyn_app -p -h localhost magazyn
```

Wejście bez błędu = dane poprawne. „Access denied" = problem z użytkownikiem
bazy, nie z panelem.

---

## 7. Dane: import, kodowanie, naprawa

### 7.1 Import

```bash
cd /var/www/html/twojapaczka
sudo mysql --default-character-set=utf8mb4 magazyn < sql/schema.sql
```

- `sql/schema.sql` — **sama struktura** (CREATE TABLE), bez żadnych danych
- repozytorium **nie zawiera** dumpa z danymi — baza po imporcie jest pusta,
  tabele startują od ID = 1 i możesz wypełniać je od zera

Dane wprowadzasz na dwa sposoby:

1. **przez panel** — menu *Tabele* → dodawanie rekordów
2. **importem własnego dumpa**:
   ```bash
   sudo mysql --default-character-set=utf8mb4 magazyn < twoj-dump.sql
   ```

Jeśli baza ma już dane — **pomiń ten krok**.

### 7.2 Naprawa kodowania

W dumpie jeden wiersz (`wymiany` ID 60) ma zniekształcony znak —
`trzeba odebraÄ‡` zamiast `trzeba odebrać`. Popraw go:

```bash
sudo -u www-data php bin/repair-encoding.php            # raport
sudo -u www-data php bin/repair-encoding.php --write    # zapis
```

Narzędzie skanuje wszystkie kolumny tekstowe wszystkich tabel i poprawia
**tylko** wartości, które jednoznacznie wyglądają na podwójnie zakodowane
(po poprawnej konwersji pojawiają się polskie znaki, których wcześniej nie
było). Nic nie zmyśla — każda poprawka to odwrócenie konwersji
UTF-8 → Windows-1250.

### 7.3 Pusta baza po imporcie struktury

Po `mysql magazyn < sql/schema.sql` wszystkie tabele są puste, a liczniki
`AUTO_INCREMENT` startują od 1 (tabele `model` i `wymiany`). Panel działa na
pustej bazie bez błędów — pulpit pokazuje zera, listy są puste, formularze
i eksport CSV działają.

| Tabela | Kolumny |
|---|---|
| `model` | `IDmodel`, `Nazwa` |
| `drukarki` | `IDdrukarki`, `Lokalizacja`, `Adres_IP`, `INFO`, `IDmodel` |
| `asortyment` | `IDasortyment`, `Nazwa`, `Symbol`, `Ilosc`, `IDmodel` |
| `pracownik` | `IDpracownik`, `Imie`, `Nazwisko` |
| `wymiany` | `IDwymiany`, `Data`, `Licznik`, `IDmodel`, `IDdrukarki`, `IDasortyment`, `IDpracownik`, `Info` |

---

## 8. Konta użytkowników i role

| Rola | Może | Nie może |
|---|---|---|
| **Administrator** | wszystko: zapis, usuwanie, zarządzanie kontami | — |
| **Tylko odczyt** | przeglądanie, wyszukiwanie, eksport CSV, zmiana własnego hasła | zapisu ani usuwania |

Blokada zapisu działa **po stronie serwera**, nie tylko w interfejsie — przycisk
jest ukryty, a POST zapisu jest odrzucany (komunikat: *„Twoje konto ma
uprawnienia tylko odczyt…"*). Dla konta read-only menu „Konta użytkowników"
jest niedostępne.

Panel **nie ma żadnego domyślnego hasła**. Hasło zakłada administrator:
w kreatorze albo z terminala:

```bash
cd /var/www/html/twojapaczka
sudo -u www-data php bin/set-password.php "TwojeNoweSilneHaslo" --write
```

Każdy użytkownik zmienia własne hasło: klik w panelu na blok z nazwą
zalogowanego użytkownika na dole menu bocznego (`ADMIN · zalogowany · zmień hasło`).

Ostatniego konta administratora nie da się usunąć ani zdegradować do roli
tylko-odczyt.

---

## 9. Przegląd funkcji — co warto kliknąć

| Ekran | Co sprawdzić |
|---|---|
| **Pulpit** | karty stanu; **wykres 12 miesięcy** — liczba nad każdym słupkiem, oś Y z siatką, maksimum wyróżnione pomarańczem, miesiące w osobnym wierszu, podsumowanie (łącznie / średnia / najwięcej / zmiana), klikalny słupek otwiera wymiany z tego miesiąca; „Materiały do zamówienia" (stan ≤ 0) z przyciskiem **⛔ Pomiń** |
| **Wymiany** | filtry: urządzenie, model, pracownik, materiał, zakres dat, dowolny tekst w uwagach; presety okresu (*7 dni, 30 dni, 90 dni, ten miesiąc, ten rok*); zaznaczanie wielu wierszy + **usuwanie masowe**; **eksport CSV** z aktualnym filtrem |
| **Urządzenia** | rejestr drukarek; klik w lokalizację → karta urządzenia: dane, statystyki, materiały pasujące do modelu, historia wymian z filtrem po pracowniku; przy edycji checkbox **„Przepisz Model także w N wymianach tego urządzenia"** |
| **Tabela** (ikona 👁 przy rekordzie) | sekcja **Powiązane rekordy** — klik otwiera listę od razu przefiltrowaną do tego rekordu; przycisk **Usuń** przy powiązanym rekordzie pokazuje skutki i blokuje skasowanie, jeśli klucz obcy na to nie pozwala |
| **Formularz** | opisy pól (ikona ⓘ), kalendarz daty, wybór urządzenia → model ustawia się sam → lista materiałów zawęża się do pasujących do modelu; etykieta materiału ma symbol i model, np. `Toner czarny (45807111) - OKI B432` |
| **Wyszukiwarka** | przeszukuje wszystkie tekstowe pola wszystkich tabel naraz |
| **Struktura bazy** | wszystkie wykryte tabele, kolumny, typy, klucze obce i reguły kasowania |
| **Konta użytkowników** | lista kont, dodawanie, zmiana loginu i nazwy, **przełączanie roli**, wyłączanie konta, reset hasła, usuwanie |

---

## 10. Aktualizacja panelu

Paczka **nie zawiera** `config/db.local.php`, `config/users.php` ani
`config/panel-data.json` — nową wersję można bezpiecznie rozpakować **na wierzch**
istniejącej. Konta, dane bazy i lista pomijanych materiałów zostaną nietknięte.

```bash
cd /var/www/html/twojapaczka
sudo unzip -o /ścieżka/do/magazyn-panel.zip
sudo chown -R www-data:www-data /var/www/html/twojapaczka
sudo chmod 755 /var/www/html/twojapaczka/config
```

Przed aktualizacją zrób kopię ([rozdział 11](#11-kopia-zapasowa-i-odtworzenie)).

---

## 11. Kopia zapasowa i odtworzenie

Panel trzyma wrażliwe rzeczy **poza bazą** — hasło do bazy (jawnym tekstem)
i hashe kont leżą w katalogu `config/`. Backup to dwie rzeczy:

```bash
# baza
mysqldump -u root -p --default-character-set=utf8mb4 magazyn \
    > backup-magazyn-$(date +%F).sql

# konfiguracja panelu (konta + dane do dolaczenia)
sudo tar czf backup-panel-config-$(date +%F).tar.gz /var/www/html/twojapaczka/config
```

**Odtwarzanie:**

```bash
mysql -u root -p --default-character-set=utf8mb4 magazyn \
    < backup-magazyn-2026-11-09.sql

sudo tar xzf backup-panel-config-2026-11-09.tar.gz -C /
sudo chown -R www-data:www-data /var/www/html/twojapaczka/config
sudo chmod 755 /var/www/html/twojapaczka/config
```

---

## 12. Podgląd na własnym komputerze

Żeby obejrzeć panel bez konfigurowania Apache i bazy:

```bash
mkdir -p podglad/twojapaczka
unzip magazyn-panel.zip -d podglad/twojapaczka/

mysql -e "CREATE DATABASE magazyn_podglad CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql --default-character-set=utf8mb4 magazyn_podglad < podglad/twojapaczka/sql/schema.sql

cd podglad
php -S 127.0.0.1:8950
```

Otwórz `http://127.0.0.1:8950/twojapaczka/` i przejdź kreatorem.

Żeby panel był dostępny też z telefonu w sieci lokalnej, uruchom
`php -S 0.0.0.0:8950` i wejdź na `http://<adres-ip-komputera>:8950/twojapaczka/`.

> **Uwaga:** `php -S` to serwer deweloperski — **nie respektuje plików `.htaccess`**,
> więc katalogi `sql/` i `config/` są w nim dostępne jako pliki. Nigdy nie
> wystawiaj tak uruchomionego panelu publicznie. Na produkcji obowiązuje
> Apache/Nginx z regułami z rozdziałów 3.5 i 4.

---

## 13. Checklista po instalacji

- [ ] `php -v` pokazuje 8.0+
- [ ] `php -m | grep -E 'pdo_mysql|mbstring|iconv'` → trzy linijki
- [ ] `mysql -u magazyn_app -p -h localhost magazyn` → wchodzi
- [ ] panel otwiera się i **nie** prosi ponownie o kreator
- [ ] logowanie działa, pod loginem widać rolę
- [ ] pulpit pokazuje 25 modeli / 67 drukarek / 114 materiałów / 5 pracowników / 644 wymiany
- [ ] menu **Struktura bazy** pokazuje 5 tabel
- [ ] `https://twojadomena.pl/twojapaczka/config/db.local.php` → **403**, nie 200
- [ ] `https://twojadomena.pl/twojapaczka/sql/schema.sql` → **403**
- [ ] katalog główny nie pokazuje spisu plików
- [ ] konto z rolą *Tylko odczyt* nie ma przycisków zapisu
- [ ] eksport CSV działa
- [ ] `show_errors` jest `false` (kreator to ustawia)
- [ ] HTTPS działa i certyfikat się odnawia (`sudo certbot renew --dry-run`)

---

## 14. Rozwiązywanie problemów

| Objaw | Przyczyna / rozwiązanie |
|---|---|
| „Brak połączenia z bazą danych" | Złe dane w kreatorze albo MariaDB nie działa. Sprawdź `mysql -u magazyn_app -p -h localhost magazyn`. |
| `config/db.local.php` otwiera się w przeglądarce | Apache ignoruje `.htaccess` — włącz `AllowOverride All` (3.5) albo `Require all denied` w vhoście. |
| Pliki z `sql/` są pobieralne | j.w. — w Nginx reguła `location ~* ... deny all`. |
| Panel nie chce zapisywać kont albo ustawień | `config/` nie jest zapisywalny dla PHP — `chmod 755 config`. |
| Panel prosi o login w pętli | Usuń `config/config.local.php` (może nadpisywać ustawienia) i wyczyść cookies przeglądarki. |
| „Hasło administratora nie zostało jeszcze ustawione" | Panel czeka na ustawienie hasła — wejdź na `index.php?r=setup`, albo sprawdź `config/users.php`. |
| „Nie znaleziono rekordu" po kliknięciu w powiązane rekordy | Rekord został usunięty w międzyczasie. |
| „Nie znaleziono strony" | Zły `base_path` w `config/config.php` — dla podkatalogu `/twojapaczka` wpisz `'/twojapaczka'`. |
| Pusta lista tabel | Brak praw użytkownika — `GRANT SELECT, INSERT, UPDATE, DELETE ON magazyn.*`. |
| Krzaczki w polskich znakach | `php bin/repair-encoding.php --write` — i przy imporcie używaj `--default-character-set=utf8mb4`. |
| Nie działa kalendarz | Za stary przeglądarka — wymagany ES6. Pole daty działa jako natywny wybór daty. |
| Biały ekran | Włącz `show_errors` w `config/config.php` i sprawdź log serwera (`/var/log/php/error.log` lub `journalctl -u php-fpm`). |
| Panel wolny | `AllowOverride All` powoduje czytanie `.htaccess` przy każdym żądaniu — zamknij katalogi w vhoście zamiast tego. |
| „Nie udało się zapisać hasła" | `chmod 755 config`. |
| Sesja ginie po zalogowaniu | Katalog sesji PHP nie jest zapisywalny — sprawdź `php -r 'echo ini_get("session.save_path");'`. |

---

## 15. HOWTO — typowe zadania

**Dodać konto z terminala:**
```bash
cd /var/www/html/twojapaczka
sudo -u www-data php bin/set-password.php "NoweHaslo" --write --login NOWYLOGIN
```
(Uwaga: tworzy konto z rolą administratora. Konto tylko-odczyt dodaj z panelu.)

**Zmienić hasło administratora z panelu:**
klik w blok zalogowanego użytkownika na dole menu bocznego → „zmień hasło".

**Wymuś ponowne uruchomienie kreatora** (np. gdy zmieniły się dane bazy):
```bash
sudo rm -f /var/www/html/twojapaczka/config/db.local.php
```
potem wejdź na `https://twojadomena.pl/twojapaczka/?r=setup`.

**Sprawdzić, czy panel łączy się z bazą z terminala:**
```bash
cd /var/www/html/twojapaczka
sudo -u www-data php -r 'require "app/bootstrap.php"; var_dump(Database::testConnection());'
```

**Zobaczyć logi Apache:**
```bash
sudo tail -f /var/log/apache2/error.log
```

**Restart po zmianie konfiguracji:**
```bash
sudo systemctl reload apache2      # albo: sudo systemctl reload nginx
sudo systemctl reload php8.2-fpm  # po zmianach w php.ini
```

---

## 16. Notatki techniczne — przemyślenia i decyzje

### 16.1 Dlaczego konta są w plikach, a nie w bazie

Baza `magazyn` należy do innego projektu i **nie wolno** było zmieniać jej
schematu. Konta, hasła i flagi panelu są więc realizowane **plikami**:
`config/users.php`, `config/db.local.php`, `config/panel-data.json`.

Konsekwencje, o których trzeba wiedzieć:
- panel nadal ma pełne prawa do danych (`SELECT, INSERT, UPDATE, DELETE`) —
  to wystarczy i jest wymagane do pracy z tabelami
- katalog `config/` musi być zapisywalny dla PHP i **niedostępny z webu**
- kopia zapasowa panelu = dump bazy + kopia `config/`
- aktualizacja panelu nie nadpisuje kont (bo te pliki nie są w paczce)

### 16.2 Dlaczego nie ma domyślnego hasła

Panel nie ma żadnego hasła w kodzie ani w instrukcji. Pierwsze konto zakłada
administrator w kreatorze. To eliminuje klasę problemów, w której domyślne
hasło administratora zostaje wdrożone na produkcję i o nim wszyscy wiedzą.
Ekran logowania nie pokazuje żadnych podpowiedzi.

### 16.3 Autodetekcja ścieżki

Panel sam odczytuje swoją ścieżkę z `SCRIPT_NAME`, więc działa w katalogu
głównym domeny i w podkatalogu bez konfiguracji serwera. `base_path` w
`config/config.php` to tylko awaryjne ręczne ustawienie.

Routing to `index.php?r=...` — **bez `mod_rewrite`**, więc nie trzeba nic
zmieniać w konfiguracji Apache/Nginx poza blokadą katalogów.

### 16.4 Klucze obce i bezpieczne usuwanie

Reguły kasowania w bazie (odczytane z `INFORMATION_SCHEMA`):

| Kolumna | Wskazuje na | Reguła |
|---|---|---|
| `asortyment.IDmodel` | `model` | `RESTRICT` |
| `drukarki.IDmodel` | `model` | `RESTRICT` |
| `wymiany.IDasortyment` | `asortyment` | `SET NULL` |
| `wymiany.IDdrukarki` | `drukarki` | `SET NULL` |
| `wymiany.IDpracownik` | `pracownik` | `SET NULL` |
| `wymiany.IDmodel` | — | brak klucza obcego |

Panel czyta `DELETE_RULE` i **egzekwuje go w interfejsie**:

- `RESTRICT` → twarda blokada (baza i tak nie pozwoli usunąć) — panel mówi,
  co trzeba najpierw usunąć lub zmienić
- `SET NULL` / `CASCADE` → ostrzeżenie z listą powiązanych rekordów i przycisk
  *„Usuń mimo to"* (parametr `_force=1`)
- usuwanie masowe działa tak samo — najpierw pokazuje podsumowanie skutków

### 16.5 Propagacja modelu

Historia wymian jest powiązana z **urządzeniem**, nie z modelem — samo
zmienienie modelu urządzenia nie kasuje ani jednego wpisu. Przy edycji
urządzenia pojawia się checkbox *„Przepisz Model także w N wymianach tego
urządzenia"*: bez niego stare wymiany zachowują model z chwili wymiany, z nim
model jest przepisywany we wszystkich wymianach tego urządzenia. Panel
pokazuje w komunikacie, ile rekordów zostało zaktualizowanych.

### 16.6 Ignorowanie materiałów

Materiał archiwalny może mieć stan ujemny, a mimo to nie powinien być
zamawiany. Przycisk **„⛔ Pomiń przy zamówieniu"** chowa materiał z listy
*do zamówienia* i z licznika na pulpicie, ale **nie usuwa go z bazy**. Flaga
leży w `config/panel-data.json` — schemat bazy pozostaje nietknięty.

### 16.7 Wykres 12 miesięcy

Pierwsza wersja wyglądała jak „coś jest wyżej lub mniej" — same słupki bez
wartości. Przebudowa: liczba **nad** słupkiem, oś Y z siatką i podziałką,
maksimum wyróżnione osobnym kolorem, miesiące w osobnym wierszu pod osią,
podsumowanie (łącznie / średnia / najwięcej / zmiana vs poprzedni miesiąc),
rozwijana tabela miesięcy i **klikalne słupki** otwierające wymiany z danego
miesiąca. Wartości biorą się z `Stats.php`, są zgodne z tym, co zwraca SQL.

### 16.8 Trzy pułapki weryfikacji bazy

Podczas audytu bazy `magazyn` trzy metody porównania dały **fałszywe
odpowiedzi**. Każda z nich może wprowadzić w błąd — warto je znać:

| Metoda | Dlaczego myli |
|---|---|
| `CHECKSUM TABLE` | Dla InnoDB reaguje na **fizyczny stan wiersza** (trx_id, roll pointer), nie na widoczne wartości. Po UPDATE i powrocie do pierwotnej wartości suma już nie wraca do wyjściowej. |
| `NOT (a.kol <=> b.kol)` | `utf8mb4_general_ci` **ignoruje diakrytyki** — „zużyty" = „zuzyty", „odebrać" = „odebrac". Warunek uznaje rekordy za identyczne. |
| `mysql` bez jawnego charsetu | Klient na tej maszynie ma domyślnie `character_set_client=latin1` — czytanie kolumn utf8mb4 zamienia polskie znaki na `?` **po obu stronach**, więc porównanie wygląda na zgodność, a jest odwrotnie. |

**Jedyna wiarygodna metoda** — porównanie bajtowe:

```bash
mysqldump --no-create-info --skip-extended-insert --compact \
    --default-character-set=utf8mb4 baza1 > a.sql
mysqldump --no-create-info --skip-extended-insert --compact \
    --default-character-set=utf8mb4 baza2 > b.sql
cmp -s a.sql b.sql && echo "IDENTYCZNE" || diff a.sql b.sql | head
```

Do wykrywania konkretnych różnic używaj porównania binarnego:

```sql
WHERE NOT (BINARY a.kol <=> BINARY b.kol)
```

i przywracaj wartości **wyłącznie z pliku**:

```sql
UPDATE baza.tabela a JOIN plik_baza.tabela b ON a.pk = b.pk
SET a.kol = b.kol
WHERE NOT (BINARY a.kol <=> BINARY b.kol);
```

### 16.9 Diagnoza uszkodzonych polskich znaków

Trzy objawy, które warto rozróżnić:

| Objaw w bazie | Znaczenie |
|---|---|
| `Ä‡` zamiast `ć` (bajty `C3 84 E2 80 A1`) | podwójne kodowanie — UTF-8 odczytane jako Windows-1250 i zapisane ponownie. Naprawia `bin/repair-encoding.php`. |
| `zuzyty` zamiast `zużyty` (brak znaku) | konwersja stratna — znak **zniknął** bezpowrotnie, nie da się go odtworzyć z bazy; trzeba wziąć wartość z pliku/dumpu. |
| `?` w miejscu znaku | import wykonany klientem z `latin1` — `?` to znak „nie do odwzorowania". |

**Skąd to się bierze:** dump zrobiony klientem, który łączył się w kodowaniu
`latin1`, albo aplikacja PHP zapisująca przez połączenie `latin1`. Panel łączy się
poprawnie (`charset=utf8mb4` w DSN, `app/Database.php`), dlatego jego zapisy nie
psują znaków — potwierdzone testem na bazie testowej (0 uszkodzonych rekordów).

**Audyt bazy źródłowej — metoda do powtórzenia przy własnym dumpie:**

1. Wczytanie swojego dumpa do czystej bazy tymczasowej
2. Porównanie bajtowe obu baz (metoda z 16.8)
3. Wykrycie rekordów z utraconymi znakami oraz pól, gdzie `NULL` zamieniono na tekst
4. Przywrócenie wszystkich wartości **wyłącznie z pliku** (nic nie zmyślać)
5. Sprawdzenie `AUTO_INCREMENT` — skasowane wiersze nie cofają licznika, więc
   może on wskazywać na dane, których już nie ma:
   ```sql
   ALTER TABLE twoja_baza.model AUTO_INCREMENT = 32;
   ```
6. Naprawa zniekształconych znaków (`bin/repair-encoding.php --write`)
7. Weryfikacja końcowa: `mysqldump` obu baz musi być identyczny bajt w bajt

**Lekcja na przyszłość:** każda aplikacja łącząca się z tą bazą musi używać
`utf8mb4` — inaczej przy każdym zapisie psuje polskie znaki. Dotyczy to również
importu: zawsze `--default-character-set=utf8mb4`.

### 16.10 Cache kont — znaleziony i naprawiony błąd

`Users::all()` trzymał listę kont w statycznym cache'u, a `Users::save()`
**nie odświeżał** tego cache'u. Skutek był widoczny dla użytkownika: w tym samym
żądaniu, które tworzyło nowe konto, lista kont renderowała się ze **starej**
wersji — nowo utworzone konto **nie pojawiało się** na liście do odświeżenia
strony. Ten sam mechanizm mógłby przeszkodzić w ochronie ostatniego
administratora.

Naprawa: cache ustawiany jest w `save()` po udanym zapisie, plus publiczna
metoda `Users::forget()` do ręcznego wyczyszczenia. Zweryfikowane przez UI:
konto utworzone przez `r=konto` jest widoczne natychmiast.

### 16.11 Błąd w narzędziu naprawy kodowania

`bin/repair-encoding.php` w trybie raportu zawsze pokazywał
*„Do poprawienia: 0"*, mimo że wypisywał kandydatów — licznik rósł tylko
w gałęzi zapisu. Poprawione: osobny licznik kandydatów dla trybu raportu.

### 16.12 Serwer deweloperski a `.htaccess`

`php -S` **nie czyta `.htaccess`** — pliki `.htaccess` są mechanizmem
Apache. W trybie deweloperskim katalogi `sql/` i `config/` są więc dostępne
jako pliki. Na produkcji obowiązuje Apache (`AllowOverride All`) albo Nginx
(reguła `deny all`). W instancji podglądowej katalog `sql/` usunięto
ręcznie, a w dokumentacji dodano ostrzeżenie.

### 16.13 Sesje i CSRF

Każdy formularz ma token CSRF; niezgodność blokuje zapis. Sesja jest
regenerowana przy logowaniu. Po 5 nieudanych próbach logowanie jest blokowane
na 5 minut. Hasła w bazie panelu (`config/users.php`) to bcrypt z kosztem 12.

---

## 17. Referencja danych

| Tabela | Klucz | Zawartość | Klucze obce |
|---|---|---|---|
| `model` | `IDmodel` | modele urządzeń (np. „OKI B431") | — |
| `drukarki` | `IDdrukarki` | lokalizacja, adres IP, uwagi, model | `IDmodel` → `model` (RESTRICT) |
| `asortyment` | `IDasortyment` | nazwa, symbol, ilość, model | `IDmodel` → `model` (RESTRICT) |
| `pracownik` | `IDpracownik` | imię, nazwisko | — |
| `wymiany` | `IDwymiany` | data, licznik, model, urządzenie, materiał, pracownik, uwagi | `IDdrukarki`, `IDasortyment`, `IDpracownik` (SET NULL) |

**Kolumny:**
- `model`: `IDmodel`, `Nazwa`
- `drukarki`: `IDdrukarki`, `Lokalizacja`, `Adres_IP`, `INFO`, `IDmodel`
- `asortyment`: `IDasortyment`, `Nazwa`, `Symbol`, `Ilosc`, `IDmodel`
- `pracownik`: `IDpracownik`, `Imie`, `Nazwisko`
- `wymiany`: `IDwymiany`, `Data`, `Licznik`, `IDmodel`, `IDdrukarki`, `IDasortyment`, `IDpracownik`, `Info`

Panel obsługuje też **każdą inną tabelę**, którą dodasz do tej bazy — bez
zmiany kodu. Polskie nazwy i opisy nowych pól dopisuje się w `app/Schema.php`
(tablica `STATIC`).

**Trasy panelu** (routing `index.php?r=...`):
`dashboard`, `wymiany`, `devices`, `device`, `table`, `record`, `form`, `delete`,
`bulk-delete`, `potwierdz-usuniecie`, `potwierdz-usuniecie-maszowe`, `search`,
`structure`, `export`, `konta`, `konto`, `haslo`, `ignoruj-zakup`, `setup`

---

## 18. Bezpieczeństwo — checklist

- [ ] `config/` niedostępny z przeglądarki (`AllowOverride All` albo reguła Nginx)
- [ ] katalog główny nie pokazuje spisu plików
- [ ] HTTPS włączony, certyfikat się odnawia
- [ ] użytkownik bazy ma **tylko** `SELECT, INSERT, UPDATE, DELETE`
- [ ] **brak domyślnego hasła** — hasło administratora ustawione w kreatorze
- [ ] każdy użytkownik ma własne konto, dostępne tylko dla administratora
- [ ] konta zbędne wyłączone (nie usuwane) — historia zostaje
- [ ] `show_errors` = `false` na produkcji
- [ ] regularna kopia: dump bazy + kopia `config/`
- [ ] `config/` ma właściwe prawa (755, właściciel `www-data`)
- [ ] kopia `config/` nie leży w katalogu dostępnym z webu

Wbudowane zabezpieczenia: zapytania SQL wyłącznie przez PDO z parametrami,
nazwy tabel i kolumn weryfikowane wobec schematu (nazwa z URL nie trafia do
SQL), token CSRF przy każdym formularzu, escapowanie danych przy wyświetlaniu,
hasła jako bcrypt, blokada po 5 nieudanych próbach logowania, ochrona
spójności przy usuwaniu.

---

## 19. Historia zmian

| Wersja | Co doszło |
|---|---|
| 1.0 | pulpit z wykresem, moduł Wymiany (filtry, presety, eksport CSV, usuwanie masowe), przeglądarka każdej tabeli (paginacja, sortowanie, filtry, wyszukiwarka), formularze CRUD z walidacją, wyszukiwarka globalna, struktura bazy, logowanie (bcrypt, blokada 5 prób), CSRF, escaping XSS |
| 1.1 | zawężanie listy materiałów do wybranego modelu, etykiety materiałów z symbolem i modelem, prefill modelu po stronie serwera, eksport CSV z filtrami |
| 1.2 | checkbox propagacji modelu do wymian, poprawka sum FK → „Różne: X", nazwa ADMIN, **brak domyślnego hasła**, `config/auth.local.php`, klikalne pole „zalogowany", usunięta podpowiedź o domyślnym haśle |
| 1.3.0 | konta użytkowników w `config/users.php` z rolami admin/readonly, kreator pierwszego uruchomienia (baza + konto), usunięcie wersji PHP/MariaDB z UI, ignorowanie materiałów przy zamówieniu, powiązane rekordy prowadzące do przefiltrowanej listy, blokada usuwania powiązanych rekordów, czytelny wykres 12 miesięcy, autodetekcja `base_path` |
| 1.3.1 | naprawa cache'u listy kont (nowe konto pojawiało się dopiero po odświeżeniu), poprawka licznika w `bin/repair-encoding.php`, `INSTALACJA-DEBIAN.txt`, `PRZEWODNIK.md`, `.htaccess` w katalogu głównym, uzupełnione README (krok `AllowOverride`, checklista, aktualizacja, backup, podgląd lokalny, HTTPS) |

---

*Dokument wygenerowany w trakcie budowy panelu. Zawiera komendy, instrukcję
wdrożenia oraz notatki techniczne z przeprowadzonych testów i audytu bazy.*
