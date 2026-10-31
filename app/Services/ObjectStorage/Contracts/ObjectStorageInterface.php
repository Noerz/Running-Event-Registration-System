<?php

namespace App\Services\ObjectStorage\Contracts;

use App\Services\ObjectStorage\DTO\ObjectStorageFile;

interface ObjectStorageInterface
{
    /**
     * Upload konten file (string/resource) ke path target.
     * Mengembalikan DTO ObjectStorageFile berisi metadata objek.
     */
    public function upload(
        mixed $contents,
        string $path,
        string $filename,
        bool $public = false,
        bool $overwrite = false
    ): ObjectStorageFile;

    /**
     * Mengambil isi file dalam bentuk string biner mentah.
     */
    public function download(string $path, bool $inline = false): string;

    /**
     * Mengambil isi file dalam bentuk stream resource PHP (berguna untuk streaming respons).
     *
     * @return resource
     */
    public function downloadStream(string $path, bool $inline = false);

    /**
     * Mengambil informasi/metadata file (ukuran, MIME, dsb).
     */
    public function info(string $path): ObjectStorageFile;

    /**
     * Menghapus file pada object storage.
     */
    public function delete(string $path): bool;

    /**
     * Mengecek apakah file ada pada object storage.
     */
    public function exists(string $path): bool;

    /**
     * Membuat direktori jika belum ada.
     */
    public function mkdir(string $path, string $name): bool;
}
