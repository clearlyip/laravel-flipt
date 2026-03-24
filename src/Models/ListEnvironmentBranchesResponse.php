<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ListEnvironmentBranchesResponse
{
    public function __construct(
        /** @var BranchEnvironment[] */
        public array $branches,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(branches: array_map(
            BranchEnvironment::fromArray(...),
            is_array($data['branches'] ?? null) ? $data['branches'] : [],
        ));
    }
}
