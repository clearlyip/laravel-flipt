<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\Reason;

readonly class Boolean
{
    public function __construct(
        public bool $enabled,
        public Reason $reason,
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
            enabled: array_key_exists('enabled', $data)
            && $data['enabled'] === true,
            reason: Reason::from((string) ($data['reason'] ?? '')),
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
