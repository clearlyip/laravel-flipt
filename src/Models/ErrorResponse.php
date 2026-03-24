<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class ErrorResponse implements Response
{
    public function __construct(
        public string $type,
        public Error $errorResponse,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? ''),
            errorResponse: Error::fromArray(
                is_array($data['errorResponse'] ?? null)
                    ? $data['errorResponse']
                    : [],
            ),
        );
    }
}
