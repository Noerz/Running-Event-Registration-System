<?php

namespace App\Services\ObjectStorage;

use App\Services\ObjectStorage\Contracts\ObjectStorageInterface;
use App\Services\ObjectStorage\Exceptions\FileNotFoundException;
use App\Services\ObjectStorage\Exceptions\ObjectStorageException;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Throwable;

class FileGatorAdapter implements FilesystemAdapter
{
    protected PathPrefixer $prefixer;

    public function __construct(
        protected ObjectStorageInterface $service,
        string $prefix = ''
    ) {
        $this->prefixer = new PathPrefixer($prefix);
    }

    public function fileExists(string $path): bool
    {
        try {
            $prefixed = $this->prefixer->prefixPath($path);

            return $this->service->exists($prefixed);
        } catch (Throwable $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        // FileGator menganggap direktori ada jika dapat diakses
        return true;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $prefixed = $this->prefixer->prefixPath($path);
        $dir = dirname($prefixed);
        $name = basename($prefixed);

        try {
            $this->service->upload(
                contents: $contents,
                path: $dir === '.' ? '/' : $dir,
                filename: $name,
                public: false,
                overwrite: true
            );
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $prefixed = $this->prefixer->prefixPath($path);
        $dir = dirname($prefixed);
        $name = basename($prefixed);

        try {
            $this->service->upload(
                contents: $contents,
                path: $dir === '.' ? '/' : $dir,
                filename: $name,
                public: false,
                overwrite: true
            );
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        $prefixed = $this->prefixer->prefixPath($path);

        try {
            return $this->service->download($prefixed);
        } catch (FileNotFoundException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        $prefixed = $this->prefixer->prefixPath($path);

        try {
            $stream = $this->service->downloadStream($prefixed);
            if (! is_resource($stream)) {
                throw new ObjectStorageException("Gagal membuka stream untuk file: {$path}");
            }

            return $stream;
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function delete(string $path): void
    {
        $prefixed = $this->prefixer->prefixPath($path);

        try {
            $this->service->delete($prefixed);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefixed = $this->prefixer->prefixPath($path);

        try {
            $this->service->delete($prefixed);
        } catch (Throwable $e) {
            // Abaikan jika direktori tidak ada
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $prefixed = $this->prefixer->prefixPath($path);
        $parent = dirname($prefixed);
        $name = basename($prefixed);

        try {
            $this->service->mkdir($parent === '.' ? '/' : $parent, $name);
        } catch (Throwable $e) {
            // Direktori sudah ada atau berhasil dibuat
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Bukti pembayaran selalu private
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: Visibility::PRIVATE);
    }

    public function mimeType(string $path): FileAttributes
    {
        $prefixed = $this->prefixer->prefixPath($path);

        try {
            $info = $this->service->info($prefixed);

            return new FileAttributes($path, mimeType: $info->mimeType);
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes($path, lastModified: time());
    }

    public function fileSize(string $path): FileAttributes
    {
        $prefixed = $this->prefixer->prefixPath($path);

        try {
            $info = $this->service->info($prefixed);

            return new FileAttributes($path, fileSize: $info->size);
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
    }
}
