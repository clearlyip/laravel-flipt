<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilities
{
    public function __construct(
        public ConfigurationCapabilityCacheInvalidation $cacheInvalidation,
        public ConfigurationCapabilityFlagEvaluation $flagEvaluation,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            cacheInvalidation: ConfigurationCapabilityCacheInvalidation::fromArray(
                is_array($data['cacheInvalidation'] ?? null)
                    ? $data['cacheInvalidation']
                    : [],
            ),
            flagEvaluation: ConfigurationCapabilityFlagEvaluation::fromArray(
                is_array($data['flagEvaluation'] ?? null)
                    ? $data['flagEvaluation']
                    : [],
            ),
        );
    }
}
