<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\ErrorReason;

readonly class Error
{
    public function __construct(
        public string $flagKey,
        public string $namespaceKey,
        public ErrorReason $reason,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            flagKey: (string) ($data['flagKey'] ?? ''),
            namespaceKey: (string) ($data['namespaceKey'] ?? ''),
            reason: ErrorReason::from((string) ($data['reason'] ?? '')),
        );
    }
}
