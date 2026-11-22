<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use App\Model\Role;
use App\Model\User;
use App\Repository\UserRepository;

/*
Konta użytkowników: walidacja pól rejestracji (jedyne miejsce, docs/api.md 1.7),
hashowanie hasła i zapis. Granice są stałymi tej klasy; kontroler ich nie powtarza.
Logowanie dodaje T-019 w tej samej klasie.
*/
final class AuthService
{
    public const NAME_MIN = 2;
    public const NAME_MAX = 60;
    public const EMAIL_MAX = 254;
    public const PASSWORD_MIN = 8;
    // Limit bcrypt: dalsze bajty hasła byłyby ignorowane.
    public const PASSWORD_MAX = 72;

    private const SQLSTATE_UNIQUE_VIOLATION = '23505';

    public function __construct(
        private readonly UserRepository $users,
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
            $errors['password'] = $this->translator->translate('validation.max_length', ['max' => self::PASSWORD_MAX]);
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

    private function emailTaken(): ValidationException
    {
        return new ValidationException(['email' => $this->translator->translate('validation.email_taken')], 409);
    }
}
