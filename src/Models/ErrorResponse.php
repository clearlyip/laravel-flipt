<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class ErrorResponse implements Response
{
    public function __construct(
        public string $type,
        public Error $errorResponse,
    ) {
        //
    }
}
