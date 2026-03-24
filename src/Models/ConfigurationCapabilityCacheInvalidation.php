<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilityCacheInvalidation
{
    public function __construct(
        public ConfigurationCapabilityCacheInvalidationPolling $polling,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(polling: ConfigurationCapabilityCacheInvalidationPolling::fromArray(
            is_array($data['polling'] ?? null) ? $data['polling'] : [],
        ));
    }
}
