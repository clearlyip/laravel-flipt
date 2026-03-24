<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilityCacheInvalidationPolling
{
    public function __construct(
        public bool $enabled,
        public int $minPollingIntervalMs,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: array_key_exists('enabled', $data)
            && $data['enabled'] === true,
            minPollingIntervalMs: (int) ($data['minPollingIntervalMs'] ?? 0),
        );
    }
}
