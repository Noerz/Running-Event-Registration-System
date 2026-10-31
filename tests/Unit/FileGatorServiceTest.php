<?php

namespace Tests\Unit;

use App\Services\ObjectStorage\DTO\ObjectStorageFile;
use App\Services\ObjectStorage\Exceptions\AuthenticationException;
use App\Services\ObjectStorage\Exceptions\FileNotFoundException;
use App\Services\ObjectStorage\Exceptions\ObjectStorageException;
use App\Services\ObjectStorage\FileGatorService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FileGatorServiceTest extends TestCase
{
    protected FileGatorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FileGatorService([
            'base_url' => 'https://dev.kantorku.my.id/cdn/api/v1',
            'token' => 'test-token-12345',
            'folder' => 'payment-proofs',
            'timeout' => 5,
            'retry' => 0,
        ]);
    }

    public function test_upload_success_returns_dto(): void
    {
        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/files/upload' => Http::response([
                'ok' => true,
                'data' => [
                    'file' => [
                        'name' => 'proof_123.jpg',
                        'type' => 'file',
                        'size' => 12345,
                        'time' => 1700000000,
                        'path' => '/payment-proofs/proof_123.jpg',
                        'mime' => 'image/jpeg',
                        'url' => null,
                    ],
                ],
            ], 201),
        ]);

        $file = $this->service->upload('fake-binary-content', '/payment-proofs', 'proof_123.jpg');

        $this->assertInstanceOf(ObjectStorageFile::class, $file);
        $this->assertSame('/payment-proofs/proof_123.jpg', $file->path);
        $this->assertSame('proof_123.jpg', $file->name);
        $this->assertSame(12345, $file->size);
        $this->assertSame('image/jpeg', $file->mimeType);
        $this->assertSame('jpg', $file->extension);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://dev.kantorku.my.id/cdn/api/v1/files/upload'
                && $request->hasHeader('X-API-Token', 'test-token-12345');
        });
    }

    public function test_upload_handles_authentication_failure(): void
    {
        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/files/upload' => Http::response([
                'ok' => false,
                'error' => [
                    'code' => 'invalid_token',
                    'message' => 'Token API tidak valid.',
                ],
            ], 401),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token API tidak valid.');

        $this->service->upload('content', '/payment-proofs', 'test.jpg');
    }

    public function test_download_returns_raw_bytes(): void
    {
        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/files/download*' => Http::response('raw-binary-bytes', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $content = $this->service->download('/payment-proofs/proof_123.jpg', inline: true);

        $this->assertSame('raw-binary-bytes', $content);
    }

    public function test_info_not_found_throws_exception(): void
    {
        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/files/info*' => Http::response([
                'ok' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'File tidak ditemukan.',
                ],
            ], 404),
        ]);

        $this->expectException(FileNotFoundException::class);

        $this->service->info('/payment-proofs/missing.jpg');
    }

    public function test_exists_returns_boolean(): void
    {
        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/files/info?path=%2Fpayment-proofs%2Fexist.jpg' => Http::response([
                'ok' => true,
                'data' => [
                    'name' => 'exist.jpg',
                    'path' => '/payment-proofs/exist.jpg',
                    'size' => 100,
                    'mime' => 'image/jpeg',
                ],
            ], 200),
            'https://dev.kantorku.my.id/cdn/api/v1/files/info?path=%2Fpayment-proofs%2Fnone.jpg' => Http::response([
                'ok' => false,
                'error' => ['code' => 'not_found'],
            ], 404),
        ]);

        $this->assertTrue($this->service->exists('/payment-proofs/exist.jpg'));
        $this->assertFalse($this->service->exists('/payment-proofs/none.jpg'));
    }

    public function test_delete_success(): void
    {
        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/files*' => Http::response([
                'ok' => true,
                'data' => ['deleted' => ['/payment-proofs/proof_123.jpg']],
            ], 200),
        ]);

        $result = $this->service->delete('/payment-proofs/proof_123.jpg');

        $this->assertTrue($result);
    }

    public function test_path_sanitization_removes_traversal(): void
    {
        $safe = $this->service->normalizePath('/payment-proofs/../../secret/file.jpg');
        $this->assertSame('/payment-proofs/secret/file.jpg', $safe);

        $safeFilename = $this->service->sanitizeFilename('../../bad file name #1!.jpg');
        $this->assertSame('bad_file_name__1_.jpg', $safeFilename);
    }

    public function test_auto_token_resolution_via_login(): void
    {
        $service = new FileGatorService([
            'base_url' => 'https://dev.kantorku.my.id/cdn/api/v1',
            'token' => null,
            'username' => 'admin',
            'password' => 'secret123',
            'timeout' => 5,
            'retry' => 0,
        ]);

        Http::fake([
            'https://dev.kantorku.my.id/cdn/api/v1/auth/token' => Http::response([
                'ok' => true,
                'data' => [
                    'token' => 'resolved-token-999',
                    'expires_at' => time() + 3600,
                ],
            ], 200),
            'https://dev.kantorku.my.id/cdn/api/v1/files/upload' => Http::response([
                'ok' => true,
                'data' => [
                    'file' => [
                        'name' => 'test.jpg',
                        'path' => '/payment-proofs/test.jpg',
                        'size' => 10,
                        'mime' => 'image/jpeg',
                    ],
                ],
            ], 201),
        ]);

        $file = $service->upload('content', '/payment-proofs', 'test.jpg');

        $this->assertSame('/payment-proofs/test.jpg', $file->path);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://dev.kantorku.my.id/cdn/api/v1/auth/token'
                && $request['username'] === 'admin';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://dev.kantorku.my.id/cdn/api/v1/files/upload'
                && $request->hasHeader('X-API-Token', 'resolved-token-999');
        });
    }
}
