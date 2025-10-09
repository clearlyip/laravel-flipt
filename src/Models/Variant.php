<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\Reason;

readonly class Variant
{
    public function __construct(
        public bool $match,
        /** @var string[] */
        public array $segmentKeys,
        public Reason $reason,
        public string $variantKey,
        public string $variantAttachment,
        public string $requestId,
        public float $requestDurationMillis,
        public string $timestamp,
        public string $flagKey,
    ) {
        //
    }
    //
}
