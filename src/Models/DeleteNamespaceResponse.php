<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class DeleteNamespaceResponse implements Response
{
    public function __construct(
        public string $revision,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(revision: (string) ($data['revision'] ?? ''));
    }
}
