# AGENTS.md

Ten plik jest przewodnikiem dla agentow AI pracujacych w tym repozytorium. Przed wprowadzeniem zmian przeczytaj go razem z `README.md` i sprawdz aktualny stan kodu. Nie zakladaj, ze opisana funkcja istnieje, dopoki nie potwierdzisz tego w plikach projektu.

## Cel projektu

To edukacyjna aplikacja webowa napisana w czystym PHP 8.3. Korzysta z lekkiej, samodzielnie zaimplementowanej architektury MVC, bez frameworka PHP. Aplikacja dziala w kontenerach Nginx i PHP-FPM uruchamianych przez Docker Compose.

Docelowo aplikacja to MotoBid — platforma aukcji samochodow na zywo. Uzytkownicy dziela sie na trzy role: kupujacy (biora udzial w aukcjach i licytuja), firma (wystawia pojazdy i prowadzi aukcje) oraz administrator (zarzadza uzytkownikami i moderuje serwis).

Aktualnie aplikacja obsluguje:

- konta z bazy danych: rejestracje kupujacego (`GET|POST /register`) i firmy (`GET|POST /register/company`, konto i firma w jednej transakcji), logowanie (`GET|POST /login`) i wylogowanie (`POST /logout`) przez `App\Service\AuthService`; w sesji tylko `userId` i `role`, token CSRF na kazdym POST, komunikaty flash po przekierowaniu,
- ekran „konto czeka na zatwierdzenie" (`/account/pending`) ze statusem firmy z bazy (zatwierdzona firma dostaje 303 na `/company`); uzytkownika w naglowku layoutu dostarcza `App\Service\CurrentUserProvider` (z bazy, tylko dla zalogowanego),
- e-mail powitalny po rejestracji (`App\Service\MailService`: wlasny klient SMTP, lokalnie Mailpit; tresc w `WelcomeMailService`); blad poczty nie cofa konta, tylko flash `info` i linia `mail=failed` w logu,
- panel administratora z bazy (`/admin?status=`, `POST /admin/companies/{id}/approve|reject` przez `CompanyReviewService`, decyzja w transakcji, 409 gdy firma juz rozpatrzona),
- strone glowna `/` i liste aukcji `/auctions` z bazy (`AuctionListService`: query `q`, `brand`, `priceMax`, `sort`, `page`, zly format 400) oraz szczegoly aukcji `/auctions/{id}` z bazy (`AuctionDetailsService`, brak aukcji 404); szczegoly przyjmuja jeszcze makietowy `?state=` (podglad ekranow 04-07, `public/js/bid-preview.js`; do usuniecia),
- licytacje na zywo przez Fetch API: `GET /api/auctions/{id}/state` (polling, `AuctionStateService`; router zwalnia blokade sesji przed polaczeniem z baza) i `POST /api/auctions/{id}/bids` (`BidApiController` -> `BidService`: transakcja z `SELECT ... FOR UPDATE`, czas z bazy, anti-sniping 30 s, CSRF w naglowku `X-CSRF-Token`, bledy regul jako `BidException` -> JSON 403/404/409/422, 422 z fraza `Unprocessable Content`); modul JS `public/js/live-bidding.js` + `public/js/bidding/`; przebity dostaje powiadomienie w bazie (w tej samej transakcji) i e-mail (`OutbidNotifier`, wysylany po oddaniu odpowiedzi przez `fastcgi_finish_request()`),
- „Moje oferty" (`/my-bids?status=`, `MyBidsService`) z bazy,
- aukcje wygrana (`/auctions/{id}/won`, `WonAuctionService::forWinner()`): 200 tylko dla zwyciezcy aukcji zamknietej przez CRON; brak aukcji albo aukcja niezamknieta 404, kazdy inny zalogowany (takze przy aukcji bez ofert) 403 `not_winner`,
- powiadomienia (`/notifications`, `NotificationService`: 50 najnowszych) i oznaczanie wszystkich jako przeczytane (`POST /notifications/read`, CSRF, flash),
- panel firmy z bazy (`/company`, `CompanyPanelService`), dodawanie aukcji ze zdjeciami (`GET /company/auctions/new`, `POST /company/auctions`: `AuctionFormValidator`, `AuctionCreationService`, `UploadService`, aukcja i zdjecia w jednej transakcji, bledy 422 z zachowanymi polami) oraz usuwanie aukcji bez ofert (`GET|POST /company/auctions/{id}/delete`: `AuctionDeletionService`, aukcja z ofertami 403 juz przy GET, pliki zdjec kasowane po `COMMIT`); trasy firmy wymagaja zatwierdzonej firmy (pending/rejected: 403 z ekranem „konto czeka"),
- `UploadService` (kontrola kodu bledu PHP, rozmiaru, MIME z `finfo`, rozszerzenia zgodnego z MIME, `getimagesize()`, losowa nazwa w `public/uploads/auctions/`, w bazie tylko sciezka),
- CRON zamykajacy aukcje (`bin/close-auctions.php` co minute w usludze `cron`, `App\Service\AuctionCloser`): zwyciezca (`winning_bid_id`), powiadomienia `won`/`auction_ended`, e-mail „Wygrales" (`WonNotifier`), aktywacja aukcji zaplanowanych, idempotentny, log jedna linia na zdarzenie (`LineLogger`); pelna lista tras w `public/index.php`,
- przelacznik jezyka PL/EN (`POST /language`, teksty w `lang/pl.php` i `lang/en.php`) oraz przelacznik motywu jasny/ciemny (bez opcji „systemowy"),
- odpowiedzi bledow 400 (zly format `{id}` lub query param, niepoprawny UTF-8, zly JSON), 401 (w HTML przekierowanie na `/login`), 403, 404 (nieznana trasa lub zasob) i 500 (nieoczekiwany wyjatek, szczegoly tylko w logu) przez `ErrorController`, dla `/api/*` w JSON,
- schemat SQL z widokiem `auction_summary` (`docker/db/01-schema.sql`) i dane demo (`docker/db/02-demo-data.sql`), ladowane przy pierwszym starcie pustego wolumenu bazy, oraz modele i repozytoria dla wszystkich tabel.

Jeszcze nie istnieje: testy jednostkowe (`tests/`, PHPUnit), konfiguracja produkcyjna i wdrozenie, zarzadzanie kontami przez Fetch API (router zna tylko GET i POST), limit prob logowania, HTTPS lokalnie, opcja motywu „systemowy". Pozostalosci makiet bez wplywu na dane: galezie `$isMock` i pliki `public/views/partials/mock-*.php` w widokach listy, powiadomien i panelu firmy (kontrolery zawsze podaja dane, wiec sie nie wykonuja) oraz `?state=` w szczegolach aukcji.

## Stack technologiczny

- PHP 8.3 z `declare(strict_types=1)`
- Composer i autoloading PSR-4
- Nginx jako serwer HTTP
- PHP-FPM do wykonywania kodu PHP (rozszerzenia: gd, pdo_pgsql, pgsql, zip, bcmath, opcache)
- PostgreSQL jako baza danych (serwis `db`); polaczenie przez `App\Database` (PDO), uzywane przez repozytoria w `src/repositories/`
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
│   ├── Exception/             # wyjatki aplikacji (App\Exception): HttpException, ValidationException, BidException, MailException
│   ├── Database.php           # polaczenie PDO z PostgreSQL
│   ├── Request.php            # niezmienny obiekt zadania (query, post, naglowki, ciasteczka, cialo JSON)
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

`public/index.php` jest jedynym punktem wejscia aplikacji. Laduje autoloader Composera, konfiguruje i uruchamia sesje, tworzy zaleznosci (`Translator`, `LanguageService`, `Database`, `CurrentUserProvider`), tworzy `MailService` i `WelcomeMailService` (polaczenie SMTP dopiero przy wysylce), rejestruje w `Routing` serwisy korzystajace z bazy (`CompanyRepository`, `UserRepository`, `AuthService`, `AuctionListService`, `AuctionDetailsService`, `AuctionStateService`, `BidService`, `OutbidNotifier`, `MyBidsService`, `CompanyReviewService`) jako leniwe fabryki (polaczenie otwiera sie dopiero przy tworzeniu kontrolera, ktory ich potrzebuje), rejestruje trasy i przekazuje zadanie (`App\Request`) do routera. Wyjatek `App\Exception\HttpException` (400, 401, 403, 404) obsluguje `ErrorController::handle()`, a kazdy inny wyjatek (`\Throwable`) jest logowany jedna linia i konczy sie odpowiedzia 500 z `ErrorController::handleServerError()` (bez bazy i bez tresci wyjatku).

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

Obecnie istnieja kontrolery (aukcja wygrana, powiadomienia i panel firmy to na razie makiety ze statycznymi danymi, ale z guardami dostepu):

- `AppController` — klasa bazowa: `render()`, `redirect()`, `addFlash()`, `currentUserId()`, `currentRole()`, `requireLogin()`, `requireRole()`, `verifyCsrf()`, `csrfToken()`, `logInUser()`, `logOutUser()`, `homePathFor()`, `redirectIfLoggedIn()`,
- `DefaultController` — `index()` (strona glowna: baner i pierwsza strona listy aukcji z `AuctionListService`),
- `AuctionController` — `index(Request)` (lista aukcji z bazy, filtry w query przez `AuctionListService`), `show(int $id, Request)` (szczegoly aukcji z `AuctionDetailsService`; makietowy `?state=` do usuniecia), `won(int $id)` (makieta aukcji wygranej, tylko zalogowany),
- `BidApiController` — `state(int $id)` (`GET /api/auctions/{id}/state`, publiczny, JSON z `AuctionStateService`), `placeBid(int $id, Request)` (`POST /api/auctions/{id}/bids`, kupujacy, CSRF w naglowku; `BidService` w transakcji, po odpowiedzi e-mail `OutbidNotifier`),
- `AccountController` — `myBids(Request)` (tylko kupujacy, filtr `?status=`, dane z `MyBidsService`; widok jeszcze makieta), `notifications()` (makieta, zalogowany), `pending()` (firma; status z bazy, zatwierdzona dostaje 303 na `/company`),
- `SecurityController` — `login()`, `register()`, `registerCompany()` (GET formularz, POST przez `AuthService`), `logout()` (`POST /logout`),
- `CompanyController` — `index()` (panel firmy), `create()` (dodaj aukcje), `delete(int $id)` (ekran usuwania aukcji); wszystko makiety; kazda akcja zaczyna od prywatnego `requireApprovedCompany()` (firma `pending`/`rejected`: `HttpException::companyNotApproved()`, 403 z ekranem „konto czeka"),
- `AdminController` — `index(Request)` (tylko administrator, filtr `?status=`), `approve(int $id, Request)`, `reject(int $id, Request)` (`CompanyReviewService`, CSRF, 404/409),
- `LanguageController` — `change()` (zmiana jezyka, `POST /language` z CSRF),
- `ErrorController` — `handle()` (400/401/403/404; 401 w HTML to 303 na `/login`, kod `company_not_approved` renderuje `account/pending`; dla `/api/*` JSON), `handleServerError()` (500, bez bazy).

Serwisy (`src/services/`, `App\Service`): `AuthService` (+ `RegistrationResult`), `CurrentUserProvider`, `LanguageService`, `Translator`, `MailerInterface`/`MailService`, `WelcomeMailService`, `CompanyReviewService`, `AuctionListService`, `AuctionDetailsService`, `AuctionStateService`, `BidService` (+ `BidResult`), `OutbidNotifier`, `MyBidsService`, `UploadService`. Kontrakt tras, JSON i kolejnosci sprawdzen jest w prywatnym `docs/api.md`.

### Model

Dostep do bazy jest przygotowany: `config.php` zwraca ustawienia ze zmiennych srodowiskowych (przekazywanych kontenerowi `php` w `docker-compose.yaml`), a `App\Database` (`getConnection(): PDO`) leniwie otwiera jedno polaczenie PostgreSQL (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false`). Modele (`src/models/`) i repozytoria (`src/repositories/`) istnieja dla wszystkich tabel. Z bazy korzystaja dzis konta (rejestracja, logowanie, `CurrentUserProvider` w layoucie), status firmy, panel administratora, lista i szczegoly aukcji oraz API licytacji; ekrany aukcji wygranej, powiadomien i panelu firmy nadal pokazuja dane statyczne, a widok „Moje oferty" rysuje makiete mimo danych z kontrolera. Nie udawaj, ze dane widoczne w tych widokach pochodza z bazy danych. Gdy funkcja wymaga trwalego przechowywania danych, wydziel klasy modelu/repozytorium zamiast dodawac zapytania SQL do kontrolera.

`composer.json` ma dokladne mapowania PSR-4 dla katalogow pisanych malymi literami:

```text
src/controllers/  -> App\Controller
src/models/       -> App\Model
src/repositories/ -> App\Repository
src/services/     -> App\Service
```

Przyklad: `src/models/Auction.php` -> `App\Model\Auction`, `src/repositories/AuctionRepository.php` -> `App\Repository\AuctionRepository`.

Po dodaniu lub przeniesieniu klas odswiez autoloader poleceniem `composer dump-autoload`.

`composer.lock` jest sledzony w repozytorium (od galezi `chore/T-007-composer-lock`). Zmiana `composer.json` wymaga `composer update --lock` (lub `composer require`) i commitu `composer.lock` w tym samym commicie.

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
