<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Configuration
{
    public function __construct(
        public string $name,
        public ConfigurationCapabilities $capabilities,
    ) {
        //
    }
}
