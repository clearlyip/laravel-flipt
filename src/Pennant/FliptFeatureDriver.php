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
use Clearlyip\LaravelFlipt\Models\FlagDefinition;
use Clearlyip\LaravelFlipt\Models\Variant;
use Clearlyip\LaravelFlipt\Models\VariantResponse;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use JsonException;
use Laravel\Pennant\Contracts\DefinesFeaturesExternally;
use Laravel\Pennant\Contracts\Driver;
use Psr\Http\Client\ClientExceptionInterface;

class FliptFeatureDriver implements Driver, DefinesFeaturesExternally
{
    public function __construct(
        protected Flipt $client,
        protected Dispatcher $events,
    ) {
    }

    /**
     * {@inheritDoc}
     * @throws BadMethodCallException
     */
    #[\Override]
    public function define(string $feature, callable $resolver): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     *
     * Returns the keys of all flags defined in the configured Flipt namespace.
     *
     * If Flipt cannot be reached or returns an unparseable response, this
     * degrades gracefully to an empty list (treated as "all features off")
     * rather than propagating an exception and taking down the request.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    #[\Override]
    public function defined(): array
    {
        try {
            $list = $this->client->flags->list();
        } catch (JsonException|ClientExceptionInterface|\ValueError $e) {
            Log::warning('Unable to list Flipt features', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        return array_map(
            static fn(FlagDefinition $flag) => $flag->key,
            $list->flags,
        );
    }

    /**
     * {@inheritDoc}
     *
     * If Flipt cannot be reached or returns an unparseable response, the
     * input features are returned unchanged (safe default / all features off)
     * rather than propagating an exception.
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    #[\Override]
    public function getAll(array $features): array
    {
        try {
            return $this->resolveAll($features);
        } catch (JsonException|ClientExceptionInterface $e) {
            Log::warning('Unable to evaluate Flipt features', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $features;
        }
    }

    /**
     * Resolves every requested feature via a single Flipt batch call and
     * returns the resolved values keyed by feature and scope index.
     *
     * @param array<string, array<int, mixed>> $features
     *
     * @return array<string, array<int, mixed>>
     * @throws \JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws Exception
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    private function resolveAll(array $features): array
    {
        $requests = Collection::make($features)->map(fn(
            $scopes,
            $feature,
        ) => Collection::make($scopes)->map(fn(
            $scope,
            $reference,
        ): EvaluationRequest => $this->makeEvaluationRequest(
            feature: $feature,
            scope: $scope,
            requestId: (string) $reference,
        )))->flatten();

        /** @var EvaluationRequest[] $requestArray */
        $requestArray = $requests->all();
        $responses = $this->client->evaluate->batch($requestArray)->responses;

        foreach ($responses as $response) {
            if ($response instanceof ErrorResponse) {
                $flagKey = $response->errorResponse->flagKey;
                foreach (array_keys($features[$flagKey]) as $idx) {
                    $features[$flagKey][$idx] = $this->getValueFromType($response->errorResponse);
                }
                continue;
            }

            if ($response instanceof BooleanResponse) {
                $flagKey = $response->booleanResponse->flagKey;
                $requestId = (int) $response->booleanResponse->requestId;
                $features[$flagKey][$requestId] = $this->getValueFromType($response->booleanResponse);
                continue;
            }

            if ($response instanceof VariantResponse) {
                $flagKey = $response->variantResponse->flagKey;
                $requestId = (int) $response->variantResponse->requestId;
                $features[$flagKey][$requestId] = $this->getValueFromType($response->variantResponse);
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
     *
     * If Flipt cannot be reached, returns an unparseable response, or returns
     * no evaluation for the feature, the feature is treated as "off" (null)
     * rather than propagating an exception.
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    #[\Override]
    public function get(string $feature, mixed $scope): mixed
    {
        try {
            $responses = $this->client->evaluate->batch([
                $this->makeEvaluationRequest(
                    feature: $feature,
                    scope: $scope,
                ),
            ])->responses;

            $response = $responses[0] ?? null;

            return match (true) {
                $response instanceof ErrorResponse
                    => $this->getValueFromType($response->errorResponse),
                $response instanceof BooleanResponse
                    => $this->getValueFromType($response->booleanResponse),
                $response instanceof VariantResponse
                    => $this->getValueFromType($response->variantResponse),
                $response === null => null,
                default => throw new Exception(
                    'Unknown response type: ' . get_class($response),
                ),
            };
        } catch (JsonException|ClientExceptionInterface $e) {
            Log::warning("Unable to evaluate Flipt feature '{$feature}'", [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * {@inheritDoc}
     * @throws BadMethodCallException
     */
    #[\Override]
    public function set(string $feature, mixed $scope, mixed $value): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     * @throws BadMethodCallException
     */
    #[\Override]
    public function setForAllScopes(string $feature, mixed $value): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     * @throws BadMethodCallException
     */
    #[\Override]
    public function delete(string $feature, mixed $scope): void
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     *
     * Flushes all cached Flipt evaluation results. Since cache entries are
     * keyed by entity rather than by feature, the entire Flipt cache is
     * cleared regardless of which features are requested.
     * @throws BadMethodCallException
     */
    #[\Override]
    public function purge(?array $features): void
    {
        if ($this->client->shouldCache() && $this->client->cache !== null) {
            $this->client->cache->tags($this->client->getCacheTags())->flush();
        }
    }

    /**
     * {@inheritDoc}
     *
     * Returns the keys of all flags defined in the Flipt namespace. Flag
     * availability in Flipt is not scope-specific, so the same list is
     * returned for any scope.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    #[\Override]
    public function definedFeaturesForScope(mixed $scope): array
    {
        return array_values($this->defined());
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
        mixed $scope,
        ?string $reference = null,
        ?string $requestId = null,
    ): EvaluationRequest {
        /** @var mixed $identityConfig */
        $identityConfig = config('flipt.identity');
        /** @var array{identifier: string, context: array<string, string>} $identity */
        $identity = is_array($identityConfig) ? $identityConfig : [];

        if ($scope === null) {
            return new EvaluationRequest(
                flagKey: $feature,
                entityId: 'anon',
                context: [],
                requestId: $requestId,
                reference: $reference,
            );
        }

        $entityId = (string) data_get($scope, $identity['identifier']);
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
     * If the response is a Variant, it will return the variantAttachment.
     *
     * If the response is of any other type, it will throw an exception.
     *
     * @param \Clearlyip\LaravelFlipt\Models\Error|\Clearlyip\LaravelFlipt\Models\Boolean|\Clearlyip\LaravelFlipt\Models\Variant $response The response from the Flipt API
     * @return mixed The value from the response
     * @throws Exception If the response is an Error or a VariantResponse
     */
    private function getValueFromType(Error|Boolean|Variant $response): mixed
    {
        if ($response instanceof Error) {
            if (
                $response->reason
                === ErrorReason::NOT_FOUND_ERROR_EVALUATION_REASON
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

        return $response->variantAttachment;
    }
}
