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