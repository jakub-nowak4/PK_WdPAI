# AGENTS.md

Ten plik jest przewodnikiem dla agentow AI pracujacych w tym repozytorium. Przed wprowadzeniem zmian przeczytaj go razem z `README.md` i sprawdz aktualny stan kodu. Nie zakladaj, ze opisana funkcja istnieje, dopoki nie potwierdzisz tego w plikach projektu.

## Cel projektu

To edukacyjna aplikacja webowa napisana w czystym PHP 8.3. Korzysta z lekkiej, samodzielnie zaimplementowanej architektury MVC, bez frameworka PHP. Aplikacja dziala w kontenerach Nginx i PHP-FPM uruchamianych przez Docker Compose.

Docelowo aplikacja to MotoBid — platforma aukcji samochodow na zywo. Uzytkownicy dziela sie na trzy role: kupujacy (biora udzial w aukcjach i licytuja), firma (wystawia pojazdy i prowadzi aukcje) oraz administrator (zarzadza uzytkownikami i moderuje serwis).

Aktualnie aplikacja obsluguje:

- strone glowna pod `/` oraz strony-makiety ekranow MotoBid: lista i szczegoly aukcji (`/auctions`, `/auctions/{id}`), aukcja wygrana, moje oferty, powiadomienia, logowanie, rejestracja kupujacego i firmy, konto czeka na zatwierdzenie, panel firmy i panel administratora (pelna lista tras w `public/index.php`); dane w widokach sa statyczne (makieta, uzytkownik demo z `App\Service\DemoUser`), nie z bazy,
- przelacznik jezyka PL/EN (`POST /language`, teksty w `lang/pl.php` i `lang/en.php`) oraz przelacznik motywu jasny/ciemny,
- odpowiedzi bledow 400 (zly format `{id}` lub query param) i 404 (nieznana trasa) przez `ErrorController`,
- schemat SQL z widokiem `auction_summary` (`docker/db/01-schema.sql`) i dane demo (`docker/db/02-demo-data.sql`), ladowane przy pierwszym starcie pustego wolumenu bazy.

## Stack technologiczny

- PHP 8.3 z `declare(strict_types=1)`
- Composer i autoloading PSR-4
- Nginx jako serwer HTTP
- PHP-FPM do wykonywania kodu PHP (rozszerzenia: gd, pdo_pgsql, pgsql, zip, bcmath, opcache)
- PostgreSQL jako baza danych (serwis `db`); polaczenie przez `App\Database` (PDO), jeszcze nieuzywane przez kontrolery
- Docker oraz Docker Compose
- HTML bez frameworka frontendowego; CSS w `public/css/style.css` (tokeny motywu, Flexbox/Grid, mobile first); motyw jasny/ciemny: `data-theme` na `<html>`, `public/js/theme.js`, klucz `theme` w localStorage

## Struktura katalogow

```text
.
├── docker/
│   ├── nginx/                 # obraz i konfiguracja Nginx
│   ├── php/                   # obraz PHP-FPM i rozszerzenia PHP
│   └── db/                    # obraz PostgreSQL
├── public/                    # jedyny publicznie dostepny katalog
│   ├── index.php              # front controller i definicje tras
│   └── views/                 # szablony HTML renderowane przez kontrolery
├── src/
│   ├── controllers/           # kontrolery aplikacji (App\Controller)
│   ├── models/                # encje i value objecty (App\Model)
│   ├── repositories/          # zapytania SQL (App\Repository)
│   ├── services/              # logika aplikacji (App\Service)
│   ├── Database.php           # polaczenie PDO z PostgreSQL
│   └── Routing.php            # rejestracja i obsluga tras
├── config.php                 # konfiguracja ze zmiennych srodowiskowych
├── composer.json              # konfiguracja autoloadingu
└── docker-compose.yaml        # uslugi `server`, `php`, `db`, `mailpit`, `pgadmin-wdpai`
```

Nie umieszczaj logiki aplikacji ani danych wrazliwych w `public/`. Ten katalog jest document rootem Nginx i sluzy tylko jako punkt wejscia oraz miejsce na widoki i zasoby publiczne.

## Architektura MVC

Projekt stosuje uproszczony przeplyw MVC:

```text
przegladarka
    -> Nginx
    -> public/index.php
    -> Routing
    -> Controller
    -> Model / repozytorium / serwis
    -> View
    -> odpowiedz HTML
```

### Front Controller

`public/index.php` jest jedynym punktem wejscia aplikacji. Laduje autoloader Composera, uruchamia sesje, tworzy zaleznosci (`Translator`, `LanguageService`), rejestruje trasy i przekazuje zadanie (`App\Request`) do routera. Wyjatek `App\Exception\HttpException` (400, 403, 404) obsluguje `ErrorController`.

Nowe trasy dodawaj w tym pliku w postaci:

```php
$routing->get('/example', ExampleController::class, 'index');
$routing->post('/example', ExampleController::class, 'store');
```

### Router

`src/Routing.php` (namespace `App`) laczy metode HTTP i sciezke URL z publiczna metoda kontrolera. Parametr zapisany jako `{name}` dopasowuje jeden segment sciezki i trafia do akcji kontrolera przez nazwany argument. Wartosc `{id}` musi byc dodatnia liczba calkowita bez zer wiodacych (najwyzej 2147483647) i jest konwertowana na `int`; zly format daje 400, brak pasujacej trasy 404. Akcja z parametrem typu `App\Request` dostaje biezace zadanie.

Przyklad: `/auctions/{id}` wywoluje `AuctionController::show(int $id, Request $request)`.

Router obsluguje obecnie metody GET i POST. Nie dodawaj logiki biznesowej do routera.

### Controller

Kontrolery znajduja sie w `src/controllers/`, uzywaja przestrzeni nazw `App\Controller` i dziedzicza po `AppController`. Ich zadaniem jest:

- odebranie i walidacja danych wejsciowych,
- wywolanie modelu lub serwisu,
- przygotowanie danych dla widoku,
- wybranie szablonu przez `$this->render()`.

Kontroler powinien byc cienki. Nie umieszczaj w nim rozbudowanych zapytan do bazy, HTML ani stylow.

Obecnie istnieja kontrolery (ekrany aukcji, konta, firmy i administratora to na razie makiety):

- `DefaultController` — `index()` (strona glowna),
- `AuctionController` — `index()` (lista aukcji z filtrami w query), `show(int $id)` (szczegoly aukcji), `won(int $id)` (aukcja wygrana),
- `AccountController` — `myBids()`, `notifications()`, `pending()` (konto czeka na zatwierdzenie),
- `SecurityController` — `login()`, `register()`, `registerCompany()`,
- `CompanyController` — `index()` (panel firmy), `create()` (dodaj aukcje), `delete(int $id)` (usun aukcje),
- `AdminController` — `index()` (panel administratora),
- `LanguageController` — `change()` (zmiana jezyka, `POST /language`),
- `ErrorController` — `handle()` (strony bledow 400/403/404, dla `/api/*` JSON).

### Model

Dostep do bazy jest przygotowany: `config.php` zwraca ustawienia ze zmiennych srodowiskowych (przekazywanych kontenerowi `php` w `docker-compose.yaml`), a `App\Database` (`getConnection(): PDO`) leniwie otwiera jedno polaczenie PostgreSQL (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false`). Modele i repozytoria jeszcze nie istnieja, wiec widoki nadal nie czytaja danych z bazy. Nie udawaj, ze dane widoczne w widokach pochodza z bazy danych. Gdy funkcja wymaga trwalego przechowywania danych, wydziel klasy modelu/repozytorium zamiast dodawac zapytania SQL do kontrolera.

`composer.json` ma dokladne mapowania PSR-4 dla katalogow pisanych malymi literami:

```text
src/controllers/  -> App\Controller
src/models/       -> App\Model
src/repositories/ -> App\Repository
src/services/     -> App\Service
```

Przyklad: `src/models/Auction.php` -> `App\Model\Auction`, `src/repositories/AuctionRepository.php` -> `App\Repository\AuctionRepository`.

Po dodaniu lub przeniesieniu klas odswiez autoloader poleceniem `composer dump-autoload`.

### View

Widoki znajduja sie w `public/views/` i maja nazwy `*.html.php`. Widoki to fragmenty (sam `<main>`); wspolny dokument HTML (head, skrypt motywu, przelacznik, `<?= $content ?>`) jest w `public/views/layout.html.php`, a `AppController::render()` wstawia widok do layoutu. `AppController::render()` przekazuje do nich dane jako zmienne przy uzyciu `extract(..., EXTR_SKIP)`.

Widoki odpowiadaja za prezentacje. Dozwolone sa w nich proste instrukcje warunkowe i petle, ale nie logika biznesowa, dostep do bazy ani obsluga routingu.

Kazda wartosc pochodzaca od uzytkownika lub z bazy musi byc escapowana w kontekscie HTML:

```php
<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>
```

Style znajduja sie w `public/css/style.css` i sa podlinkowane w kazdym widoku (`<link rel="stylesheet" href="/css/style.css">`). Kolory, odstepy i promienie definiuj jako zmienne CSS w `:root` (ciemny motyw: `html[data-theme="dark"]`), a elementy stylizuj klasami.

## Konwencje implementacji

- Zachowuj `declare(strict_types=1)` we wszystkich nowych plikach PHP.
- Uzywaj deklaracji typow parametrow, zwracanych wartosci i wlasciwosci.
- Nazwy klas zapisuj w `PascalCase`, a metod i zmiennych w `camelCase`.
- Nazwy techniczne w kodzie zapisuj po angielsku; tekst interfejsu moze byc po polsku.
- Jeden kontroler powinien obslugiwac jeden spojny obszar aplikacji.
- Dane przekazuj do widoku jawnie w tablicy drugiego argumentu `render()`.
- Nie tworz globalnego stanu i nie uzywaj `$_GET`, `$_POST` ani `$_SERVER` poza warstwa wejscia, jezeli dane mozna przekazac jako argument.
- Nie dodawaj frameworka ani duzej biblioteki bez wyraznej potrzeby i zgody uzytkownika.
- Sekrety umieszczaj w `.env` (wzor w `.env.example`); nigdy nie commituj hasel, tokenow ani danych dostepowych.
- Nie edytuj `vendor/` recznie.
- Zachowuj zakres zadania i nie zmieniaj niezwiazanych plikow.

## Dodawanie nowej funkcji

Typowy porzadek prac:

1. Dodaj trase w `public/index.php`.
2. Dodaj albo rozszerz kontroler w `src/controllers/`.
3. Dodaj model, repozytorium lub serwis, jesli funkcja zawiera logike biznesowa albo dostep do danych.
4. Dodaj widok w `public/views/` i przekaz do niego tylko potrzebne dane.
5. Dodaj lub zmien style, gdy warstwa CSS zostanie wprowadzona.
6. Sprawdz poprawna trase, dane wejsciowe, HTML oraz zachowanie dla bledow i 404.

## Uruchamianie projektu

```bash
docker compose up --build -d
```

Aplikacja jest domyslnie dostepna pod `http://localhost:8080`. Port mozna zmienic przez zmienna `APP_PORT` (patrz `.env.example`).

Przydatne polecenia:

```bash
docker compose ps
docker compose logs -f
docker compose exec php php -l src/Routing.php
docker compose exec php composer dump-autoload
docker compose down
```

Jesli katalog `vendor/` nie istnieje, zainstaluj zaleznosci/autoloader poleceniem:

```bash
docker compose exec php composer install
```

Warto wiedziec:

- `vendor/` nie jest w repozytorium, wiec na czystym klonie po `docker compose up --build -d` trzeba raz wykonac `docker compose exec php composer install`.
- Nazwy wolumenu bazy i sieci nie sa sztywne: Compose dodaje prefiks nazwy projektu (katalog albo opcja `-p`), np. `<projekt>_app` i `<projekt>_app-network`. Dzieki temu `-p` izoluje zasoby kilku kopii projektu. Stary wolumen o nazwie `app` nie jest juz uzywany; nowy startuje pusty i wykonuje `01-schema.sql` oraz `02-demo-data.sql`.
- Nginx wykonuje PHP wylacznie przez `/index.php`; zadanie o inny plik `.php` (widok, partial) dostaje 404. `display_errors` jest wylaczone, a bledy PHP trafiaja do logow kontenera: `docker compose logs php`.

## Weryfikacja zmian

W projekcie nie ma obecnie skonfigurowanego zestawu testow automatycznych ani narzedzia do formatowania. Po zmianie:

- uruchom `php -l` dla kazdego zmienionego pliku PHP,
- uruchom `composer dump-autoload`, jezeli zmieniales klasy lub namespace,
- uruchom kontenery i sprawdz zmienione endpointy,
- sprawdz przypadek poprawny, bledne dane oraz nieistniejaca trase,
- upewnij sie, ze logi `docker compose logs` nie zawieraja nowych bledow,
- nie twierdz, ze testy przeszly, jezeli ich faktycznie nie uruchomiono.

## Zasady dla agentow AI

- Najpierw przeczytaj pliki bezposrednio zwiazane z zadaniem i potwierdz aktualna implementacje.
- Przed edycja sprawdz `git status`; zachowaj zmiany uzytkownika i nie cofaj ich.
- Wprowadzaj najmniejsza spojna zmiane realizujaca wymaganie.
- Utrzymuj rozdzial odpowiedzialnosci MVC.
- Nie rozszerzaj zakresu zadania o refaktoryzacje, migracje lub nowe zaleznosci bez uzasadnienia.
- Jesli wymaganie jest niejednoznaczne, wybierz rozwiazanie zgodne z istniejacymi wzorcami; pytaj tylko wtedy, gdy wybor istotnie zmieni zachowanie aplikacji.
- Po zakonczeniu opisz zmienione pliki oraz wykonana weryfikacje.
