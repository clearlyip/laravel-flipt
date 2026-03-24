<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Resource
{
    public function __construct(
        public string $namespaceKey,
        public string $key,
        public array $payload,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            namespaceKey: (string) ($data['namespaceKey'] ?? ''),
            key: (string) ($data['key'] ?? ''),
            payload: is_array($data['payload'] ?? null) ? $data['payload'] : [],
        );
    }
}
