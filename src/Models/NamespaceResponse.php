<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class NamespaceResponse implements Response
{
    public function __construct(
        public FliptNamespace $namespace,
        public string $revision,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            namespace: FliptNamespace::fromArray(
                is_array($data['namespace'] ?? null) ? $data['namespace'] : [],
            ),
            revision: (string) ($data['revision'] ?? ''),
        );
    }
}
