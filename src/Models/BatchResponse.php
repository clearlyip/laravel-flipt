<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class BatchResponse implements Response
{
    public function __construct(
        public string $requestId,
        /** @var Response[] */
        public array $responses,
        public float $requestDurationMillis,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requestId: (string) ($data['requestId'] ?? ''),
            responses: array_map(
                static function ($r) {
                    $r = is_array($r) ? $r : [];
                    $type = array_key_exists('type', $r)
                        ? (string) $r['type']
                        : '';
                    return match ($type) {
                        'BOOLEAN_EVALUATION_RESPONSE_TYPE'
                            => BooleanResponse::fromArray($r),
                        'VARIANT_EVALUATION_RESPONSE_TYPE'
                            => VariantResponse::fromArray($r),
                        'ERROR_EVALUATION_RESPONSE_TYPE'
                            => ErrorResponse::fromArray($r),
                        default => throw new \DomainException(
                            "Unhandled type `{$type}`.",
                        ),
                    };
                },
                is_array($data['responses'] ?? null) ? $data['responses'] : [],
            ),
            requestDurationMillis: is_numeric(
                $data['requestDurationMillis'] ?? null,
            )
                ? (float) $data['requestDurationMillis']
                : 0.0,
        );
    }
}
