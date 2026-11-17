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

-- 4. Aukcje (aktualnej ceny nie przechowujemy: MAX(bids.amount) albo starting_price)
CREATE TABLE auctions (
    id                INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    company_id        INTEGER      NOT NULL,
    brand_id          SMALLINT     NOT NULL,
    title             VARCHAR(120) NOT NULL,
    model             VARCHAR(60)  NOT NULL,
    year              SMALLINT     NOT NULL,
    mileage_km        INTEGER      NOT NULL,
    fuel              VARCHAR(10)  NOT NULL,
    transmission      VARCHAR(10)  NOT NULL,
    origin_country    CHAR(2)      NOT NULL,
    vin               CHAR(17)     NULL,
    description       TEXT         NOT NULL DEFAULT '',
    starting_price    BIGINT       NOT NULL,
    min_increment     BIGINT       NOT NULL,
    starts_at         TIMESTAMPTZ  NOT NULL DEFAULT now(),
    ends_at           TIMESTAMPTZ  NOT NULL,
    original_ends_at  TIMESTAMPTZ  NOT NULL,
    status            VARCHAR(10)  NOT NULL DEFAULT 'scheduled',
    winning_bid_id    INTEGER      NULL,
    closed_at         TIMESTAMPTZ  NULL,
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT auctions_company_fk       FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE RESTRICT,
    CONSTRAINT auctions_brand_fk         FOREIGN KEY (brand_id)   REFERENCES brands (id)    ON DELETE RESTRICT,
    CONSTRAINT auctions_title_chk        CHECK (char_length(btrim(title)) BETWEEN 5 AND 120),
    CONSTRAINT auctions_model_chk        CHECK (char_length(btrim(model)) BETWEEN 1 AND 60),
    CONSTRAINT auctions_year_chk         CHECK (year BETWEEN 1950 AND 2100),
    CONSTRAINT auctions_mileage_chk      CHECK (mileage_km BETWEEN 0 AND 2000000),
    CONSTRAINT auctions_fuel_chk         CHECK (fuel IN ('petrol', 'diesel', 'hybrid', 'electric', 'lpg')),
    CONSTRAINT auctions_transmission_chk CHECK (transmission IN ('manual', 'automatic')),
    CONSTRAINT auctions_country_chk      CHECK (origin_country IN ('US','CA','DE','FR','NL','BE','IT','ES','SE','CH','GB','JP','KR')),
    CONSTRAINT auctions_vin_chk          CHECK (vin IS NULL OR vin ~ '^[A-HJ-NPR-Z0-9]{17}$'),
    CONSTRAINT auctions_description_chk  CHECK (char_length(description) <= 5000),
    CONSTRAINT auctions_price_chk        CHECK (starting_price BETWEEN 10000 AND 1000000000),
    CONSTRAINT auctions_increment_chk    CHECK (min_increment IN (10000, 20000, 50000, 100000, 200000)),
    CONSTRAINT auctions_duration_chk     CHECK (original_ends_at - starts_at BETWEEN INTERVAL '1 hour' AND INTERVAL '30 days'),
    CONSTRAINT auctions_extension_chk    CHECK (ends_at >= original_ends_at),
    CONSTRAINT auctions_status_chk       CHECK (status IN ('scheduled', 'active', 'ended')),
    CONSTRAINT auctions_closed_chk       CHECK ((status = 'ended') = (closed_at IS NOT NULL)),
    CONSTRAINT auctions_winner_chk       CHECK (winning_bid_id IS NULL OR status = 'ended')
);

-- 5. Zdjecia aut: w bazie tylko sciezka (kryterium 34), relacja 1:N z auctions
CREATE TABLE auction_images (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    auction_id  INTEGER      NOT NULL,
    path        VARCHAR(255) NOT NULL,
    position    SMALLINT     NOT NULL,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT auction_images_auction_fk   FOREIGN KEY (auction_id) REFERENCES auctions (id) ON DELETE CASCADE,
    CONSTRAINT auction_images_path_uq      UNIQUE (path),
    CONSTRAINT auction_images_position_uq  UNIQUE (auction_id, position),
    CONSTRAINT auction_images_position_chk CHECK (position BETWEEN 1 AND 8),
    CONSTRAINT auction_images_path_chk     CHECK (path ~ '^/uploads/auctions/[0-9a-f]{32}\.(jpg|png|webp)$')
);

-- 6. Oferty: encja asocjacyjna users N:M auctions (z atrybutami: kwota, czas)
--    bids.auction_id ON DELETE RESTRICT: aukcji z ofertami nie wolno usunac (regula 1)
--    created_at = clock_timestamp(), bo now() w transakcji to czas jej startu, sprzed blokady
CREATE TABLE bids (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    auction_id  INTEGER     NOT NULL,
    user_id     INTEGER     NOT NULL,
    amount      BIGINT      NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    CONSTRAINT bids_auction_fk        FOREIGN KEY (auction_id) REFERENCES auctions (id) ON DELETE RESTRICT,
    CONSTRAINT bids_user_fk           FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE RESTRICT,
    CONSTRAINT bids_amount_chk        CHECK (amount > 0 AND amount <= 100000000000),
    CONSTRAINT bids_auction_amount_uq UNIQUE (auction_id, amount),
    CONSTRAINT bids_id_auction_uq     UNIQUE (id, auction_id)
);
CREATE INDEX bids_user_auction_idx ON bids (user_id, auction_id);

-- 7. Zwycieska oferta musi nalezec do tej samej aukcji (zlozony FK).
--    Cykl auctions <-> bids, wiec FK dodajemy dopiero po utworzeniu bids.
ALTER TABLE auctions
    ADD CONSTRAINT auctions_winning_bid_fk
    FOREIGN KEY (winning_bid_id, id) REFERENCES bids (id, auction_id) ON DELETE RESTRICT;

CREATE INDEX auctions_status_ends_idx ON auctions (status, ends_at);
CREATE INDEX auctions_company_idx     ON auctions (company_id);
CREATE INDEX auctions_brand_idx       ON auctions (brand_id);

-- 8. Powiadomienia w serwisie (outbid, won, auction_ended)
CREATE TABLE notifications (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     INTEGER     NOT NULL,
    auction_id  INTEGER     NOT NULL,
    type        VARCHAR(20) NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    read_at     TIMESTAMPTZ NULL,
    CONSTRAINT notifications_user_fk    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
    CONSTRAINT notifications_auction_fk FOREIGN KEY (auction_id) REFERENCES auctions (id) ON DELETE CASCADE,
    CONSTRAINT notifications_type_chk   CHECK (type IN ('outbid', 'won', 'auction_ended'))
);
-- PostgreSQL nie indeksuje kluczy obcych automatycznie
CREATE INDEX notifications_user_idx    ON notifications (user_id, created_at DESC);
CREATE INDEX notifications_unread_idx  ON notifications (user_id) WHERE read_at IS NULL;
CREATE INDEX notifications_auction_idx ON notifications (auction_id);
-- idempotencja CRON: najwyzej jedno 'won' / 'auction_ended' na osobe i aukcje
CREATE UNIQUE INDEX notifications_once_uq ON notifications (user_id, auction_id, type)
    WHERE type IN ('won', 'auction_ended');

-- 9. Dane slownikowe (potrzebne aplikacji niezaleznie od danych demo)
INSERT INTO brands (name, slug) VALUES
    ('Alfa Romeo', 'alfa-romeo'), ('Audi', 'audi'), ('BMW', 'bmw'), ('Chevrolet', 'chevrolet'),
    ('Dodge', 'dodge'), ('Fiat', 'fiat'), ('Ford', 'ford'), ('Honda', 'honda'), ('Hyundai', 'hyundai'),
    ('Jeep', 'jeep'), ('Kia', 'kia'), ('Land Rover', 'land-rover'), ('Lexus', 'lexus'), ('Mazda', 'mazda'),
    ('Mercedes-Benz', 'mercedes-benz'), ('Mini', 'mini'), ('Nissan', 'nissan'), ('Peugeot', 'peugeot'),
    ('Porsche', 'porsche'), ('Renault', 'renault'), ('Subaru', 'subaru'), ('Tesla', 'tesla'),
    ('Toyota', 'toyota'), ('Volkswagen', 'volkswagen'), ('Volvo', 'volvo');

-- 10. Widok auction_summary: aktualna cena, liczba ofert i prowadzacy w jednym miejscu
--     (lista i szczegoly aukcji, kryterium 25). Cena nie jest przechowywana, tylko liczona.
--     Prowadzacy = autor najwyzszej oferty (kwoty w aukcji sa unikalne, wiec bez remisow).
--     min_next_bid: przy braku ofert cena wywolawcza, potem aktualna cena + krok.
CREATE VIEW auction_summary AS
SELECT
    a.id,
    a.company_id,
    c.name                                   AS company_name,
    a.brand_id,
    b.name                                   AS brand_name,
    b.slug                                   AS brand_slug,
    a.title,
    a.model,
    a.year,
    a.mileage_km,
    a.fuel,
    a.transmission,
    a.origin_country,
    a.starting_price,
    a.min_increment,
    a.starts_at,
    a.ends_at,
    a.original_ends_at,
    (a.ends_at > a.original_ends_at)         AS extended,
    a.status,
    a.winning_bid_id,
    a.closed_at,
    a.created_at,
    COALESCE(top.amount, a.starting_price)   AS current_price,
    COALESCE(cnt.bid_count, 0)               AS bid_count,
    top.user_id                              AS leader_user_id,
    CASE WHEN top.amount IS NULL
         THEN a.starting_price
         ELSE top.amount + a.min_increment
    END                                      AS min_next_bid
FROM auctions a
JOIN companies c ON c.id = a.company_id
JOIN brands    b ON b.id = a.brand_id
LEFT JOIN LATERAL (
    SELECT bd.amount, bd.user_id
    FROM bids bd
    WHERE bd.auction_id = a.id
    ORDER BY bd.amount DESC
    LIMIT 1
) top ON TRUE
LEFT JOIN LATERAL (
    SELECT COUNT(*)::INTEGER AS bid_count
    FROM bids bd
    WHERE bd.auction_id = a.id
) cnt ON TRUE;

COMMIT;
