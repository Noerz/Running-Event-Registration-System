<?php

namespace App\Services\PaymentProof;

use App\Models\Payment;
use App\Models\PaymentProof;
use App\Services\ObjectStorage\Exceptions\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PaymentProofService
{
    public function __construct(
        protected PaymentProofValidator $validator
    ) {}

    /**
     * Nama disk yang aktif.
     */
    public function diskName(): string
    {
        return (string) config('registration.payment_proof_disk', 'local');
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->diskName());
    }

    /**
     * Validasi dan upload file bukti pembayaran ke storage.
     * Mengembalikan payload metadata file yang baru diunggah.
     *
     * @return array{path: string, disk: string, file_name: string, file_extension: string, mime_type: string, file_size: int, file_url: ?string}
     */
    public function uploadFile(UploadedFile $file, ?string $fullName = null, ?string $phone = null): array
    {
        $this->validator->validate($file);

        $diskName = $this->diskName();
        $safeFilename = $this->validator->generateSafeFilename($file, $fullName, $phone);
        $path = 'payment-proofs/'.$safeFilename;

        $handle = @fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('File bukti pembayaran tidak dapat dibaca.');
        }

        try {
            $this->disk()->writeStream($path, $handle);
        } catch (Throwable $e) {
            Log::error('Gagal mengunggah bukti pembayaran ke storage.', [
                'disk' => $diskName,
                'target_path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal mengunggah bukti pembayaran: '.$e->getMessage(), 0, $e);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        return [
            'path' => $path,
            'disk' => $diskName,
            'file_name' => $safeFilename,
            'file_extension' => strtolower($file->getClientOriginalExtension() ?: 'jpg'),
            'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'file_size' => (int) ($file->getSize() ?: 0),
            'file_url' => null,
        ];
    }

    /**
     * Menyimpan baris metadata ke tabel payment_proofs.
     */
    public function recordMetadata(Payment $payment, array $uploaded): PaymentProof
    {
        return PaymentProof::create([
            'payment_id' => $payment->getKey(),
            'storage_disk' => $uploaded['disk'] ?? $this->diskName(),
            'object_key' => $uploaded['path'],
            'file_name' => $uploaded['file_name'] ?? basename($uploaded['path']),
            'file_extension' => $uploaded['file_extension'] ?? pathinfo($uploaded['path'], PATHINFO_EXTENSION),
            'mime_type' => $uploaded['mime_type'] ?? 'image/jpeg',
            'file_size' => (int) ($uploaded['file_size'] ?? 0),
            'file_url' => $uploaded['file_url'] ?? null,
        ]);
    }

    /**
     * Hapus objek fisik dari storage. Bersifat aman (tidak melempar exception).
     */
    public function deleteObject(?string $key): void
    {
        if (blank($key)) {
            return;
        }

        try {
            $this->disk()->delete($key);
        } catch (Throwable $e) {
            Log::warning('Gagal menghapus file dari storage.', [
                'key' => $key,
                'disk' => $this->diskName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Hapus bukti pembayaran milik Payment (file storage dan record DB).
     */
    public function delete(Payment $payment): void
    {
        $keys = $payment->proofs()->pluck('object_key')->all();

        if ($payment->payment_proof) {
            $keys[] = $payment->payment_proof;
        }

        $payment->proofs()->delete();
        $payment->update(['payment_proof' => '']);

        foreach (array_unique(array_filter($keys)) as $key) {
            $this->deleteObject($key);
        }
    }

    /**
     * Stream response untuk melihat (inline preview) file.
     */
    public function response(string $path): StreamedResponse
    {
        return $this->stream($path, false);
    }

    /**
     * Stream response untuk mengunduh (attachment) file.
     */
    public function download(string $path, ?string $name = null): StreamedResponse
    {
        return $this->stream($path, true, $name);
    }

    /**
     * Bangun streamed response; lempar FileNotFoundException bila objek tidak ada
     * agar controller bisa mengembalikan 404, bukan 500.
     */
    protected function stream(string $path, bool $attachment, ?string $name = null): StreamedResponse
    {
        if (! $this->exists($path)) {
            throw new FileNotFoundException($path);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = $this->disk();

        return $attachment
            ? $disk->download($path, $name)
            : $disk->response($path);
    }

    /**
     * Cek apakah file ada pada storage yang aktif.
     */
    public function exists(?string $key): bool
    {
        if (blank($key)) {
            return false;
        }

        try {
            return $this->disk()->exists($key);
        } catch (Throwable $e) {
            Log::warning('Gagal memeriksa keberadaan file pada storage.', [
                'key' => $key,
                'disk' => $this->diskName(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
