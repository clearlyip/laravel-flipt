<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class EvaluationNamespaceSnapshot
{
    public function __construct(
        public EvaluationNamespace $namespace,
        /** @var EvaluationFlag[] */
        public array $flags,
        public string $digest,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            namespace: EvaluationNamespace::fromArray(
                is_array($data['namespace'] ?? null) ? $data['namespace'] : [],
            ),
            flags: array_map(
                EvaluationFlag::fromArray(...),
                is_array($data['flags'] ?? null) ? $data['flags'] : [],
            ),
            digest: (string) ($data['digest'] ?? ''),
        );
    }
}
