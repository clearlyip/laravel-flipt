<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class FlagVariant
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public mixed $attachment,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            attachment: $data['attachment'] ?? null,
        );
    }
}
