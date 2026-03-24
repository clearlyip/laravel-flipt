<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\RolloutType;

readonly class FlagRollout
{
    public function __construct(
        public RolloutType $type,
        public string $description,
        public ?FlagRolloutSegment $segment = null,
        public ?FlagRolloutThreshold $threshold = null,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: RolloutType::from((string) ($data['type'] ?? '')),
            description: (string) ($data['description'] ?? ''),
            segment: array_key_exists('segment', $data)
                ? FlagRolloutSegment::fromArray(
                    is_array($data['segment']) ? $data['segment'] : [],
                )
                : null,
            threshold: array_key_exists('threshold', $data)
                ? FlagRolloutThreshold::fromArray(
                    is_array($data['threshold']) ? $data['threshold'] : [],
                )
                : null,
        );
    }
}
