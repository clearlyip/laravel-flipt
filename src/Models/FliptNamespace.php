<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class FliptNamespace
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public bool $protected,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            protected: array_key_exists('protected', $data)
            && $data['protected'] === true,
        );
    }
}
