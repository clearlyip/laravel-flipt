<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class ResourceResponse implements Response
{
    public function __construct(
        public Resource $resource,
        public string $revision,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            resource: Resource::fromArray(
                is_array($data['resource'] ?? null) ? $data['resource'] : [],
            ),
            revision: (string) ($data['revision'] ?? ''),
        );
    }
}
