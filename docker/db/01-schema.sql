-- MotoBid: schemat bazy PostgreSQL (T-012).
-- Zrodlo prawdy: ERD (docs/erd.md). Plik jest wykonywany raz, przy inicjalizacji pustej bazy,
-- przez /docker-entrypoint-initdb.d (pliki *.sql w kolejnosci alfabetycznej).
-- Kwoty: BIGINT w groszach (4550000 = 45 500 zl). Czas: TIMESTAMPTZ.
-- Kolejnosc: brands -> users -> companies -> auctions -> auction_images -> bids
--            -> FK auctions.winning_bid_id (ALTER) -> notifications -> indeksy -> slownik marek.
-- Dane demo: 02-demo-data.sql (T-014). Widok auction_summary: T-013.

BEGIN;

-- 1. Slownik marek (filtr ?brand=<slug>, <select> w formularzu aukcji)
CREATE TABLE brands (
    id    SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name  VARCHAR(40) NOT NULL,
    slug  VARCHAR(40) NOT NULL,
    CONSTRAINT brands_name_uq  UNIQUE (name),
    CONSTRAINT brands_slug_uq  UNIQUE (slug),
    CONSTRAINT brands_slug_chk CHECK (slug ~ '^[a-z0-9-]{1,40}$')
);

-- 2. Konta wszystkich rol (BUYER, DEALER, ADMIN)
CREATE TABLE users (
    id             INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email          VARCHAR(254) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    name           VARCHAR(60)  NOT NULL,
    role           VARCHAR(10)  NOT NULL DEFAULT 'BUYER',
    locale         CHAR(2)      NOT NULL DEFAULT 'pl',
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT users_role_chk   CHECK (role IN ('BUYER', 'DEALER', 'ADMIN')),
    CONSTRAINT users_locale_chk CHECK (locale IN ('pl', 'en')),
    CONSTRAINT users_name_chk   CHECK (char_length(btrim(name)) BETWEEN 2 AND 60),
    CONSTRAINT users_email_chk  CHECK (email = lower(email) AND position('@' IN email) > 1)
);
-- unikalnosc e-maila bez wzgledu na wielkosc liter (SQLSTATE 23505 -> 409)
CREATE UNIQUE INDEX users_email_lower_uq ON users (LOWER(email));

-- 3. Profil firmy: relacja 1:1 z kontem roli DEALER (companies.user_id UNIQUE)
CREATE TABLE companies (
    id           INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id      INTEGER      NOT NULL,
    name         VARCHAR(120) NOT NULL,
    city         VARCHAR(80)  NOT NULL,
    phone        VARCHAR(20)  NULL,
    status       VARCHAR(10)  NOT NULL DEFAULT 'pending',
    reviewed_by  INTEGER      NULL,
    reviewed_at  TIMESTAMPTZ  NULL,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT companies_user_uq      UNIQUE (user_id),
    CONSTRAINT companies_user_fk      FOREIGN KEY (user_id)     REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT companies_reviewer_fk  FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT companies_status_chk   CHECK (status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT companies_reviewed_chk CHECK ((status = 'pending') = (reviewed_at IS NULL)),
    CONSTRAINT companies_name_chk     CHECK (char_length(btrim(name)) BETWEEN 2 AND 120),
    CONSTRAINT companies_city_chk     CHECK (char_length(btrim(city)) BETWEEN 2 AND 80),
    CONSTRAINT companies_phone_chk    CHECK (phone IS NULL OR phone ~ '^\+?[0-9 ]{9,15}$')
);
CREATE INDEX companies_status_idx ON companies (status, created_at);

COMMIT;
