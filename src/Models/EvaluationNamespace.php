<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class EvaluationNamespace
{
    public function __construct(
        public string $key,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(key: (string) ($data['key'] ?? ''));
    }
}
