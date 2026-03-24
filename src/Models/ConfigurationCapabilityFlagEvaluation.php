<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilityFlagEvaluation
{
    public function __construct(
        /** @var string[] */
        public array $supportedTypes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(supportedTypes: array_map(
            'strval',
            is_array($data['supportedTypes'] ?? null)
                ? $data['supportedTypes']
                : [],
        ));
    }
}
