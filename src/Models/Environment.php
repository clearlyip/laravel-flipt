<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Environment
{
    public function __construct(
        public string $key,
        public string $name,
        public bool $default,
        public ?EnvironmentConfiguration $configuration = null,
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
            default: array_key_exists('default', $data)
            && $data['default'] === true,
            configuration: array_key_exists('configuration', $data)
                ? EnvironmentConfiguration::fromArray(
                    is_array($data['configuration'])
                        ? $data['configuration']
                        : [],
                )
                : null,
        );
    }
}
