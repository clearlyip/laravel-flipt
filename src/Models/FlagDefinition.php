<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\FlagType;

readonly class FlagDefinition
{
    public function __construct(
        public string $key,
        public FlagType $type,
        public string $name,
        public string $description,
        public bool $enabled,
        /** @var FlagVariant[] */
        public array $variants,
        /** @var FlagRule[] */
        public array $rules,
        /** @var FlagRollout[] */
        public array $rollouts,
        public string $defaultVariant,
        public array $metadata,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            type: FlagType::from((string) ($data['type'] ?? '')),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            enabled: array_key_exists('enabled', $data)
            && $data['enabled'] === true,
            variants: array_map(
                FlagVariant::fromArray(...),
                is_array($data['variants'] ?? null) ? $data['variants'] : [],
            ),
            rules: array_map(
                FlagRule::fromArray(...),
                is_array($data['rules'] ?? null) ? $data['rules'] : [],
            ),
            rollouts: array_map(
                FlagRollout::fromArray(...),
                is_array($data['rollouts'] ?? null) ? $data['rollouts'] : [],
            ),
            defaultVariant: (string) ($data['defaultVariant'] ?? ''),
            metadata: is_array($data['metadata'] ?? null)
                ? $data['metadata']
                : [],
        );
    }
}
