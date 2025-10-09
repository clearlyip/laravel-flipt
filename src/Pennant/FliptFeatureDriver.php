<?php

namespace Clearlyip\LaravelFlipt\Pennant;

use BadMethodCallException;
use Clearlyip\LaravelFlipt\Enums\ErrorReason;
use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\Boolean;
use Clearlyip\LaravelFlipt\Models\BooleanResponse;
use Clearlyip\LaravelFlipt\Models\Error;
use Clearlyip\LaravelFlipt\Models\ErrorResponse;
use Clearlyip\LaravelFlipt\Models\EvaluationRequest;
use Clearlyip\LaravelFlipt\Models\Variant;
use Clearlyip\LaravelFlipt\Models\VariantResponse;
use Exception;
use Laravel\Pennant\Contracts\Driver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Laravel\Pennant\Contracts\DefinesFeaturesExternally;

class FliptFeatureDriver implements Driver, DefinesFeaturesExternally
{
    public function __construct(
        protected Flipt $client,
        protected Dispatcher $events,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function define(string $feature, callable $resolver): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function defined(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $features): array
    {
        $requests = Collection::make($features)
            ->map(
                fn($scopes, $feature) => Collection::make($scopes)->map(
                    fn(
                        $scope,
                        $reference,
                    ): EvaluationRequest => $this->makeEvaluationRequest(
                        feature: $feature,
                        scope: $scope,
                        requestId: (string) $reference,
                    ),
                ),
            )
            ->flatten();

        $responses = $this->client->evaluate->batch($requests->all())
            ->responses;

        foreach ($responses as $response) {
            if ($response instanceof ErrorResponse) {
                $flagKey = $response->errorResponse->flagKey;
                foreach ($features[$flagKey] as &$flag) {
                    $flag = $this->getValueFromType($response->errorResponse);
                }
                continue;
            }

            if ($response instanceof BooleanResponse) {
                $flagKey = $response->booleanResponse->flagKey;
                $requestId = (int) $response->booleanResponse->requestId;
                $features[$flagKey][$requestId] = $this->getValueFromType(
                    $response->booleanResponse,
                );
                continue;
            }

            if ($response instanceof VariantResponse) {
                $flagKey = $response->variantResponse->flagKey;
                $requestId = (int) $response->variantResponse->requestId;
                $features[$flagKey][$requestId] = $this->getValueFromType(
                    $response->variantResponse,
                );
                continue;
            }

            throw new Exception(
                'Unknown response type: ' . get_class($response),
            );
        }

        return $features;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $feature, mixed $scope): mixed
    {
        $responses = $this->client->evaluate->batch([
            $this->makeEvaluationRequest(feature: $feature, scope: $scope),
        ])->responses;

        $response = $responses[0];

        return match (get_class($response)) {
            ErrorResponse::class => $this->getValueFromType(
                $response->errorResponse,
            ),
            BooleanResponse::class => $this->getValueFromType(
                $response->booleanResponse,
            ),
            VariantResponse::class => $this->getValueFromType(
                $response->variantResponse,
            ),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $feature, mixed $scope, mixed $value): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function setForAllScopes(string $feature, mixed $value): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $feature, mixed $scope): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function purge(array|null $features): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function definedFeaturesForScope(mixed $scope): array
    {
        return [];
    }

    /**
     * Creates an EvaluationRequest for the given feature and scope.
     *
     * The entity ID is taken from the given scope using the identifier
     * specified in the 'flipt.identity.identifier' configuration.
     *
     * The context is built from the given scope using the traits specified
     * in the 'flipt.identity.traits' configuration.
     *
     * @param string $feature The feature to evaluate
     * @param mixed $scope The scope to evaluate the feature against
     * @param string|null $reference The reference for the evaluation request
     * @param string|null $requestId The request ID for the evaluation request
     * @return EvaluationRequest The created evaluation request
     */
    private function makeEvaluationRequest(
        string $feature,
        $scope,
        ?string $reference = null,
        ?string $requestId = null,
    ): EvaluationRequest {
        $identity = config('flipt.identity');

        if ($scope === null) {
            return new EvaluationRequest(
                flagKey: $feature,
                entityId: 'anon',
                context: [],
                requestId: $requestId,
                reference: $reference,
            );
        }

        $entityId = data_get($scope, $identity['identifier']);
        $contexts = [];
        foreach ($identity['context'] as $context => $attribute) {
            $contexts[$context] = data_get($scope, $attribute);
        }

        return new EvaluationRequest(
            flagKey: $feature,
            entityId: $entityId,
            context: $contexts,
            requestId: $requestId,
            reference: $reference,
        );
    }

    /**
     * Given a response from the Flipt API, extracts the value from the response
     * into a native PHP type.
     *
     * If the response is an Error, it will return null if the error reason is
     * NOT_FOUND_ERROR_EVALUATION_REASON, otherwise it will throw an exception.
     *
     * If the response is a Boolean, it will return the enabled property of the
     * response.
     *
     * If the response is a VariantResponse, it will throw an exception (not
     * implemented).
     *
     * If the response is of any other type, it will throw an exception.
     *
     * @param Error|Boolean|Variant $response The response from the Flipt API
     * @return mixed The value from the response
     * @throws Exception If the response is an Error or a VariantResponse
     */
    private function getValueFromType(Error|Boolean|Variant $response): mixed
    {
        if ($response instanceof Error) {
            if (
                $response->reason ===
                ErrorReason::NOT_FOUND_ERROR_EVALUATION_REASON
            ) {
                return null;
            }
            throw new Exception(
                "Error evaluating feature {$response->flagKey}",
            );
        }

        if ($response instanceof Boolean) {
            return $response->enabled;
        }

        /**
         * @psalm-suppress RedundantCondition
         */
        if ($response instanceof Variant) {
            return $response->variantAttachment;
        }

        throw new Exception('Unknown response type: ' . get_class($response));
    }
}
