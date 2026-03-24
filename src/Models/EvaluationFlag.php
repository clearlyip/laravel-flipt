<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\FlagType;

readonly class EvaluationFlag
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public bool $enabled,
        public FlagType $type,
        public string $createdAt,
        public string $updatedAt,
        /** @var EvaluationRule[] */
        public array $rules,
        /** @var EvaluationRollout[] */
        public array $rollouts,
        public ?EvaluationVariant $defaultVariant = null,
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
            enabled: array_key_exists('enabled', $data)
            && $data['enabled'] === true,
            type: FlagType::from((string) ($data['type'] ?? '')),
            createdAt: (string) ($data['createdAt'] ?? ''),
            updatedAt: (string) ($data['updatedAt'] ?? ''),
            rules: array_map(
                EvaluationRule::fromArray(...),
                is_array($data['rules'] ?? null) ? $data['rules'] : [],
            ),
            rollouts: array_map(
                EvaluationRollout::fromArray(...),
                is_array($data['rollouts'] ?? null) ? $data['rollouts'] : [],
            ),
            defaultVariant: array_key_exists('defaultVariant', $data)
                ? EvaluationVariant::fromArray(
                    is_array($data['defaultVariant'])
                        ? $data['defaultVariant']
                        : [],
                )
                : null,
        );
    }
}
