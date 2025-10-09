<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class BatchResponse
{
    public function __construct(
        public string $requestId,
        /** @var Response[] */
        public array $responses,
        public float $requestDurationMillis,
    ) {
        //
    }
}
