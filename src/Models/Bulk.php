<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Bulk
{
    public function __construct(
        /** @var Flag[] */
        public array $flags,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(flags: array_map(
            Flag::fromArray(...),
            is_array($data['flags'] ?? null) ? $data['flags'] : [],
        ));
    }
}
