# Instrukcja dla użytkownika — Panel Magazynu

Krótka instrukcja obsługi panelu. Szczegóły techniczne (instalacja, konfiguracja
serwera) znajdziesz w pliku `README.md` w głównym katalogu projektu.

---

## Logowanie


Adres panelu podaje administrator. Loguj się loginem i hasłem, które ustalił administrator.

Po 5 nieudanych próbach logowanie jest blokowane na 5 minut.

**Nie ma żadnego domyślnego hasła.** Przy pierwszym otwarciu panel poprosi o ustawienie
hasła administratora — wystarczy wpisać je dwa razy i gotowe.

---

## Zmiana hasła

W lewym menu, na dole, widzisz blok z nazwą zalogowanego użytkownika
(np. `ADMIN · Administrator · zmień hasło`). **Kliknij w niego** — otworzy się formularz:

1. **Aktualne hasło** — to, którym logujesz się teraz
2. **Nowe hasło** — minimum 8 znaków, nie może być takie samo jak login
3. **Powtórz nowe hasło** — musi zgadzać się z nowym

Po zapisaniu stare hasło przestaje działać. Hasło zapisuje się w pliku
`config/users.php` na serwerze — nie w bazie danych.

**Zmianę hasła może zrobić każdy użytkownik dla siebie.** Dla innych kont hasło
zmienia wyłącznie administrator (menu *Konta użytkowników*).

---

## Konta użytkowników (dla administratora)

Menu **Konta użytkowników** pozwala:

- **dodać konto** — login, nazwa wyświetlana, hasło, rola,
- **zmienić** login, nazwę i **rolę** konta,
- **wyłączyć** konto (konto zostaje, ale nikt się na nim nie zaloguje),
- **zresetować hasło** dowolnemu użytkownikowi,
- **usunąć konto** (jedynego administratora usunąć się nie da).

### Dwie role

| Rola | Co może |
|---|---|
| **Administrator** | wszystko: zapis, usuwanie, zarządzanie kontami |
| **Tylko odczyt** | przeglądać dane, wyszukiwać, eksportować do CSV — **bez zapisu i usuwania** |

Konto „tylko odczyt” nie widzi przycisków dodawania i usuwania, a gdyby ktoś spróbował
zapisać dane bezpośrednio przez przeglądarkę, serwer odrzuci operację.

---

## Pulpit

Ekran startowy pokazuje najważniejsze liczby: ile urządzeń jest w rejestrze, ile
wymian zapisało się w bazie, ile materiałów czeka na zamówienie oraz jaki model
urządzeń jest serwisowany najczęściej.

Na dole są skróty — po kliknięciu w dowolny z nich otwiera się gotowy formularz
dodawania nowego rekordu.

---

## Wymiany — najczęstsze zadanie

Zakładka **Wymiany** to główne miejsce pracy.

### Wybranie urządzenia

W polu **Urządzenie (drukarka)** rozwiń listę i wskaż drukarkę — panel pokaże
tylko wymiany tego urządzenia. Listę można też zawęzić: wpisz fragment nazwy
w polu „filtruj listę urządzeń…” pod rozwijanym menu.

### Wybranie okresu

Szybkie przyciski nad tabelą:

| Przycisk | Znaczenie |
|---|---|
| Wszystkie | wszystkie wymiany w bazie |
| 7 dni / 30 dni / 90 dni | wymiany z ostatniego okresu |
| Ten miesiąc | od pierwszego dnia bieżącego miesiąca |
| Ten rok | od 1 stycznia |
| Ostatnie 10 | dziesięć najnowszych wpisów |

Jeśli potrzebujesz dokładnego przedziału, wpisz daty w polach **Data od** i **Data do**
(każde otwiera kalendarz).

### Dalsze filtry

Możesz łączyć filtry: wybierz **Model**, **Pracownika**, **Materiał** albo wpisz
fragment tekstu w polu **Szukaj w uwagach**. Wszystkie warunki działają jednocześnie.

### Podsumowanie

Karty nad tabelą pokazują liczbę wymian w wynikach, najstarszą i najnowszą datę
oraz sumę i średnią liczników drukarki.

---

## Dodawanie wymiany

Przycisk **＋ Dodaj wymiany** (albo **＋ Dodaj dla tego urządzenia**, jeśli wcześniej
wybrano drukarkę) otwiera formularz.

- **Data** — domyślnie podpowiada dzisiejszy dzień; kliknij w pole, aby otworzyć kalendarz
- **Licznik** — liczba stron wydrukowanych na drukarce w chwili wymiany
- **Model / Urządzenie / Materiał / Pracownik** — listy wyboru; możesz je filtrować
  polem pod każdą listą
- **Uwagi** — np. „1 reset”, informacje o pojemnikach

Przycisk **Zapisz i dodaj kolejny** zapisuje rekord i zostawia formularz otwarty,
co przyspiesza wpisywanie serii wymian.

### Wybieranie materiału (toneru, bębna, fusera)


Nazwy materiałów w bazie powtarzają się — np. „Toner czarny” występuje
6 razy, „Beben cyjan” 4 razy. Każdy materiał należy do **konkretnego modelu
drukarki**, dlatego panel zawęża listę:

1. Wybierz **Urządzenie** — panel sam ustawi **Model** na model tego urządzenia.
2. Lista **Materiał** od razu pokaże tylko pozycje pasujące do wybranego modelu.
   Pod listą pojawi się informacja, ile ich jest, np.
   *„Dla modelu „Sharp MX-3061” pasuje 5 z 114 materiałów.”*
3. W etykiecie materiału zobaczysz **symbol w nawiasie** i **model**, np.
   `Toner czarny (00000000) - OKI B432` — to jednoznacznie identyfikuje pozycję.

Jeśli chcesz zobaczyć wszystkie materiały (np. żeby dodać nowy do modelu),
wybierz **Model** ręcznie albo zostaw puste — polecenie „wybierz model lub
urządzenie, aby zawęzić listę” zniknie.

> 4 materiały w bazie nie mają przypisanego modelu (`IDmodel` jest puste).
> Po wskazaniu modelu są ukryte, bo nie da się stwierdzić, do której drukarki pasują.

---

## Usuwanie i poprawianie

- **Edytuj** (ikonka ołówka) otwiera rekord do poprawy
- **Usuń** (ikonka kosza) kasuje pojedynczy rekord — zawsze z potwierdzeniem
- **Usuń zaznaczone** kasuje wszystkie wiersze z zaznaczonymi checkboxami naraz

> Operacji usunięcia nie da się cofnąć — dlatego przed kasowaniem zawsze
> pojawia się pytanie o potwierdzenie.

### Rekord używany w innych tabelach

Jeśli kasujesz coś, czego używają inne rekordy (np. model drukarki, do której są przypisane
materiały i wymiany), **panel nie usuwa tego od razu**. Najpierw pokaże, gdzie ten rekord
jest używany:

- **baza nie pozwoli usunąć** — wtedy dostaniesz informację, żeby najpierw usunąć
  albo zmienić powiązane rekordy (lista z linkami jest klikalna),
- **baza pozwoli, ale powiązane rekordy zostaną puste** — dostaniesz ostrzeżenie
  i przycisk *„Usuń mimo to”*. Możesz też kliknąć *„Anuluj — zostaw rekord”*.

Dzięki temu w bazie nie zostają rekordy bez opisu.

### Powiązane rekordy

Otwierasz rekord (ikonka 👁) i w kolumnie bocznej widzisz sekcję **Powiązane rekordy**.
Kliknięcie w liczbę albo nazwę tabeli otwiera listę **już przefiltrowaną** do tego rekordu
(np. wszystkie wymiany danej drukarki) — nie musisz niczego wpisywać w wyszukiwarce.

---

## Materiały do zamówienia

Na pulpicie jest lista **Materiałów do zamówienia** (stan równy zero lub ujemny).

Czasem materiał ma wartość archiwalną — nie chce go zamawiać, mimo że stan jest ujemny.
Przy takim materiale (i na jego karcie) jest przycisk **„⛔ Pomiń przy zamówieniu”**.
Materiał znika wtedy z listy i z licznika na pulpicie, ale zostaje w bazie i widać go
na liście *Pominięte przy zamówieniu*. Przycisk *„×”* przy takim materialu przywraca go
na listę.

## Wykres na pulpicie

Wykres **Wymiany w ostatnich 12 miesiącach** pokazuje:

- liczbę wymian **nad każdym słupkiem**,
- skalę po lewej stronie (0, …, maksimum) i linie siatki,
- miesiąc z największą liczbą wymian w innym kolorze,
- podsumowanie: łącznie, średnio na miesiąc, najwięcej i zmiana ostatniego miesiąca,
- rozwijany **Podgląd miesięcy w liczbach** (miesiąc, liczba, zmiana).

**Kliknięcie słupka** otwiera listę wymian tylko z tego miesiąca.

## Pozostałe tabele

Zakładka **Tabele** w menu bocznym daje dostęp do każdej tabeli bazy
(modele, drukarki, asortyment, pracownicy, wymiany).

W każdej liście dostępne są:

- **wyszukiwarka** w treści pól
- **filtry kolumn** (przycisk „Filtry kolumn”) — w tym zakres dat od/do
- **sortowanie** po kliknięciu w nagłówek kolumny
- **paginacja** i wybór liczby rekordów na stronie (25 / 50 / 100 / 200)
- **⬇ CSV** — eksport aktualnego widoku do pliku CSV (Excel)

---

## Urządzenia i karta drukarki

Zakładka **Urządzenia** to rejestr drukarek. Kliknij lokalizację, aby otworzyć
**kartę urządzenia**: dane, statystyki, materiały pasujące do modelu oraz pełną
historię wymian z filtrem po pracowniku.

### Zmiana modelu urządzenia

Historia wymian jest przypisana do **urządzenia**, nie do modelu — sama zmiana modelu
nie kasuje żadnego wpisu w historii.

Przy edycji urządzenia (✎ Edytuj urządzenie) pod polem **Model** pojawia się checkbox:

> ☐ Przepisz „Model” także w 14 wymianach tego urządzenia

- **odznaczone** — model urządzenia się zmienia, a stare wymiany zachowują model,
  który obowiązywał w chwili ich wykonania;
- **zaznaczone** — model zostanie przepisany również we wszystkich wymianach tego
  urządzenia. Przydatne, gdy zmiana modelu to korekta pomyłki we wpisie.

Po zapisaniu panel pisze, ile wymian zostało zaktualizowanych.

---

## Skróty klawiszowe

| Klawisz | Działanie |
|---|---|
| `/` | przejdź do wyszukiwarki |
| `Esc` | zamknij kalendarz |

---

## Gdy coś nie działa

- **„Brak połączenia z bazą danych”** — problem z konfiguracją serwera, zgłoś administratorowi
- **„Nie znaleziono strony"** — adres jest niepoprawny; wejdź na główny adres panelu
- **„Hasło administratora nie zostało jeszcze ustawione"** — wejdź ponownie na adres
  panelu; powinien pojawić się ekran ustawiania hasła
- **„Nie udało się zapisać hasła"** — serwer nie pozwala zapisywać plików w katalogu
  `config/`; zgłoś administratorowi
- **Brak polskich znaków** — zgłoś administratorowi (naprawa jest jednym poleceniem)
