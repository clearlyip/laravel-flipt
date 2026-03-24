<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class ListNamespacesResponse implements Response
{
    public function __construct(
        /** @var FliptNamespace[] */
        public array $items,
        public string $revision,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(
                FliptNamespace::fromArray(...),
                is_array($data['items'] ?? null) ? $data['items'] : [],
            ),
            revision: (string) ($data['revision'] ?? ''),
        );
    }
}
