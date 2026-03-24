<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class EvaluationVariant
{
    public function __construct(
        public string $id,
        public string $key,
        public string $attachment,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            key: (string) ($data['key'] ?? ''),
            attachment: (string) ($data['attachment'] ?? ''),
        );
    }
}
