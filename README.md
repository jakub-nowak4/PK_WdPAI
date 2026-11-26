# PK_WdPAI

Lokalne środowisko developmentowe aplikacji PHP uruchamiane z użyciem Docker Compose.

## Funkcje

| Obszar | Co robi |
|---|---|
| Strona startowa | Krótki opis funkcji |

## Tech stack

| Warstwa | Technologie |
|---|---|
| Backend | PHP 8.3 |
| Serwer HTTP | Nginx |
| Baza danych | PostgreSQL 18 |
| Konteneryzacja | Docker Compose |

## Szybki start

```bash
git clone https://github.com/jakub-nowak4/PK_WdPAI.git
cd PK_WdPAI
cp .env.example .env
docker compose up --build -d
docker compose exec php composer install
```

Aplikacja: http://localhost:8080

Katalog `vendor/` nie jest w repozytorium, więc na czystym klonie `composer install` jest
potrzebny do wygenerowania autoloadera.

Kod projektu jest montowany jako bind mount, dlatego zmiany lokalnych plików są
widoczne w kontenerach bez przebudowy obrazów. Dane PostgreSQL przechowuje named volume
`<projekt>_app` (np. `pk_wdpai_app` dla katalogu `PK_WdPAI`), więc nie giną po usunięciu kontenera.

Nazwy wolumenu i sieci nie są już sztywne (`name: app`), więc Compose nadaje im prefiks nazwy
projektu (katalogu lub opcji `-p`). Po tej zmianie stary wolumen `app` przestaje być używany,
a nowy startuje pusty, więc wykonają się `01-schema.sql` i `02-demo-data.sql`. Stary wolumen
można usunąć ręcznie (`docker volume rm app`) po sprawdzeniu, że nie jest już potrzebny.

## Konta demo

Schemat (`docker/db/01-schema.sql`) i dane demo (`docker/db/02-demo-data.sql`) ładują się
automatycznie przy pierwszym starcie pustego wolumenu bazy. Dane są fikcyjne (adresy `@example.com`).
Aby wczytać je od nowa: `docker compose down -v`, potem `docker compose up --build -d`.

| E-mail | Rola | Hasło | Uwagi |
|---|---|---|---|
| `admin@example.com` | ADMIN | `Admin123!` | zatwierdza konta firm |
| `firma1@example.com` | DEALER (firma) | `Firma123!` | firma zatwierdzona, właściciel wszystkich aukcji demo |
| `firma2@example.com` | DEALER (firma) | `Firma123!` | konto czeka na zatwierdzenie |
| `kupujacy1@example.com` | BUYER | `Kupuj123!` | język PL |
| `kupujacy2@example.com` | BUYER | `Kupuj123!` | język PL |
| `kupujacy3@example.com` | BUYER | `Kupuj123!` | język EN |

Hasła są zapisane w bazie wyłącznie jako hashe bcrypt. Dane demo zawierają 10 aukcji: 2 zaplanowane,
5 aktywnych (w tym jedna bez ofert), 3 zakończone (ze zwycięzcą, z przedłużeniem anty-snipingiem
i bez ofert) oraz przykładowe powiadomienia. Czasy są liczone względem momentu importu, więc aukcje
aktywne kończą się po kilku dniach od pierwszego startu bazy.

Logowanie nie jest jeszcze zaimplementowane: ekrany są na razie makietami z danymi demo
w kodzie, a konta powyżej istnieją tylko w bazie.

## CRON: zamykanie aukcji

Aukcja po czasie musi zostać zamknięta, a zwycięzca ustalony. Robi to skrypt `bin/close-auctions.php`
(klasa `App\Service\AuctionCloser`), uruchamiany co minutę przez usługę `cron` z `docker-compose.yaml`.
Harmonogram jest w `docker/cron/crontab`:

```text
* * * * * cd /app && /usr/local/bin/php bin/close-auctions.php >> /proc/1/fd/1 2>&1
```

Co robi jeden przebieg:

1. Wybiera aukcje, które nie są zakończone i mają `ends_at <= now()` (czas bazy, nie przeglądarki).
2. Każdą zamyka w osobnej transakcji: `SELECT ... FOR UPDATE` na aukcji, ponowne sprawdzenie czasu pod
   blokadą (oferta w ostatnich 30 s mogła ją przedłużyć), `status = 'ended'`, `closed_at` i `winning_bid_id`
   (najwyższa oferta; `NULL`, gdy ofert nie było).
3. W tej samej transakcji tworzy powiadomienia: `won` dla zwycięzcy, `auction_ended` dla pozostałych
   licytujących i dla firmy-właściciela.
4. Po zatwierdzeniu transakcji wysyła zwycięzcy e-mail „Wygrałeś" (Mailpit). Błąd poczty nie cofa wyniku:
   w logu jest linia `mail=failed type=won ...`.
5. Aktywuje aukcje zaplanowane, których `starts_at` już minął (inaczej nie przyjmą ofert).

Skrypt jest idempotentny: zakończone aukcje nie są wybierane, powiadomienia powstają przez
`INSERT ... ON CONFLICT DO NOTHING`, a e-mail idzie tylko wtedy, gdy powiadomienie `won` powstało teraz.
Dwa równoległe przebiegi nie zdublują wyniku (blokada wiersza). Błąd jednej aukcji nie blokuje pozostałych;
kod wyjścia to 0 (sukces), 1 (błąd przy części aukcji, spróbuje następny przebieg) albo 2 (błąd krytyczny,
np. baza niedostępna).

Log (jedna linia na zdarzenie, czas ISO 8601) trafia do logów kontenera:

```bash
docker compose logs -f cron
# [2026-11-26T15:01:00+01:00] INFO auction=closed id=8 winnerId=6 winningBidId=11 finalPrice=7250000
# [2026-11-26T15:01:00+01:00] INFO run=done closed=1 withWinner=1 skipped=0 failed=0 activated=0 mailsSent=1 mailsFailed=0
```

Jak sprawdzić ręcznie (zamiast czekać na koniec aukcji demo, przesuwamy ją w przeszłość; stos musi działać):

```bash
# 1. Przesuń aukcję "Mercedes-Benz C 220 d AMG Line" o 2 dni wstecz (start, koniec i koniec pierwotny razem).
docker compose exec db psql -U app -d app -c "UPDATE auctions SET starts_at = starts_at - INTERVAL '2 days', original_ends_at = original_ends_at - INTERVAL '2 days', ends_at = ends_at - INTERVAL '2 days' WHERE title = 'Mercedes-Benz C 220 d AMG Line';"
# 2. Uruchom skrypt ręcznie (albo poczekaj minutę na cron).
docker compose exec php php bin/close-auctions.php
# 3. Wynik: status ended, zwycięzca i powiadomienia.
docker compose exec db psql -U app -d app -c "SELECT id, status, closed_at, winning_bid_id FROM auctions WHERE title LIKE 'Mercedes%';"
docker compose exec db psql -U app -d app -c "SELECT u.email, n.type FROM notifications n JOIN users u ON u.id = n.user_id JOIN auctions a ON a.id = n.auction_id WHERE a.title LIKE 'Mercedes%' ORDER BY n.id;"
# 4. Idempotencja: drugie uruchomienie nic nie zmienia (closed=0), a w Mailpit (http://localhost:8025) jest jeden e-mail.
docker compose exec php php bin/close-auctions.php
```

Oczekiwany wynik: zwycięzca `kupujacy3@example.com` (powiadomienie `won`, e-mail w Mailpit), `kupujacy2@example.com`
i `firma1@example.com` dostają `auction_ended`. Zaplanowaną aukcję (np. „BMW 320d Touring xDrive") aktywujesz, ustawiając
`starts_at = now() - INTERVAL '1 hour'` i uruchamiając skrypt (`activated=1`). Cron na hoście bez usługi `cron` działa tak samo:
`* * * * * cd /ścieżka/do/projektu && docker compose exec -T php php bin/close-auctions.php`.

## Struktura projektu

- `public/` - front controller (`index.php`), widoki, CSS i JS.
- `src/` - router, kontrolery i serwisy (namespace `App\…`).
- `lang/` - teksty interfejsu PL i EN.
- `docker/` - Dockerfile, konfiguracja Nginx i skrypty inicjalizacyjne bazy.
- `.env` - lokalne wartości konfiguracyjne; plik nie powinien być commitowany.
- `.env.example` - bezpieczny szablon konfiguracji.

## Zasady developmentu

- Branch dla każdej funkcji.
- Małe, logiczne commity.
- Sekrety wyłącznie w .env.

## Autorzy

- Jakub Nowak