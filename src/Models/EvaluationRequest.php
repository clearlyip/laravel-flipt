<?php

namespace Clearlyip\LaravelFlipt\Models;

use Illuminate\Contracts\Support\Arrayable;

/**
 * EvaluationRequest
 * @implements Arrayable<string,mixed>
 */
readonly class EvaluationRequest implements Arrayable
{
    public function __construct(
        public string $flagKey,
        public string $entityId,
        public array $context = [],
        public ?string $requestId = null,
        public ?string $reference = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'flagKey' => $this->flagKey,
            'entityId' => $this->entityId,
            'context' => array_map(static fn($value) => match (gettype(
                $value,
            )) {
                'string' => $value,
                'integer' => (string) $value,
                'double' => (string) $value,
                'boolean' => $value === true ? '1' : '0',
                default => throw new \DomainException(
                    'Unsupported type: ' . gettype($value),
                ),
            }, $this->context),
            'requestId' => $this->requestId,
            'reference' => $this->reference,
        ];
    }

    public function toBody(string $namespace): array
    {
        return [
            'namespaceKey' => $namespace,
            ...$this->toArray(),
        ];
    }
}
