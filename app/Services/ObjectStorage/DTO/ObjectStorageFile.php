<?php

namespace App\Services\ObjectStorage\DTO;

class ObjectStorageFile
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $name = null,
        public readonly ?string $mimeType = null,
        public readonly ?int $size = null,
        public readonly ?string $url = null,
        public readonly ?string $extension = null,
    ) {}

    public static function fromApiResponse(array $item): self
    {
        $path = (string) ($item['path'] ?? '');
        $name = $item['name'] ?? basename($path) ?: null;

        return new self(
            path: $path,
            name: $name,
            mimeType: $item['mime'] ?? $item['mime_type'] ?? null,
            size: isset($item['size']) ? (int) $item['size'] : null,
            url: $item['url'] ?? null,
            extension: $name ? (pathinfo($name, PATHINFO_EXTENSION) ?: null) : null,
        );
    }

    public function toMetadata(): array
    {
        return [
            'object_key' => $this->path,
            'file_name' => $this->name,
            'file_extension' => $this->extension,
            'mime_type' => $this->mimeType,
            'file_size' => $this->size,
            'file_url' => $this->url,
        ];
    }
}
