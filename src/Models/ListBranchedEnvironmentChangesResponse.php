<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ListBranchedEnvironmentChangesResponse
{
    public function __construct(
        /** @var Change[] */
        public array $changes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(changes: array_map(
            Change::fromArray(...),
            is_array($data['changes'] ?? null) ? $data['changes'] : [],
        ));
    }
}
