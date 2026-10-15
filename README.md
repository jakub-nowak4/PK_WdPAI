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
| Konteneryzacja | Docker Compose |

## Szybki start

```bash
git clone https://github.com/jakub-nowak4/PK_WdPAI.git
cd REPOZYTORIUM
cp .env-example .env
docker compose up --build -d
```

Aplikacja: http://localhost:8080

Kod projektu jest montowany jako bind mount, dlatego zmiany lokalnych plików są
widoczne w kontenerach bez przebudowy obrazów. Named volume `app` przechowuje
dane PostgreSQL, aby nie były tracone po usunięciu kontenera.

## Struktura projektu

- `public/` - kod publiczny aplikacji PHP.
- `docker/` - Dockerfile, konfiguracja Nginx i skrypty inicjalizacyjne bazy.
- `.env` - lokalne wartości konfiguracyjne; plik nie powinien być commitowany.
- `.env-example` - bezpieczny szablon konfiguracji.

## Zasady developmentu

- Branch dla każdej funkcji.
- Małe, logiczne commity.
- Sekrety wyłącznie w .env.

## Autorzy

- Jakub Nowak