<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\FlagReason;

readonly class Flag
{
    public function __construct(
        public string $key,
        public FlagReason $reason,
        public string $variant,
        public array $metadata,
        public mixed $value,
    ) {
        //
    }
}
