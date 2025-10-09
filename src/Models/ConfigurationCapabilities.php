<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilities
{
    public function __construct(
        public ConfigurationCapabilityCacheInvalidation $cacheInvalidation,
        public ConfigurationCapabilityFlagEvaluation $flagEvaluation,
    ) {
        //
    }
}
