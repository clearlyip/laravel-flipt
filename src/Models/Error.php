<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\ErrorReason;

readonly class Error
{
    public function __construct(
        public string $flagKey,
        public string $namespaceKey,
        public ErrorReason $reason,
    ) {
        //
    }
}
