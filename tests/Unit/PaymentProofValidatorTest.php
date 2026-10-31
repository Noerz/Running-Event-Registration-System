<?php

namespace Tests\Unit;

use App\Services\ObjectStorage\Exceptions\ObjectStorageException;
use App\Services\PaymentProof\PaymentProofValidator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PaymentProofValidatorTest extends TestCase
{
    protected PaymentProofValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new PaymentProofValidator([
            'max_kb' => 100, // 100 KB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
        ]);
    }

    public function test_valid_file_passes(): void
    {
        $file = UploadedFile::fake()->create('proof.jpg', 50, 'image/jpeg');

        // Tidak boleh melempar exception
        $this->validator->validate($file);
        $this->assertTrue(true);
    }

    public function test_disallowed_extension_throws_exception(): void
    {
        $file = UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream');

        $this->expectException(ObjectStorageException::class);
        $this->expectExceptionMessage('Ekstensi file .exe tidak diizinkan.');

        $this->validator->validate($file);
    }

    public function test_disallowed_mime_throws_exception(): void
    {
        $file = UploadedFile::fake()->create('document.jpg', 10, 'text/plain');

        $this->expectException(ObjectStorageException::class);
        $this->expectExceptionMessage("Tipe MIME file 'text/plain' tidak valid.");

        $this->validator->validate($file);
    }

    public function test_file_exceeding_max_size_throws_exception(): void
    {
        $file = UploadedFile::fake()->create('huge.jpg', 200, 'image/jpeg');

        $this->expectException(ObjectStorageException::class);
        $this->expectExceptionMessage('melebihi batas maksimal 100 KB.');

        $this->validator->validate($file);
    }

    public function test_magic_bytes_valid_jpeg(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'test_jpg');
        file_put_contents($temp, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00");

        $file = new UploadedFile($temp, 'valid.jpg', 'image/jpeg', null, true);

        $this->validator->validate($file);
        $this->assertTrue(true);

        @unlink($temp);
    }

    public function test_magic_bytes_invalid_signature_throws(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'test_fake');
        // Konten bukan JPEG (bukan FF D8 FF), misalnya string random
        file_put_contents($temp, "NOT_AN_IMAGE_HEADER_BUT_LONG_ENOUGH");

        // Set allowed_mimes mencakup teks untuk menguji spesifik rute signature checking
        $customValidator = new PaymentProofValidator([
            'max_kb' => 100,
            'allowed_extensions' => ['jpg'],
            'allowed_mimes' => ['text/plain', 'image/jpeg'],
        ]);

        $file = new UploadedFile($temp, 'fake.jpg', 'text/plain', null, true);

        $this->expectException(ObjectStorageException::class);
        $this->expectExceptionMessage('Header/signature file tidak sesuai dengan ekstensi .jpg.');

        try {
            $customValidator->validate($file);
        } finally {
            @unlink($temp);
        }
    }

    public function test_generate_safe_filename(): void
    {
        $file = UploadedFile::fake()->create('My Proof (1) [final]!.PNG', 10, 'image/png');

        $safe = $this->validator->generateSafeFilename($file, 'Budi Santoso', '081234567890');

        $this->assertSame('proof-budi-santoso-7890.png', $safe);
    }

    public function test_generate_safe_filename_fallback(): void
    {
        $file = UploadedFile::fake()->create('proof.jpg', 10, 'image/jpeg');

        $safe = $this->validator->generateSafeFilename($file, null, null);

        $this->assertSame('proof-user-0000.jpg', $safe);
    }

    public function test_sanitize_key_prevents_path_traversal(): void
    {
        $sanitized = $this->validator->sanitizeKey('../../etc/passwd');
        $this->assertSame('etc/passwd', $sanitized);
    }
}
