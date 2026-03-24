<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\ConstraintComparisonType;

readonly class EvaluationConstraint
{
    public function __construct(
        public ConstraintComparisonType $type,
        public string $property,
        public string $operator,
        public string $value,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: ConstraintComparisonType::from(
                (string) ($data['type'] ?? ''),
            ),
            property: (string) ($data['property'] ?? ''),
            operator: (string) ($data['operator'] ?? ''),
            value: (string) ($data['value'] ?? ''),
        );
    }
}
