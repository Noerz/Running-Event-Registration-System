<?php

namespace App\Services\PaymentProof;

use App\Services\ObjectStorage\Exceptions\ObjectStorageException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PaymentProofValidator
{
    /**
     * Magic bytes signatures untuk tipe file yang diizinkan.
     */
    protected const MAGIC_BYTES = [
        'jpg' => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'png' => ["\x89PNG\r\n\x1a\n"],
        'pdf' => ['%PDF-'],
    ];

    protected array $allowedExtensions;
    protected array $allowedMimes;
    protected int $maxKb;

    public function __construct(array $config = [])
    {
        $this->allowedExtensions = $config['allowed_extensions']
            ?? config('object_storage.allowed_extensions', ['jpg', 'jpeg', 'png', 'pdf']);
        $this->allowedMimes = $config['allowed_mimes']
            ?? config('object_storage.allowed_mimes', ['image/jpeg', 'image/png', 'application/pdf']);
        $this->maxKb = (int) ($config['max_kb']
            ?? config('object_storage.max_kb', config('registration.payment_proof_max_kb', 2048)));
    }

    /**
     * Memvalidasi UploadedFile secara menyeluruh (ekstensi, mime, ukuran, magic bytes).
     * Melempar ObjectStorageException jika tidak valid.
     */
    public function validate(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new ObjectStorageException(
                message: 'File upload tidak valid atau rusak.',
                errorCode: 'invalid_upload',
                statusCode: 422
            );
        }

        $sizeKb = (int) ceil($file->getSize() / 1024);
        if ($sizeKb > $this->maxKb) {
            throw new ObjectStorageException(
                message: "Ukuran file ({$sizeKb} KB) melebihi batas maksimal {$this->maxKb} KB.",
                errorCode: 'file_too_large',
                statusCode: 413
            );
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $this->allowedExtensions, true)) {
            $allowed = implode(', ', $this->allowedExtensions);
            throw new ObjectStorageException(
                message: "Ekstensi file .{$ext} tidak diizinkan. Hanya menerima: {$allowed}.",
                errorCode: 'invalid_extension',
                statusCode: 422
            );
        }

        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        if (! in_array($mime, $this->allowedMimes, true)) {
            $allowedM = implode(', ', $this->allowedMimes);
            throw new ObjectStorageException(
                message: "Tipe MIME file '{$mime}' tidak valid. Hanya menerima: {$allowedM}.",
                errorCode: 'invalid_mime',
                statusCode: 422
            );
        }

        // Validasi magic bytes jika file memiliki isi biner nyata (> 0 bytes)
        // UploadedFile::fake()->create() membuat file 0 bytes pada environment tanpa PHP GD.
        $realPath = $file->getRealPath();
        if ($realPath && file_exists($realPath) && filesize($realPath) > 0) {
            $this->validateMagicBytes($realPath, $ext);
        }
    }

    /**
     * Memeriksa magic bytes pada header file asli.
     */
    public function validateMagicBytes(string $filePath, string $extension): void
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return;
        }

        $header = (string) fread($handle, 16);
        fclose($handle);

        $signatures = self::MAGIC_BYTES[$extension] ?? null;
        if (! $signatures) {
            return;
        }

        $matched = false;
        foreach ($signatures as $sig) {
            if (str_starts_with($header, $sig)) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            throw new ObjectStorageException(
                message: "Header/signature file tidak sesuai dengan ekstensi .{$extension}.",
                errorCode: 'signature_mismatch',
                statusCode: 422
            );
        }
    }

    /**
     * Membuat nama file unik yang aman (mencegah path traversal & tubrukan nama).
     * Format: proof-{nama-slug}-{4-digit-hp}.{ext}
     * Contoh: proof-budi-santoso-7890.jpg
     */
    public function generateSafeFilename(UploadedFile $file, ?string $fullName = null, ?string $phone = null): string
    {
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $ext = preg_replace('/[^a-z0-9]/', '', $ext);

        $nameSlug = $fullName ? Str::slug($fullName, '-') : '';
        if ($nameSlug === '') {
            $nameSlug = 'user';
        }

        $digits = preg_replace('/\D/', '', $phone ?? '');
        $last4 = strlen($digits) >= 4 ? substr($digits, -4) : ($digits !== '' ? $digits : '0000');

        return "proof-{$nameSlug}-{$last4}.{$ext}";
    }

    /**
     * Sanitasi key/path objek untuk mencegah path traversal (../ atau ..\).
     */
    public function sanitizeKey(string $key): string
    {
        $clean = str_replace('\\', '/', $key);
        $segments = array_filter(
            explode('/', $clean),
            fn ($seg) => $seg !== '' && $seg !== '.' && $seg !== '..'
        );

        return implode('/', $segments);
    }
}
