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
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            match: array_key_exists('match', $data) && $data['match'] === true,
            segmentKeys: array_map(
                'strval',
                is_array($data['segmentKeys'] ?? null)
                    ? $data['segmentKeys']
                    : [],
            ),
            reason: Reason::from((string) ($data['reason'] ?? '')),
            variantKey: (string) ($data['variantKey'] ?? ''),
            variantAttachment: (string) ($data['variantAttachment'] ?? ''),
            requestId: (string) ($data['requestId'] ?? ''),
            requestDurationMillis: is_numeric(
                $data['requestDurationMillis'] ?? null,
            )
                ? (float) $data['requestDurationMillis']
                : 0.0,
            timestamp: (string) ($data['timestamp'] ?? ''),
            flagKey: (string) ($data['flagKey'] ?? ''),
        );
    }
}
