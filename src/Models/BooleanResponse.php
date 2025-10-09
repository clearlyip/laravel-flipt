<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class BooleanResponse implements Response
{
    public function __construct(
        public string $type,
        public Boolean $booleanResponse,
    ) {
        //
    }
}
