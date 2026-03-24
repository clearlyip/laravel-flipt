<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class EvaluationDistribution
{
    public function __construct(
        public string $ruleId,
        public string $variantKey,
        public string $variantAttachment,
        public float $rollout,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ruleId: (string) ($data['ruleId'] ?? ''),
            variantKey: (string) ($data['variantKey'] ?? ''),
            variantAttachment: (string) ($data['variantAttachment'] ?? ''),
            rollout: is_numeric($data['rollout'] ?? null)
                ? (float) $data['rollout']
                : 0.0,
        );
    }
}
