<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Configuration
{
    public function __construct(
        public string $name,
        public ConfigurationCapabilities $capabilities,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            capabilities: ConfigurationCapabilities::fromArray(
                is_array($data['capabilities'] ?? null)
                    ? $data['capabilities']
                    : [],
            ),
        );
    }
}
