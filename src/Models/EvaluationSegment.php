<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\SegmentMatchType;

readonly class EvaluationSegment
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public SegmentMatchType $matchType,
        public string $createdAt,
        public string $updatedAt,
        /** @var EvaluationConstraint[] */
        public array $constraints,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            matchType: SegmentMatchType::from(
                (string) ($data['matchType'] ?? ''),
            ),
            createdAt: (string) ($data['createdAt'] ?? ''),
            updatedAt: (string) ($data['updatedAt'] ?? ''),
            constraints: array_map(
                EvaluationConstraint::fromArray(...),
                is_array($data['constraints'] ?? null)
                    ? $data['constraints']
                    : [],
            ),
        );
    }
}
