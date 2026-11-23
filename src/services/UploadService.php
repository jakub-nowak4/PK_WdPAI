<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;

/*
Zapis zdjęć aut (docs/api.md 2.5, kryterium 34). Jedyne miejsce, które dotyka plików z $_FILES.
Kolejność kontroli: kod błędu PHP, rozmiar, rzeczywisty MIME z finfo (nigdy typ z przeglądarki),
rozszerzenie nazwy z białej listy zgodne z MIME, getimagesize(). Plik dostaje losową nazwę
z rozszerzeniem wynikającym z MIME (nie z nazwy od użytkownika) i trafia do public/uploads/auctions/.
Serwis zwraca tylko ścieżkę publiczną ("/uploads/auctions/<nazwa>"), tę wartość zapisujemy w bazie.
Błąd walidacji to ValidationException (422) z już przetłumaczonym komunikatem przy polu.
*/
final class UploadService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_FILES = 8;
    public const FIELD = 'photos';

    /** Biała lista: rzeczywisty MIME => dozwolone rozszerzenia nazwy; pierwsze jest rozszerzeniem zapisu. */
    private const ALLOWED = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    private const SUBDIR = 'auctions';

    /**
     * @param string $uploadDir  absolutna ścieżka katalogu uploadów (public/uploads)
     * @param string $publicBase prefiks URL katalogu uploadów, bez końcowego "/"
     * @param bool $requireHttpUpload true: przyjmuje wyłącznie pliki z żądania HTTP (is_uploaded_file);
     *                                false tylko w testach jednostkowych
     */
    public function __construct(
        private readonly string $uploadDir,
        private readonly Translator $translator,
        private readonly string $publicBase = '/uploads',
        private readonly bool $requireHttpUpload = true,
    ) {
    }

    /**
     * Sprawdza i zapisuje jeden plik z elementu $_FILES.
     *
     * @param array<string, mixed> $file pojedynczy plik (name, tmp_name, size, error, type)
     * @return string ścieżka publiczna do zapisu w bazie, np. "/uploads/auctions/<32 hex>.jpg"
     * @throws ValidationException
     */
    public function store(array $file, string $field = self::FIELD): string
    {
        [$tmpName, $extension] = $this->validate($file, $field);

        $directory = $this->targetDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw $this->fail($field, 'upload.failed');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $directory . DIRECTORY_SEPARATOR . $name;

        $moved = $this->requireHttpUpload ? move_uploaded_file($tmpName, $target) : copy($tmpName, $target);
        if (!$moved) {
            throw $this->fail($field, 'upload.failed');
        }
        @chmod($target, 0644);

        return $this->publicBase . '/' . self::SUBDIR . '/' . $name;
    }

    /**
     * Zapisuje wiele plików (1 do MAX_FILES). Gdy któryś jest zły, usuwa już zapisane i rzuca wyjątek,
     * więc po błędzie nie zostają osierocone pliki.
     *
     * @param list<array<string, mixed>> $files pliki w kolejności przesłania (patrz normalizeFiles())
     * @return list<string>
     * @throws ValidationException
     */
    public function storeAll(array $files, string $field = self::FIELD): array
    {
        if ($files === []) {
            throw $this->fail($field, 'upload.required');
        }
        if (count($files) > self::MAX_FILES) {
            throw $this->fail($field, 'upload.too_many', ['max' => self::MAX_FILES]);
        }

        $saved = [];
        try {
            foreach ($files as $file) {
                $saved[] = $this->store($file, $field);
            }
        } catch (ValidationException $e) {
            $this->deleteAll($saved);
            throw $e;
        }

        return $saved;
    }

    /**
     * Usuwa zapisany plik po ścieżce publicznej z bazy. Ścieżka musi mieć dokładnie kształt
     * generowany przez store(), więc nie da się wyjść poza katalog uploadów.
     */
    public function delete(string $publicPath): bool
    {
        $prefix = $this->publicBase . '/' . self::SUBDIR . '/';
        if (!str_starts_with($publicPath, $prefix)) {
            return false;
        }
        $name = substr($publicPath, strlen($prefix));
        if (preg_match('/^[0-9a-f]{32}\.(?:jpg|png|webp)$/D', $name) !== 1) {
            return false;
        }

        $file = $this->targetDirectory() . DIRECTORY_SEPARATOR . $name;

        return is_file($file) && @unlink($file);
    }

    /**
     * @param list<string> $publicPaths
     */
    public function deleteAll(array $publicPaths): void
    {
        foreach ($publicPaths as $path) {
            $this->delete($path);
        }
    }

    /**
     * Zamienia strukturę $_FILES['photos'] (pola name[], tmp_name[]...) na listę plików.
     * Puste miejsca formularza (UPLOAD_ERR_NO_FILE) są pomijane.
     *
     * @param array<string, mixed> $entry
     * @return list<array<string, mixed>>
     */
    public static function normalizeFiles(array $entry): array
    {
        if (!isset($entry['name'])) {
            return [];
        }
        if (!is_array($entry['name'])) {
            return ($entry['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$entry];
        }

        $files = [];
        foreach (array_keys($entry['name']) as $i) {
            $file = [
                'name' => $entry['name'][$i] ?? '',
                'type' => $entry['type'][$i] ?? '',
                'tmp_name' => $entry['tmp_name'][$i] ?? '',
                'error' => $entry['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $entry['size'][$i] ?? 0,
            ];
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{0: string, 1: string} ścieżka pliku tymczasowego i rozszerzenie zapisu
     */
    private function validate(array $file, string $field): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw $this->fail($field, 'upload.required');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw $this->fail($field, 'upload.too_large', ['max' => self::MAX_BYTES / 1024 / 1024]);
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw $this->fail($field, 'upload.failed');
        }

        $tmpName = $file['tmp_name'] ?? '';
        if (!is_string($tmpName) || $tmpName === '' || !is_file($tmpName)) {
            throw $this->fail($field, 'upload.failed');
        }
        if ($this->requireHttpUpload && !is_uploaded_file($tmpName)) {
            throw $this->fail($field, 'upload.failed');
        }

        // Rozmiar z dysku, nie z pola "size" od przeglądarki.
        $size = filesize($tmpName);
        if ($size === false || $size === 0) {
            throw $this->fail($field, 'upload.failed');
        }
        if ($size > self::MAX_BYTES) {
            throw $this->fail($field, 'upload.too_large', ['max' => self::MAX_BYTES / 1024 / 1024]);
        }

        // Rzeczywisty typ z zawartości; pole "type" od przeglądarki jest ignorowane.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if ($mime === false || !isset(self::ALLOWED[$mime])) {
            throw $this->fail($field, 'upload.invalid_type');
        }

        $clientName = is_string($file['name'] ?? null) ? $file['name'] : '';
        $clientExtension = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if (!in_array($clientExtension, self::ALLOWED[$mime], true)) {
            throw $this->fail($field, 'upload.invalid_extension');
        }

        // Plik z poprawnym nagłówkiem, ale nie będący obrazem, odpada tutaj.
        $info = @getimagesize($tmpName);
        if ($info === false || ($info['mime'] ?? '') !== $mime) {
            throw $this->fail($field, 'upload.invalid_type');
        }

        return [$tmpName, self::ALLOWED[$mime][0]];
    }

    private function targetDirectory(): string
    {
        return rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . self::SUBDIR;
    }

    /**
     * @param array<string, scalar> $params
     */
    private function fail(string $field, string $key, array $params = []): ValidationException
    {
        return new ValidationException([$field => $this->translator->translate($key, $params)]);
    }
}
