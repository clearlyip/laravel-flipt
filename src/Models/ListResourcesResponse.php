<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class ListResourcesResponse implements Response
{
    public function __construct(
        /** @var \Clearlyip\LaravelFlipt\Models\Resource[] */
        public array $resources,
        public string $revision,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            resources: array_map(
                Resource::fromArray(...),
                is_array($data['resources'] ?? null) ? $data['resources'] : [],
            ),
            revision: (string) ($data['revision'] ?? ''),
        );
    }
}
