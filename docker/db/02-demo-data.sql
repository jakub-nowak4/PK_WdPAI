-- MotoBid: dane demonstracyjne (T-014).
-- Ladowany po 01-schema.sql (pliki w /docker-entrypoint-initdb.d w kolejnosci alfabetycznej),
-- tylko przy pierwszym starcie pustego wolumenu. Calosc w jednej transakcji.
-- Dane sa fikcyjne: adresy @example.com, nazwy firm i osob wymyslone, brak prawdziwych danych osobowych.
-- Hasla: wylacznie hashe bcrypt (password_hash PHP); jawne hasla demo sa w README.md, sekcja "Konta demo".
-- Czasy sa wzgledne wobec momentu importu (now()), zeby aukcje aktywne byly aktywne tuz po starcie.
-- Kwoty w groszach (4550000 = 45 500 zl). Wiersze wskazujemy podzapytaniem po e-mailu lub tytule,
-- bez jawnych id (kolumny GENERATED ALWAYS).
-- Uwaga: w literalach INTERVAL znak dotyczy tylko jednostki za nim, dlatego kazda jednostka ma wlasny znak.

BEGIN;

-- 1. Uzytkownicy: 1 administrator, 2 konta firmowe, 3 kupujacych
--    hasla: Admin123! (admin), Firma123! (firmy), Kupuj123! (kupujacy)
INSERT INTO users (email, password_hash, name, role, locale, created_at) VALUES
    ('admin@example.com',     '$2y$10$zgCabeGpTU.kBUnrfPemxOzKzBoGFhzeoGZ9YclbAhS8BTGpzmAy2', 'Administrator MotoBid', 'ADMIN',  'pl', now() - INTERVAL '40 days'),
    ('firma1@example.com',    '$2y$10$VHPw9kbbRkttu1QkT1TYg.Phs6OjS4TzOVY3rWbtmri/BTrq2Ii5a', 'Auto Import Kraków',    'DEALER', 'pl', now() - INTERVAL '35 days'),
    ('firma2@example.com',    '$2y$10$VHPw9kbbRkttu1QkT1TYg.Phs6OjS4TzOVY3rWbtmri/BTrq2Ii5a', 'Moto Sprowadzenia',     'DEALER', 'pl', now() - INTERVAL '2 days'),
    ('kupujacy1@example.com', '$2y$10$ugbID2PCMG36UJFyU7iV3O2QCdnd1XlgZ0J4r86EfriLG/4BjNTTe', 'Anna Przykładowa',      'BUYER',  'pl', now() - INTERVAL '30 days'),
    ('kupujacy2@example.com', '$2y$10$ugbID2PCMG36UJFyU7iV3O2QCdnd1XlgZ0J4r86EfriLG/4BjNTTe', 'Piotr Demonstracyjny', 'BUYER',  'pl', now() - INTERVAL '28 days'),
    ('kupujacy3@example.com', '$2y$10$ugbID2PCMG36UJFyU7iV3O2QCdnd1XlgZ0J4r86EfriLG/4BjNTTe', 'Eve Sample',           'BUYER',  'en', now() - INTERVAL '20 days');

-- 2. Firmy: zatwierdzona (moze dodawac aukcje) i oczekujaca (ekran "konto czeka na zatwierdzenie")
INSERT INTO companies (user_id, name, city, phone, status, reviewed_by, reviewed_at, created_at)
SELECT u.id, 'Auto Import Kraków', 'Kraków', '+48 12 345 67 89', 'approved',
       (SELECT id FROM users WHERE email = 'admin@example.com'), now() - INTERVAL '34 days', now() - INTERVAL '35 days'
FROM users u WHERE u.email = 'firma1@example.com';

INSERT INTO companies (user_id, name, city, phone, status, reviewed_by, reviewed_at, created_at)
SELECT u.id, 'Moto Sprowadzenia', 'Gdańsk', '+48 58 123 45 67', 'pending', NULL, NULL, now() - INTERVAL '2 days'
FROM users u WHERE u.email = 'firma2@example.com';

-- 3. Aukcje (10), wszystkie zatwierdzonej firmy.
--    scheduled: 2 | active: 5 | ended: 3 (w tym 1 z anty-snipingiem, 1 bez ofert)
--    Aukcje zakonczone wstawiamy od razu ze status='ended' i closed_at (winning_bid_id uzupelnia krok 5).
INSERT INTO auctions (company_id, brand_id, title, model, year, mileage_km, fuel, transmission, origin_country, vin,
                      description, starting_price, min_increment, starts_at, ends_at, original_ends_at, status, closed_at, created_at)
SELECT c.id, b.id, v.title, v.model, v.year, v.mileage_km, v.fuel, v.transmission, v.origin_country, v.vin,
       v.description, v.starting_price, v.min_increment,
       now() + v.starts_off, now() + v.ends_off, now() + v.orig_ends_off, v.status,
       CASE WHEN v.status = 'ended' THEN now() + v.ends_off + INTERVAL '1 minute' END,
       now() + v.created_off
FROM (VALUES
    -- zaplanowane
    ('bmw',           'BMW 320d Touring xDrive',        '320d Touring', 2019::smallint,  96000, 'diesel',   'automatic', 'DE', 'WBAJM71000FX12345',
     'Kombi z niemieckiego salonu, serwisowany w ASO. Pakiet M Sport, adaptacyjne światła LED, hak holowniczy.',
      7500000::bigint,  50000::bigint, INTERVAL '1 day',  INTERVAL '6 days',  INTERVAL '6 days',  'scheduled', INTERVAL '-1 day'),
    ('tesla',         'Tesla Model 3 Long Range',       'Model 3',      2021::smallint,  48000, 'electric', 'automatic', 'US', '5YJ3E1EA7MF123456',
     'Wersja Long Range z napędem na cztery koła. Autopilot, szklany dach, bateria w bardzo dobrym stanie.',
     12000000::bigint, 100000::bigint, INTERVAL '2 days',  INTERVAL '9 days',  INTERVAL '9 days',  'scheduled', INTERVAL '-1 day'),
    -- aktywne
    ('audi',          'Audi A4 Avant 2.0 TDI S line',   'A4 Avant',     2018::smallint, 134000, 'diesel',   'automatic', 'DE', 'WAUZZZF48JA123456',
     'Kombi z pakietem S line, matrycowe reflektory, skórzana tapicerka. Bezwypadkowe, drugi właściciel.',
      6500000::bigint,  50000::bigint, INTERVAL '-3 days', INTERVAL '2 days',  INTERVAL '2 days',  'active',    INTERVAL '-4 days'),
    ('volkswagen',    'Volkswagen Golf VIII 1.5 TSI',   'Golf',         2020::smallint,  62000, 'petrol',   'manual',    'NL', NULL,
     'Kompaktowy hatchback z holenderskiego rynku. Cyfrowy kokpit, asystent pasa ruchu, serwisowany regularnie.',
      5500000::bigint,  20000::bigint, INTERVAL '-1 day',  INTERVAL '4 days',  INTERVAL '4 days',  'active',    INTERVAL '-2 days'),
    ('toyota',        'Toyota RAV4 2.5 Hybrid AWD',     'RAV4',         2021::smallint,  55000, 'hybrid',   'automatic', 'BE', 'JTMRWRFV10D123456',
     'Hybrydowy SUV z napędem na cztery koła. Kamera cofania, adaptacyjny tempomat, niskie spalanie.',
      9500000::bigint, 100000::bigint, INTERVAL '-2 days', INTERVAL '3 days',  INTERVAL '3 days',  'active',    INTERVAL '-3 days'),
    ('mercedes-benz', 'Mercedes-Benz C 220 d AMG Line', 'C 220 d',      2017::smallint, 148000, 'diesel',   'automatic', 'DE', 'WDD2050041F123456',
     'Sedan w pakiecie AMG Line, nawigacja, kamera 360 stopni. Aukcja zbliża się do końca.',
      7000000::bigint,  50000::bigint, INTERVAL '-5 days', INTERVAL '20 hours', INTERVAL '20 hours', 'active',   INTERVAL '-6 days'),
    ('porsche',       'Porsche Macan S 3.0 V6',         'Macan',        2019::smallint,  72000, 'petrol',   'automatic', 'US', 'WP1AB2A57KL123456',
     'Sportowy SUV z pakietem Sport Chrono, pneumatycznym zawieszeniem i nagłośnieniem Bose.',
     19000000::bigint, 200000::bigint, INTERVAL '-2 days', INTERVAL '5 days',  INTERVAL '5 days',  'active',    INTERVAL '-3 days'),
    -- zakonczone
    ('ford',          'Ford Mustang GT 5.0 V8',         'Mustang',      2016::smallint,  81000, 'petrol',   'manual',    'US', '1FA6P8CF5G5123456',
     'Klasyczny muscle car z ręczną skrzynią. Aukcja przedłużona anty-snipingiem: ostatnia oferta tuż przed końcem.',
     11000000::bigint, 100000::bigint, INTERVAL '-10 days', INTERVAL '-3 days' + INTERVAL '30 seconds', INTERVAL '-3 days', 'ended', INTERVAL '-11 days'),
    ('volvo',         'Volvo XC60 D4 Momentum',         'XC60',         2018::smallint, 119000, 'diesel',   'automatic', 'SE', 'YV1UZA2MCJ1123456',
     'SUV ze szwedzkiego rynku, kompletna dokumentacja serwisowa, pakiet zimowy.',
      8000000::bigint,  50000::bigint, INTERVAL '-9 days', INTERVAL '-2 days', INTERVAL '-2 days', 'ended',      INTERVAL '-10 days'),
    ('honda',         'Honda Civic 1.0 VTEC Turbo',     'Civic',        2015::smallint, 156000, 'petrol',   'manual',    'GB', NULL,
     'Kompaktowy sedan z Wielkiej Brytanii, mały przebieg jak na rocznik. Aukcja zakończona bez ofert.',
      3000000::bigint,  10000::bigint, INTERVAL '-8 days', INTERVAL '-1 day',  INTERVAL '-1 day',  'ended',      INTERVAL '-9 days')
) AS v(brand_slug, title, model, year, mileage_km, fuel, transmission, origin_country, vin,
       description, starting_price, min_increment, starts_off, ends_off, orig_ends_off, status, created_off)
JOIN brands    b ON b.slug = v.brand_slug
JOIN companies c ON c.name = 'Auto Import Kraków';

-- 4. Oferty. Kwoty unikalne w aukcji, pierwsza >= cena wywolawcza, kazda kolejna o >= min_increment.
--    Czasy wzgledne (ujemne = w przeszlosci); oferty zakonczonych aukcji sa przed ends_at.
INSERT INTO bids (auction_id, user_id, amount, created_at)
SELECT a.id, u.id, v.amount, now() + v.off
FROM (VALUES
    -- Audi A4 (aktywna, krok 500 zl): kupujacy1 przebity przez kupujacy2, odpowiada, potem przebija go kupujacy3
    ('Audi A4 Avant 2.0 TDI S line',   'kupujacy1@example.com',  6500000::bigint, INTERVAL '-2 days' + INTERVAL '-4 hours'),
    ('Audi A4 Avant 2.0 TDI S line',   'kupujacy2@example.com',  6600000::bigint, INTERVAL '-1 day'  + INTERVAL '-20 hours'),
    ('Audi A4 Avant 2.0 TDI S line',   'kupujacy1@example.com',  6700000::bigint, INTERVAL '-1 day'  + INTERVAL '-2 hours'),
    ('Audi A4 Avant 2.0 TDI S line',   'kupujacy3@example.com',  6800000::bigint, INTERVAL '-5 hours'),
    -- Toyota RAV4 (aktywna, krok 1000 zl): prowadzi kupujacy2, kupujacy1 zostal przebity
    ('Toyota RAV4 2.5 Hybrid AWD',     'kupujacy1@example.com',  9500000::bigint, INTERVAL '-1 day'  + INTERVAL '-6 hours'),
    ('Toyota RAV4 2.5 Hybrid AWD',     'kupujacy2@example.com',  9700000::bigint, INTERVAL '-3 hours'),
    -- Mercedes C 220 d (aktywna, konczy sie za 20 h)
    ('Mercedes-Benz C 220 d AMG Line', 'kupujacy3@example.com',  7000000::bigint, INTERVAL '-4 days'),
    ('Mercedes-Benz C 220 d AMG Line', 'kupujacy2@example.com',  7100000::bigint, INTERVAL '-2 days' + INTERVAL '-3 hours'),
    ('Mercedes-Benz C 220 d AMG Line', 'kupujacy3@example.com',  7250000::bigint, INTERVAL '-1 hour'),
    -- Porsche Macan (aktywna, krok 2000 zl)
    ('Porsche Macan S 3.0 V6',         'kupujacy2@example.com', 19000000::bigint, INTERVAL '-1 day'  + INTERVAL '-12 hours'),
    ('Porsche Macan S 3.0 V6',         'kupujacy1@example.com', 19400000::bigint, INTERVAL '-20 hours'),
    ('Porsche Macan S 3.0 V6',         'kupujacy2@example.com', 19800000::bigint, INTERVAL '-30 minutes'),
    -- Ford Mustang (zakonczona, przedluzona): oryginalny koniec = -3 dni, ostatnia oferta 12 s przed nim
    ('Ford Mustang GT 5.0 V8',         'kupujacy1@example.com', 11000000::bigint, INTERVAL '-8 days'),
    ('Ford Mustang GT 5.0 V8',         'kupujacy3@example.com', 11200000::bigint, INTERVAL '-5 days'),
    ('Ford Mustang GT 5.0 V8',         'kupujacy1@example.com', 11300000::bigint, INTERVAL '-3 days' + INTERVAL '-1 minute'),
    ('Ford Mustang GT 5.0 V8',         'kupujacy2@example.com', 11400000::bigint, INTERVAL '-3 days' + INTERVAL '-12 seconds'),
    -- Volvo XC60 (zakonczona): wygrywa kupujacy1
    ('Volvo XC60 D4 Momentum',         'kupujacy2@example.com',  8000000::bigint, INTERVAL '-7 days'),
    ('Volvo XC60 D4 Momentum',         'kupujacy1@example.com',  8100000::bigint, INTERVAL '-5 days'),
    ('Volvo XC60 D4 Momentum',         'kupujacy1@example.com',  8250000::bigint, INTERVAL '-2 days' + INTERVAL '-2 hours')
) AS v(title, email, amount, off)
JOIN auctions a ON a.title = v.title
JOIN users    u ON u.email = v.email
ORDER BY v.off, v.amount;

-- 5. Aukcje zakonczone: zwyciezca = najwyzsza oferta (jak robi to CRON), zlozony FK (winning_bid_id, id)
UPDATE auctions a
SET winning_bid_id = (SELECT b.id FROM bids b WHERE b.auction_id = a.id ORDER BY b.amount DESC LIMIT 1)
WHERE a.status = 'ended';

-- 6. Powiadomienia w serwisie: outbid (przebity), won (wygrana), auction_ended (dla firmy)
INSERT INTO notifications (user_id, auction_id, type, created_at, read_at)
SELECT u.id, a.id, v.type, now() + v.off,
       CASE WHEN v.is_read THEN now() + v.off + INTERVAL '10 minutes' END
FROM (VALUES
    -- przebicia w aukcjach aktywnych
    ('kupujacy1@example.com', 'Audi A4 Avant 2.0 TDI S line', 'outbid',        INTERVAL '-1 day' + INTERVAL '-20 hours', TRUE),
    ('kupujacy2@example.com', 'Audi A4 Avant 2.0 TDI S line', 'outbid',        INTERVAL '-1 day' + INTERVAL '-2 hours',  FALSE),
    ('kupujacy1@example.com', 'Audi A4 Avant 2.0 TDI S line', 'outbid',        INTERVAL '-5 hours',                      FALSE),
    ('kupujacy1@example.com', 'Toyota RAV4 2.5 Hybrid AWD',   'outbid',        INTERVAL '-3 hours',                      FALSE),
    ('kupujacy1@example.com', 'Porsche Macan S 3.0 V6',       'outbid',        INTERVAL '-30 minutes',                   FALSE),
    ('kupujacy2@example.com', 'Porsche Macan S 3.0 V6',       'outbid',        INTERVAL '-20 hours',                     TRUE),
    -- aukcje zakonczone: przebicie tuz przed koncem, wygrane
    ('kupujacy1@example.com', 'Ford Mustang GT 5.0 V8',       'outbid',        INTERVAL '-3 days' + INTERVAL '-12 seconds', TRUE),
    ('kupujacy2@example.com', 'Ford Mustang GT 5.0 V8',       'won',           INTERVAL '-3 days' + INTERVAL '1 minute', TRUE),
    ('kupujacy1@example.com', 'Volvo XC60 D4 Momentum',       'won',           INTERVAL '-2 days' + INTERVAL '1 minute', FALSE),
    -- firma: aukcje zakonczone (takze ta bez ofert)
    ('firma1@example.com',    'Ford Mustang GT 5.0 V8',       'auction_ended', INTERVAL '-3 days' + INTERVAL '1 minute', TRUE),
    ('firma1@example.com',    'Volvo XC60 D4 Momentum',       'auction_ended', INTERVAL '-2 days' + INTERVAL '1 minute', TRUE),
    ('firma1@example.com',    'Honda Civic 1.0 VTEC Turbo',   'auction_ended', INTERVAL '-1 day'  + INTERVAL '1 minute', FALSE)
) AS v(email, title, type, off, is_read)
JOIN users    u ON u.email = v.email
JOIN auctions a ON a.title = v.title;

COMMIT;
