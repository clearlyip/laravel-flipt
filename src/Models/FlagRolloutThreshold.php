<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class FlagRolloutThreshold
{
    public function __construct(
        public float $percentage,
        public bool $value,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            percentage: is_numeric($data['percentage'] ?? null)
                ? (float) $data['percentage']
                : 0.0,
            value: array_key_exists('value', $data) && $data['value'] === true,
        );
    }
}
