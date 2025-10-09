<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilityCacheInvalidation
{
    public function __construct(
        public ConfigurationCapabilityCacheInvalidationPolling $polling,
    ) {
        //
    }
}
