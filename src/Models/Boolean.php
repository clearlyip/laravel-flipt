<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\Reason;

readonly class Boolean
{
    public function __construct(
        public bool $enabled,
        public Reason $reason,
        public string $requestId,
        public float $requestDurationMillis,
        public string $timestamp,
        public string $flagKey,
    ) {
        //
    }
}
