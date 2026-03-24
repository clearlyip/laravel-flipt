<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\SegmentOperator;

readonly class FlagRolloutSegment
{
    public function __construct(
        public bool $value,
        /** @var string[] */
        public array $segments,
        public SegmentOperator $segmentOperator,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: array_key_exists('value', $data) && $data['value'] === true,
            segments: array_map(
                'strval',
                is_array($data['segments'] ?? null) ? $data['segments'] : [],
            ),
            segmentOperator: SegmentOperator::from(
                (string) ($data['segmentOperator'] ?? ''),
            ),
        );
    }
}
