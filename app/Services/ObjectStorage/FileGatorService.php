<?php

namespace App\Services\ObjectStorage;

use App\Services\ObjectStorage\Contracts\ObjectStorageInterface;
use App\Services\ObjectStorage\DTO\ObjectStorageFile;
use App\Services\ObjectStorage\Exceptions\AuthenticationException;
use App\Services\ObjectStorage\Exceptions\FileNotFoundException;
use App\Services\ObjectStorage\Exceptions\ObjectStorageException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FileGatorService implements ObjectStorageInterface
{
    protected string $baseUrl;
    protected ?string $token;
    protected ?string $username;
    protected ?string $password;
    protected int $timeout;
    protected int $retry;
    protected string $defaultFolder;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? config('object_storage.base_url', '')), '/');
        $this->token = array_key_exists('token', $config) ? $config['token'] : config('object_storage.token');
        $this->username = array_key_exists('username', $config) ? $config['username'] : config('object_storage.username');
        $this->password = array_key_exists('password', $config) ? $config['password'] : config('object_storage.password');
        $this->timeout = (int) ($config['timeout'] ?? config('object_storage.timeout', 30));
        $this->retry = (int) ($config['retry'] ?? config('object_storage.retry', 2));
        $this->defaultFolder = trim((string) ($config['folder'] ?? config('object_storage.folder', '')), '/');
    }

    public function upload(
        mixed $contents,
        string $path,
        string $filename,
        bool $public = false,
        bool $overwrite = false
    ): ObjectStorageFile {
        $normalizedPath = $this->normalizeDir($path);
        $safeName = $this->sanitizeFilename($filename);

        $client = $this->httpClient();

        $contentData = is_resource($contents) ? stream_get_contents($contents) : (string) $contents;

        try {
            $response = $client
                ->attach('file', $contentData, $safeName)
                ->post($this->url('/files/upload'), [
                    'path' => $normalizedPath,
                    'name' => $safeName,
                    'public' => $public ? 'true' : 'false',
                    'overwrite' => $overwrite ? 'true' : 'false',
                ]);
        } catch (Throwable $e) {
            $this->logError('Gagal melakukan upload ke object storage', [
                'target_path' => $normalizedPath,
                'filename' => $safeName,
                'error' => $e->getMessage(),
            ]);
            throw new ObjectStorageException(
                message: "Koneksi ke object storage gagal saat upload: {$e->getMessage()}",
                errorCode: 'connection_error',
                previous: $e
            );
        }

        $this->assertSuccessful($response, "Upload file {$safeName} gagal");

        $data = $response->json('data.file') ?? [];
        $file = ObjectStorageFile::fromApiResponse($data);

        Log::info('File berhasil diunggah ke object storage', [
            'object_key' => $file->path,
            'file_name' => $file->name,
            'file_size' => $file->size,
            'is_public' => $public,
        ]);

        return $file;
    }

    public function download(string $path, bool $inline = false): string
    {
        $normalized = $this->normalizePath($path);

        try {
            $response = $this->httpClient()->get($this->url('/files/download'), [
                'path' => $normalized,
                'inline' => $inline ? 'true' : 'false',
            ]);
        } catch (Throwable $e) {
            $this->logError('Gagal mendownload file dari object storage', [
                'path' => $normalized,
                'error' => $e->getMessage(),
            ]);
            throw new ObjectStorageException(
                message: "Koneksi ke object storage gagal saat download: {$e->getMessage()}",
                errorCode: 'connection_error',
                previous: $e
            );
        }

        $this->assertSuccessful($response, "Download file {$normalized} gagal");

        return $response->body();
    }

    public function downloadStream(string $path, bool $inline = false)
    {
        $contents = $this->download($path, $inline);
        $stream = fopen('php://temp', 'r+');
        if ($stream !== false) {
            fwrite($stream, $contents);
            rewind($stream);
        }

        return $stream;
    }

    public function info(string $path): ObjectStorageFile
    {
        $normalized = $this->normalizePath($path);

        try {
            $response = $this->httpClient()->get($this->url('/files/info'), [
                'path' => $normalized,
            ]);
        } catch (Throwable $e) {
            $this->logError('Gagal mengambil info file dari object storage', [
                'path' => $normalized,
                'error' => $e->getMessage(),
            ]);
            throw new ObjectStorageException(
                message: "Koneksi ke object storage gagal saat membaca info: {$e->getMessage()}",
                errorCode: 'connection_error',
                previous: $e
            );
        }

        $this->assertSuccessful($response, "Pengambilan info file {$normalized} gagal");

        $data = $response->json('data') ?? [];

        return ObjectStorageFile::fromApiResponse($data);
    }

    public function delete(string $path): bool
    {
        $normalized = $this->normalizePath($path);

        try {
            // Coba DELETE /files?path=... terlebih dahulu sesuai dokumentasi FileGator
            $response = $this->httpClient()
                ->withQueryParameters([
                    'path' => $normalized,
                    'recursive' => 'false',
                ])
                ->delete($this->url('/files'));

            // Jika DELETE method tidak didukung, coba fallback POST /files/delete
            if ($response->status() === 405) {
                $response = $this->httpClient()->post($this->url('/files/delete'), [
                    'items' => [$normalized],
                ]);
            }
        } catch (Throwable $e) {
            $this->logError('Gagal menghapus file di object storage', [
                'path' => $normalized,
                'error' => $e->getMessage(),
            ]);
            throw new ObjectStorageException(
                message: "Koneksi ke object storage gagal saat menghapus file: {$e->getMessage()}",
                errorCode: 'connection_error',
                previous: $e
            );
        }

        if ($response->status() === 404) {
            // Anggap sukses jika file memang sudah tidak ada (idempotent)
            return true;
        }

        $this->assertSuccessful($response, "Penghapusan file {$normalized} gagal");

        Log::info('File berhasil dihapus dari object storage', [
            'object_key' => $normalized,
        ]);

        return true;
    }

    public function exists(string $path): bool
    {
        try {
            $this->info($path);

            return true;
        } catch (FileNotFoundException) {
            return false;
        } catch (ObjectStorageException $e) {
            if ($e->errorCode === 'not_found' || $e->getCode() === 404) {
                return false;
            }
            throw $e;
        }
    }

    public function mkdir(string $path, string $name): bool
    {
        $dir = $this->normalizeDir($path);
        $safeName = $this->sanitizeFilename($name);

        try {
            $response = $this->httpClient()->post($this->url('/files/mkdir'), [
                'path' => $dir,
                'name' => $safeName,
            ]);
        } catch (Throwable $e) {
            $this->logError('Gagal membuat folder di object storage', [
                'path' => $dir,
                'name' => $safeName,
                'error' => $e->getMessage(),
            ]);
            throw new ObjectStorageException(
                message: "Koneksi ke object storage gagal saat mkdir: {$e->getMessage()}",
                errorCode: 'connection_error',
                previous: $e
            );
        }

        if ($response->json('error.code') === 'already_exists') {
            return true;
        }

        $this->assertSuccessful($response, "Pembuatan direktori {$safeName} gagal");

        return true;
    }

    protected function httpClient(): PendingRequest
    {
        $token = $this->resolveToken();

        $client = Http::timeout($this->timeout);

        if ($this->retry > 0) {
            $client->retry($this->retry, 200, throw: false);
        }

        if ($token) {
            $client->withHeaders([
                'X-API-Token' => $token,
                'Accept' => 'application/json',
            ]);
        }

        return $client;
    }

    protected function resolveToken(): ?string
    {
        if (! empty($this->token)) {
            return $this->token;
        }

        if (empty($this->username) || empty($this->password)) {
            return null;
        }

        $cacheKey = 'filegator_auth_token_'.md5($this->baseUrl.':'.$this->username);

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->acceptJson()
                    ->post($this->url('/auth/token'), [
                        'username' => $this->username,
                        'password' => $this->password,
                        'label' => 'running-event-app',
                    ]);
            } catch (Throwable $e) {
                $this->logError('Gagal menghubungi endpoint auth object storage', [
                    'error' => $e->getMessage(),
                ]);
                throw new AuthenticationException(
                    message: "Gagal terhubung ke autentikasi object storage: {$e->getMessage()}",
                    previous: $e
                );
            }

            if (! $response->successful() || ! $response->json('ok')) {
                $err = $response->json('error.message') ?? 'Kredensial object storage salah';
                $this->logError('Autentikasi token object storage ditolak', [
                    'status' => $response->status(),
                    'code' => $response->json('error.code'),
                ]);
                throw new AuthenticationException($err);
            }

            return (string) $response->json('data.token');
        });
    }

    protected function assertSuccessful(Response $response, string $fallbackMessage): void
    {
        if ($response->successful()) {
            $ok = $response->json('ok');
            if ($ok === false) {
                $code = (string) $response->json('error.code', 'unknown');
                $msg = (string) $response->json('error.message', $fallbackMessage);
                $this->throwFromErrorCode($code, $msg, $response->status());
            }

            return;
        }

        $status = $response->status();
        $code = (string) ($response->json('error.code') ?? 'http_'.$status);
        $message = (string) ($response->json('error.message') ?? $fallbackMessage);

        $this->throwFromErrorCode($code, $message, $status);
    }

    protected function throwFromErrorCode(string $code, string $message, int $status): void
    {
        if ($code === 'not_found' || $status === 404) {
            throw new FileNotFoundException($message);
        }

        if (in_array($code, ['invalid_token', 'invalid_credentials', 'permission_denied'], true) || in_array($status, [401, 403], true)) {
            throw new AuthenticationException($message);
        }

        throw new ObjectStorageException(
            message: $message,
            errorCode: $code,
            statusCode: $status
        );
    }

    protected function url(string $endpoint): string
    {
        return $this->baseUrl.'/'.ltrim($endpoint, '/');
    }

    public function normalizePath(string $path): string
    {
        $clean = str_replace('\\', '/', $path);
        // Cegah path traversal
        $segments = array_filter(explode('/', $clean), fn ($s) => $s !== '' && $s !== '.' && $s !== '..');

        return '/'.implode('/', $segments);
    }

    public function normalizeDir(string $path): string
    {
        $normalized = $this->normalizePath($path);

        return $normalized === '' ? '/' : $normalized;
    }

    public function sanitizeFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));
        // Hanya izinkan karakter alfanumerik, dash, underscore, dan dot
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $basename) ?: 'file';

        return ltrim($sanitized, '.');
    }

    protected function logError(string $message, array $context = []): void
    {
        // Sanitasi context agar tidak pernah membocorkan token/password
        unset($context['token'], $context['password'], $context['authorization'], $context['auth']);
        Log::error($message, $context);
    }
}
