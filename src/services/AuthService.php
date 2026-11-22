<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InvalidCredentialsException;
use App\Exception\ValidationException;
use App\Model\Role;
use App\Model\User;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;

/*
Konta użytkowników: walidacja pól rejestracji (jedyne miejsce, docs/api.md 1.7),
hashowanie hasła i zapis. Granice są stałymi tej klasy; kontroler ich nie powtarza.
Obsługuje też logowanie (login) i rejestrację firmy (konto DEALER + profil firmy w jednej transakcji).
*/
final class AuthService
{
    public const NAME_MIN = 2;
    public const NAME_MAX = 60;
    public const EMAIL_MAX = 254;
    public const PASSWORD_MIN = 8;
    // Limit bcrypt: dalsze bajty hasła byłyby ignorowane.
    public const PASSWORD_MAX = 72;
    public const COMPANY_NAME_MIN = 2;
    public const COMPANY_NAME_MAX = 120;
    public const CITY_MIN = 2;
    public const CITY_MAX = 80;
    // Telefon opcjonalny; ten sam wzorzec ma CHECK companies_phone_chk w bazie.
    public const PHONE_PATTERN = '/^\+?[0-9 ]{9,15}$/D';

    private const SQLSTATE_UNIQUE_VIOLATION = '23505';

    // Hash bcrypt losowego, nieużywanego hasła: logowanie na nieistniejące konto robi tyle samo pracy co zwykłe.
    private const DUMMY_HASH = '$2y$10$C6DZKxrkcjcG0k93NfFYf.wKVGExv8kdZPPJ9KojaZtNdAaa9W1gS';

    // $pdo to to samo połączenie, na którym działają repozytoria: transakcję prowadzi serwis (docs/api.md, 1.8).
    public function __construct(
        private readonly UserRepository $users,
        private readonly CompanyRepository $companies,
        private readonly \PDO $pdo,
        private readonly Translator $translator,
    ) {
    }

    /**
     * Rejestracja kupującego (rola BUYER). Błąd reguł: ValidationException 422,
     * zajęty e-mail: ValidationException 409 (przy polu e-mail).
     *
     * @throws ValidationException
     */
    public function registerBuyer(
        string $name,
        string $email,
        string $password,
        string $passwordRepeat,
        string $locale,
    ): User {
        $name = trim($name);
        $email = mb_strtolower(trim($email));

        $errors = $this->validateRegistration($name, $email, $password, $passwordRepeat);

        if ($errors !== []) {
            throw new ValidationException($errors, 422);
        }

        // Szybka odpowiedź dla typowego przypadku; wyścig dwóch rejestracji łapie indeks unikalny niżej.
        if ($this->users->findByEmail($email) !== null) {
            throw $this->emailTaken();
        }

        try {
            return $this->users->create($email, password_hash($password, PASSWORD_DEFAULT), $name, Role::Buyer, $locale);
        } catch (\PDOException $exception) {
            if ($exception->getCode() === self::SQLSTATE_UNIQUE_VIOLATION) {
                throw $this->emailTaken();
            }

            throw $exception;
        }
    }

    /**
     * Rejestracja firmy (rola DEALER, profil firmy w statusie pending; docs/api.md, 2.4).
     * Pola konta walidowane jak u kupującego (`name` to osoba kontaktowa), do tego nazwa
     * firmy, miasto i opcjonalny telefon. Konto i profil powstają w jednej transakcji:
     * błąd drugiego zapisu cofa pierwszy. Błąd reguł: ValidationException 422, zajęty
     * e-mail: ValidationException 409 (przy polu e-mail).
     *
     * @throws ValidationException
     */
    public function registerCompany(
        string $name,
        string $email,
        string $password,
        string $passwordRepeat,
        string $companyName,
        string $city,
        string $phone,
        string $locale,
    ): User {
        $name = trim($name);
        $email = mb_strtolower(trim($email));
        $companyName = trim($companyName);
        $city = trim($city);
        $phone = trim($phone);

        $errors = $this->validateRegistration($name, $email, $password, $passwordRepeat)
            + $this->validateCompany($companyName, $city, $phone);

        if ($errors !== []) {
            throw new ValidationException($errors, 422);
        }

        if ($this->users->findByEmail($email) !== null) {
            throw $this->emailTaken();
        }

        $this->pdo->beginTransaction();

        try {
            $user = $this->users->create($email, password_hash($password, PASSWORD_DEFAULT), $name, Role::Dealer, $locale);
            $this->companies->create($user->getId(), $companyName, $city, $phone === '' ? null : $phone);
            $this->pdo->commit();
        } catch (\PDOException $exception) {
            $this->pdo->rollBack();

            // Wyścig dwóch rejestracji z tym samym e-mailem łapie indeks unikalny users_email_lower_uq.
            if ($exception->getCode() === self::SQLSTATE_UNIQUE_VIOLATION) {
                throw $this->emailTaken();
            }

            throw $exception;
        }

        return $user;
    }

    /**
     * Logowanie (docs/api.md, 2.4). Puste pole lub zły format e-maila: ValidationException 422
     * (błędy przy polach). Zły e-mail albo hasło: InvalidCredentialsException (401, jeden
     * ogólny komunikat). Hasło sprawdzamy także dla nieistniejącego konta (hash zastępczy),
     * żeby czas odpowiedzi nie zdradzał, czy e-mail jest w bazie.
     *
     * @throws ValidationException
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): User
    {
        $email = mb_strtolower(trim($email));
        $errors = [];

        if ($email === '') {
            $errors['email'] = $this->translator->translate('validation.required');
        } elseif (mb_strlen($email) > self::EMAIL_MAX) {
            $errors['email'] = $this->translator->translate('validation.max_length', ['max' => self::EMAIL_MAX]);
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = $this->translator->translate('validation.email');
        }

        if ($password === '') {
            $errors['password'] = $this->translator->translate('validation.required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors, 422);
        }

        $user = $this->users->findByEmail($email);
        $passwordMatches = password_verify($password, $user?->getPasswordHash() ?? self::DUMMY_HASH);

        // Bajt \0 kończy hasło dla crypt(), więc "abc\0x" przeszłoby jako "abc"; rejestracja takich haseł nie przyjmuje.
        if ($user === null || !$passwordMatches || str_contains($password, "\0")) {
            throw new InvalidCredentialsException($this->translator->translate('auth.invalid_credentials'));
        }

        return $user;
    }

    /**
     * @return array<string, string> pole => przetłumaczony komunikat
     */
    private function validateRegistration(string $name, string $email, string $password, string $passwordRepeat): array
    {
        $errors = [];

        $nameLength = mb_strlen($name);
        if ($name === '') {
            $errors['name'] = $this->translator->translate('validation.required');
        } elseif ($nameLength < self::NAME_MIN) {
            $errors['name'] = $this->translator->translate('validation.min_length', ['min' => self::NAME_MIN]);
        } elseif ($nameLength > self::NAME_MAX) {
            $errors['name'] = $this->translator->translate('validation.max_length', ['max' => self::NAME_MAX]);
        }

        if ($email === '') {
            $errors['email'] = $this->translator->translate('validation.required');
        } elseif (mb_strlen($email) > self::EMAIL_MAX) {
            $errors['email'] = $this->translator->translate('validation.max_length', ['max' => self::EMAIL_MAX]);
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = $this->translator->translate('validation.email');
        }

        // Hasła nie przycinamy; długość w bajtach, bo tyle czyta bcrypt.
        $passwordLength = strlen($password);
        if ($password === '') {
            $errors['password'] = $this->translator->translate('validation.required');
        } elseif ($passwordLength < self::PASSWORD_MIN) {
            $errors['password'] = $this->translator->translate('validation.min_length', ['min' => self::PASSWORD_MIN]);
        } elseif ($passwordLength > self::PASSWORD_MAX) {
            $errors['password'] = $this->translator->translate('validation.password_max_bytes', ['max' => self::PASSWORD_MAX]);
        } elseif (str_contains($password, "\0")) {
            $errors['password'] = $this->translator->translate('validation.invalid_format');
        }

        if ($passwordRepeat === '') {
            $errors['passwordRepeat'] = $this->translator->translate('validation.required');
        } elseif (!isset($errors['password']) && !hash_equals($password, $passwordRepeat)) {
            $errors['passwordRepeat'] = $this->translator->translate('validation.password_mismatch');
        }

        return $errors;
    }

    /**
     * @return array<string, string> pole => przetłumaczony komunikat
     */
    private function validateCompany(string $companyName, string $city, string $phone): array
    {
        $errors = [];

        $nameLength = mb_strlen($companyName);
        if ($companyName === '') {
            $errors['companyName'] = $this->translator->translate('validation.required');
        } elseif ($nameLength < self::COMPANY_NAME_MIN) {
            $errors['companyName'] = $this->translator->translate('validation.min_length', ['min' => self::COMPANY_NAME_MIN]);
        } elseif ($nameLength > self::COMPANY_NAME_MAX) {
            $errors['companyName'] = $this->translator->translate('validation.max_length', ['max' => self::COMPANY_NAME_MAX]);
        }

        $cityLength = mb_strlen($city);
        if ($city === '') {
            $errors['city'] = $this->translator->translate('validation.required');
        } elseif ($cityLength < self::CITY_MIN) {
            $errors['city'] = $this->translator->translate('validation.min_length', ['min' => self::CITY_MIN]);
        } elseif ($cityLength > self::CITY_MAX) {
            $errors['city'] = $this->translator->translate('validation.max_length', ['max' => self::CITY_MAX]);
        }

        if ($phone !== '' && preg_match(self::PHONE_PATTERN, $phone) !== 1) {
            $errors['phone'] = $this->translator->translate('validation.invalid_format');
        }

        return $errors;
    }

    private function emailTaken(): ValidationException
    {
        return new ValidationException(['email' => $this->translator->translate('validation.email_taken')], 409);
    }
}
