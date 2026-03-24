<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class FlagDistribution
{
    public function __construct(
        public string $variant,
        public float $rollout,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            variant: (string) ($data['variant'] ?? ''),
            rollout: is_numeric($data['rollout'] ?? null)
                ? (float) $data['rollout']
                : 0.0,
        );
    }
}
