<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class BooleanResponse implements Response
{
    public function __construct(
        public string $type,
        public Boolean $booleanResponse,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? ''),
            booleanResponse: Boolean::fromArray(
                is_array($data['booleanResponse'] ?? null)
                    ? $data['booleanResponse']
                    : [],
            ),
        );
    }
}
