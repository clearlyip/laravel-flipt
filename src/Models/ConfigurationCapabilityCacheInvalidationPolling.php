<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class ConfigurationCapabilityCacheInvalidationPolling
{
    public function __construct(
        public bool $enabled,
        public int $minPollingIntervalMs,
    ) {
        //
    }
}
