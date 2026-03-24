<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class VariantResponse implements Response
{
    public function __construct(
        public string $type,
        public Variant $variantResponse,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? ''),
            variantResponse: Variant::fromArray(
                is_array($data['variantResponse'] ?? null)
                    ? $data['variantResponse']
                    : [],
            ),
        );
    }
}
