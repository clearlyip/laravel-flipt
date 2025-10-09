<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Bulk
{
    public function __construct(
        /** @var Flag[] */
        public array $flags,
    ) {
        //
    }
}
