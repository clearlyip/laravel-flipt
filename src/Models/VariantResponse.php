<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class VariantResponse implements Response
{
    public function __construct(
        public string $type,
        public Variant $variantResponse,
    ) {
        //
    }
}
