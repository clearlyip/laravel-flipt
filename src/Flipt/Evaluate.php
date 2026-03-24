<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\BatchResponse;
use Clearlyip\LaravelFlipt\Models\Boolean;
use Clearlyip\LaravelFlipt\Models\EvaluationRequest;
use Clearlyip\LaravelFlipt\Models\Variant;
use InvalidArgumentException;

readonly class Evaluate
{
    public function __construct(
        public Flipt $client,
    ) {
    }

    /**
     * Batch return evaluation requests
     *
     * @param EvaluationRequest[] $requests
     *
     * @return BatchResponse
     *
     * @throws \JsonException if request or response includes invalid json data
     * @throws \Psr\Http\Client\ClientExceptionInterface if network or request error occurs
     * @throws \ValueError
     * @throws \InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function batch(
        array $requests,
        ?string $reference = '',
    ): BatchResponse {
        $entityIds = array_unique(array_map(
            static fn(EvaluationRequest $v) => $v->entityId,
            $requests,
        ));

        //TODO(@anagy) technically this *could* be supported but not in open feature
        if (count($entityIds) > 1) {
            throw new InvalidArgumentException(
                'All requests must have the same entity ID',
            );
        }

        $response = $this->client->apiRequest(
            method: 'POST',
            path: '/evaluate/v1/batch',
            body: [
                'requests' => array_map(
                    fn(EvaluationRequest $request) => $request->toBody($this->client->namespace),
                    $requests,
                ),
                'reference' => $reference,
            ],
            cacheTags: $requests !== []
                ? ['flipt.' . $requests[array_key_first($requests)]->entityId]
                : [],
        );

        $body = $this->client->decodeResponse($response);
        /** @var BatchResponse $mappedBatchresponse */
        $mappedBatchresponse = $this->client->map(BatchResponse::class, $body);
        return $mappedBatchresponse;
    }

    /**
     * Evaluate a boolean flag for the given entity and context.
     *
     * @param EvaluationRequest $request
     *
     * @return \Clearlyip\LaravelFlipt\Models\Boolean The result of the evaluation.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function boolean(EvaluationRequest $request): Boolean
    {
        $response = $this->client->apiRequest(
            method: 'POST',
            path: '/evaluate/v1/boolean',
            body: $request->toBody($this->client->namespace),
            cacheTags: ['flipt.' . $request->entityId],
        );

        $body = $this->client->decodeResponse($response);
        /** @var \Clearlyip\LaravelFlipt\Models\Boolean $mappedBoolean */
        $mappedBoolean = $this->client->map(Boolean::class, $body);
        return $mappedBoolean;
    }

    /**
     * Evaluate a variant flag for the given entity and context.
     *
     * @param EvaluationRequest $request
     *
     * @return Variant The result of the evaluation.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function variant(EvaluationRequest $request): Variant
    {
        $response = $this->client->apiRequest(
            method: 'POST',
            path: '/evaluate/v1/variant',
            body: $request->toBody($this->client->namespace),
            cacheTags: ['flipt.' . $request->entityId],
        );

        $body = $this->client->decodeResponse($response);
        /** @var Variant $mappedVariant */
        $mappedVariant = $this->client->map(Variant::class, $body);
        return $mappedVariant;
    }
}
