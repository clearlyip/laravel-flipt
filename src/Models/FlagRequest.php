<?php

namespace Clearlyip\LaravelFlipt\Models;

use Illuminate\Contracts\Support\Arrayable;

/**
 * EvaluationRequest
 * @implements Arrayable<string,mixed>
 */
readonly class FlagRequest implements Arrayable
{
    public function __construct(
        public string $entityId,
        public array $context = [],
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'entityId' => $this->entityId,
            'context' => array_map(
                fn($value) => match (gettype($value)) {
                    'string' => $value,
                    'integer' => (string) $value,
                    'double' => (string) $value,
                    'boolean' => $value === true ? '1' : '0',
                    default => throw new \DomainException(
                        'Unsupported type: ' . gettype($value),
                    ),
                },
                $this->context,
            ),
        ];
    }

    /**
     * Convert the FlagRequest object to an associative array that can be used as the body of a request to the Flipt API.
     *
     * @return array The associative array representation of the FlagRequest object.
     */
    public function toBody(): array
    {
        $array = $this->toArray();
        unset($array['entityId']);
        return [
            'context' => [
                'targetingKey' => $this->entityId,
                ...$array,
            ],
        ];
    }
}
