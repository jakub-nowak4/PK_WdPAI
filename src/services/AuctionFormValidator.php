<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use App\Model\Fuel;
use App\Model\Money;
use App\Model\NewAuction;
use App\Model\Transmission;
use App\Repository\BrandRepository;
use DateTimeImmutable;
use DateTimeZone;

/*
Walidacja formularza "Dodaj aukcję" (docs/api.md 1.7 i 2.5): jedyne miejsce z regułami pól. Granice są stałymi
tej klasy (te same ma CHECK w docker/db/01-schema.sql, więc baza jest tylko ostatnią linią obrony). Dopiero po
udanej walidacji powstaje NewAuction; marka przychodzi jako slug, a id bierzemy z wiersza słownika brands.
Czas "teraz" podaje wołający (czas serwera bazy), dzięki czemu reguły dat da się sprawdzić bez zegara.
*/
final class AuctionFormValidator
{
    public const TITLE_MIN = 5;
    public const TITLE_MAX = 120;
    public const MODEL_MIN = 1;
    public const MODEL_MAX = 60;
    public const YEAR_MIN = 1950;
    public const MILEAGE_MAX = 2_000_000;
    public const DESCRIPTION_MAX = 5000;
    public const PRICE_MIN_ZLOTY = 100;
    public const PRICE_MAX_ZLOTY = 10_000_000;
    public const START_TOLERANCE_MINUTES = 5;
    public const DURATION_MIN = 'PT1H';
    public const DURATION_MAX = 'P30D';
    public const TIMEZONE = 'Europe/Warsaw';

    /** Kroki licytacji w pełnych złotych (CHECK auctions_increment_chk w groszach). */
    public const INCREMENTS_ZLOTY = [100, 200, 500, 1000, 2000];
    public const COUNTRIES = ['US', 'CA', 'DE', 'FR', 'NL', 'BE', 'IT', 'ES', 'SE', 'CH', 'GB', 'JP', 'KR'];
    public const VIN_PATTERN = '/^[A-HJ-NPR-Z0-9]{17}$/D';
    private const SLUG_PATTERN = '/^[a-z0-9-]{1,40}$/D';

    public function __construct(
        private readonly BrandRepository $brands,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $input surowe pola tekstowe formularza (nazwy jak w docs/api.md 2.5)
     * @param int $photoCount liczba przesłanych plików (treść plików sprawdza UploadService)
     * @throws ValidationException 422 z błędami przy polach
     */
    public function validate(array $input, int $companyId, int $photoCount, DateTimeImmutable $now): NewAuction
    {
        $errors = [];

        $title = $this->text($input, 'title');
        $this->checkLine($errors, 'title', $title, self::TITLE_MIN, self::TITLE_MAX);

        $brand = null;
        $slug = $this->text($input, 'brand');
        if ($slug === '') {
            $errors['brand'] = $this->translator->translate('validation.required');
        } elseif (preg_match(self::SLUG_PATTERN, $slug) !== 1 || ($brand = $this->brands->findBySlug($slug)) === null) {
            $errors['brand'] = $this->translator->translate('validation.invalid_choice');
        }

        $model = $this->text($input, 'model');
        $this->checkLine($errors, 'model', $model, self::MODEL_MIN, self::MODEL_MAX);

        $year = $this->integer($errors, 'year', $input, self::YEAR_MIN, (int) $now->format('Y') + 1);
        $mileage = $this->integer($errors, 'mileageKm', $input, 0, self::MILEAGE_MAX);
        $fuel = $this->choice($errors, 'fuel', $input, Fuel::tryFrom(...));
        $transmission = $this->choice($errors, 'transmission', $input, Transmission::tryFrom(...));

        $country = $this->text($input, 'originCountry');
        if ($country === '') {
            $errors['originCountry'] = $this->translator->translate('validation.required');
        } elseif (!in_array($country, self::COUNTRIES, true)) {
            $errors['originCountry'] = $this->translator->translate('validation.invalid_choice');
        }

        $vin = strtoupper($this->text($input, 'vin'));
        if ($vin !== '' && preg_match(self::VIN_PATTERN, $vin) !== 1) {
            $errors['vin'] = $this->translator->translate('validation.invalid_format');
        }

        // Opis może mieć nowe wiersze, ale nie inne znaki sterujące.
        $description = $this->text($input, 'description');
        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            $errors['description'] = $this->translator->translate('validation.max_length', ['max' => self::DESCRIPTION_MAX]);
        } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $description) === 1) {
            $errors['description'] = $this->translator->translate('validation.invalid_format');
        }

        $price = $this->integer($errors, 'startingPrice', $input, self::PRICE_MIN_ZLOTY, self::PRICE_MAX_ZLOTY);
        $increment = $this->integer($errors, 'minIncrement', $input, self::INCREMENTS_ZLOTY[0], max(self::INCREMENTS_ZLOTY));
        if ($increment !== null && !in_array($increment, self::INCREMENTS_ZLOTY, true)) {
            $increment = null;
            $errors['minIncrement'] = $this->translator->translate('validation.invalid_choice');
        }

        [$startsAt, $endsAt] = $this->dates($errors, $input, $now);

        if ($photoCount === 0) {
            $errors['photos'] = $this->translator->translate('upload.required');
        } elseif ($photoCount > UploadService::MAX_FILES) {
            $errors['photos'] = $this->translator->translate('upload.too_many', ['max' => UploadService::MAX_FILES]);
        }

        if ($errors !== []) {
            throw new ValidationException($errors, 422);
        }

        return new NewAuction(
            $companyId,
            $brand->getId(),
            $title,
            $model,
            $year,
            $mileage,
            $fuel,
            $transmission,
            $country,
            $vin === '' ? null : $vin,
            $description,
            Money::fromZloty($price),
            Money::fromZloty($increment),
            $startsAt,
            $endsAt,
        );
    }

    /**
     * Początek (puste = teraz, najwyżej 5 minut wstecz) i koniec (od 1 godziny do 30 dni po początku).
     * Wartość z datetime-local jest czasem Europe/Warsaw. Koniec nie jest porównywany z początkiem,
     * gdy sam początek jest błędny.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $input
     * @return array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable}
     */
    private function dates(array &$errors, array $input, DateTimeImmutable $now): array
    {
        $startsRaw = $this->text($input, 'startsAt');
        $startsAt = $now;
        if ($startsRaw !== '') {
            $startsAt = $this->parseLocal($startsRaw);
            if ($startsAt === null) {
                $errors['startsAt'] = $this->translator->translate('validation.datetime');
            } elseif ($startsAt < $now->modify('-' . self::START_TOLERANCE_MINUTES . ' minutes')) {
                $errors['startsAt'] = $this->translator->translate('auction.error.starts_in_past');
                $startsAt = null;
            }
        }

        $endsRaw = $this->text($input, 'endsAt');
        $endsAt = null;
        if ($endsRaw === '') {
            $errors['endsAt'] = $this->translator->translate('validation.required');
        } else {
            $endsAt = $this->parseLocal($endsRaw);
            if ($endsAt === null) {
                $errors['endsAt'] = $this->translator->translate('validation.datetime');
            } elseif ($startsAt !== null && $endsAt < $startsAt->add(new \DateInterval(self::DURATION_MIN))) {
                $errors['endsAt'] = $this->translator->translate('auction.error.ends_too_soon');
            } elseif ($startsAt !== null && $endsAt > $startsAt->add(new \DateInterval(self::DURATION_MAX))) {
                $errors['endsAt'] = $this->translator->translate('auction.error.ends_too_late');
            }
        }

        return [$startsAt, $endsAt];
    }

    // "2026-12-01T18:30" (datetime-local, czasem z sekundami) w strefie Warszawy; inny zapis lub data spoza kalendarza to null.
    private function parseLocal(string $value): ?DateTimeImmutable
    {
        $zone = new DateTimeZone(self::TIMEZONE);
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value, $zone);
            $problems = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($problems === false || ($problems['warning_count'] === 0 && $problems['error_count'] === 0))) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Pole jednowierszowe: wymagane, długość po przycięciu, bez znaków sterujących.
     *
     * @param array<string, string> $errors
     */
    private function checkLine(array &$errors, string $field, string $value, int $min, int $max): void
    {
        $length = mb_strlen($value);
        if ($value === '') {
            $errors[$field] = $this->translator->translate('validation.required');
        } elseif ($length < $min) {
            $errors[$field] = $this->translator->translate('validation.min_length', ['min' => $min]);
        } elseif ($length > $max) {
            $errors[$field] = $this->translator->translate('validation.max_length', ['max' => $max]);
        } elseif (preg_match('/[\x00-\x1F\x7F-\x9F]/u', $value) === 1) {
            $errors[$field] = $this->translator->translate('validation.invalid_format');
        }
    }

    /**
     * Liczba całkowita z zakresu (same cyfry, bez znaku i zer wiodących). Zwraca null i zapisuje błąd przy polu.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $input
     */
    private function integer(array &$errors, string $field, array $input, int $min, int $max): ?int
    {
        $value = $this->text($input, $field);
        if ($value === '') {
            $errors[$field] = $this->translator->translate('validation.required');

            return null;
        }
        if (preg_match('/^(0|[1-9][0-9]{0,9})$/D', $value) !== 1) {
            $errors[$field] = $this->translator->translate('validation.number');

            return null;
        }

        $number = (int) $value;
        if ($number < $min) {
            $errors[$field] = $this->translator->translate('validation.min_value', ['min' => $min]);

            return null;
        }
        if ($number > $max) {
            $errors[$field] = $this->translator->translate('validation.max_value', ['max' => $max]);

            return null;
        }

        return $number;
    }

    /**
     * Wartość enuma (biała lista przez ::tryFrom). Zwraca null i zapisuje błąd przy polu.
     *
     * @template T of \BackedEnum
     * @param array<string, string> $errors
     * @param array<string, mixed> $input
     * @param \Closure(string): ?T $resolve
     * @return T|null
     */
    private function choice(array &$errors, string $field, array $input, \Closure $resolve): ?\BackedEnum
    {
        $value = $this->text($input, $field);
        if ($value === '') {
            $errors[$field] = $this->translator->translate('validation.required');

            return null;
        }

        $enum = $resolve($value);
        if ($enum === null) {
            $errors[$field] = $this->translator->translate('validation.invalid_choice');
        }

        return $enum;
    }

    // Pole jako tekst; brak lub tablica (title[]=x) to pusty tekst, który odrzuci reguła "wymagane".
    private function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
