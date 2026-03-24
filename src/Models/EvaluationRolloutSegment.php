<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\SegmentOperator;

readonly class EvaluationRolloutSegment
{
    public function __construct(
        public bool $value,
        public SegmentOperator $segmentOperator,
        /** @var EvaluationSegment[] */
        public array $segments,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: array_key_exists('value', $data) && $data['value'] === true,
            segmentOperator: SegmentOperator::from(
                (string) ($data['segmentOperator'] ?? ''),
            ),
            segments: array_map(
                EvaluationSegment::fromArray(...),
                is_array($data['segments'] ?? null) ? $data['segments'] : [],
            ),
        );
    }
}
