<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\FlagReason;

readonly class Flag
{
    public function __construct(
        public string $key,
        public FlagReason $reason,
        public string $variant,
        public array $metadata,
        public mixed $value,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            reason: FlagReason::from((string) ($data['reason'] ?? '')),
            variant: (string) ($data['variant'] ?? ''),
            metadata: is_array($data['metadata'] ?? null)
                ? $data['metadata']
                : [],
            value: $data['value'] ?? null,
        );
    }
}
