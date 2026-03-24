<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\SegmentOperator;

readonly class EvaluationRule
{
    public function __construct(
        public string $id,
        /** @var EvaluationSegment[] */
        public array $segments,
        public int $rank,
        public SegmentOperator $segmentOperator,
        /** @var EvaluationDistribution[] */
        public array $distributions,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            segments: array_map(
                EvaluationSegment::fromArray(...),
                is_array($data['segments'] ?? null) ? $data['segments'] : [],
            ),
            rank: (int) ($data['rank'] ?? 0),
            segmentOperator: SegmentOperator::from(
                (string) ($data['segmentOperator'] ?? ''),
            ),
            distributions: array_map(
                EvaluationDistribution::fromArray(...),
                is_array($data['distributions'] ?? null)
                    ? $data['distributions']
                    : [],
            ),
        );
    }
}
