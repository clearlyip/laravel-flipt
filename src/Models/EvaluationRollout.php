<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\RolloutType;

readonly class EvaluationRollout
{
    public function __construct(
        public RolloutType $type,
        public int $rank,
        public ?EvaluationRolloutSegment $segment = null,
        public ?EvaluationRolloutThreshold $threshold = null,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: RolloutType::from((string) ($data['type'] ?? '')),
            rank: (int) ($data['rank'] ?? 0),
            segment: array_key_exists('segment', $data)
                ? EvaluationRolloutSegment::fromArray(
                    is_array($data['segment']) ? $data['segment'] : [],
                )
                : null,
            threshold: array_key_exists('threshold', $data)
                ? EvaluationRolloutThreshold::fromArray(
                    is_array($data['threshold']) ? $data['threshold'] : [],
                )
                : null,
        );
    }
}
