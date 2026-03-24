<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\SegmentOperator;

readonly class FlagRule
{
    public function __construct(
        public SegmentOperator $segmentOperator,
        /** @var string[] */
        public array $segments,
        /** @var FlagDistribution[] */
        public array $distributions,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            segmentOperator: SegmentOperator::from(
                (string) ($data['segmentOperator'] ?? ''),
            ),
            segments: array_map(
                'strval',
                is_array($data['segments'] ?? null) ? $data['segments'] : [],
            ),
            distributions: array_map(
                FlagDistribution::fromArray(...),
                is_array($data['distributions'] ?? null)
                    ? $data['distributions']
                    : [],
            ),
        );
    }
}
