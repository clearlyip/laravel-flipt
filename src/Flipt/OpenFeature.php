<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\Bulk;
use Clearlyip\LaravelFlipt\Models\Configuration;
use Clearlyip\LaravelFlipt\Models\Flag;
use Clearlyip\LaravelFlipt\Models\FlagRequest;

readonly class OpenFeature
{
    public function __construct(
        public Flipt $client,
    ) {
    }

    /**
     * Get the current configuration from the Flipt API.
     *
     * @return Configuration The current configuration.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function configuration(): Configuration
    {
        $response = $this->client->apiRequest(path: '/ofrep/v1/configuration');
        $body = $this->client->decodeResponse($response);
        /** @var Configuration $result */
        /** @var Configuration $mappedConfiguration */
        $mappedConfiguration = $this->client->map(Configuration::class, $body);
        return $mappedConfiguration;
    }

    /**
     * Evaluate a single flag for the given entity and context.
     *
     * @param string $name The name of the flag to evaluate.
     * @param FlagRequest $request The evaluation request context.
     *
     * @return Flag The result of the evaluation.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function flag(string $name, FlagRequest $request): Flag
    {
        $response = $this->client->apiRequest(
            path: '/ofrep/v1/evaluate/flags/' . $name,
            method: 'POST',
            body: $request->toBody(),
            headers: [
                'X-Flipt-Namespace' => $this->client->namespace,
            ],
            cacheTags: ['flipt.' . $request->entityId],
        );

        $body = $this->client->decodeResponse($response);
        /** @var Flag $result */
        /** @var Flag $mappedFlag */
        $mappedFlag = $this->client->map(Flag::class, $body);
        return $mappedFlag;
    }

    /**
     * Evaluate a list of flags for the given entity and context.
     *
     * @param string[] $names The names of the flags to evaluate.
     * @param FlagRequest $request The evaluation request context.
     *
     * @return Bulk The result of the bulk evaluation.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function bulk(array $names, FlagRequest $request): Bulk
    {
        $response = $this->client->apiRequest(
            path: '/ofrep/v1/evaluate/flags',
            method: 'POST',
            body: [
                'context' => [
                    'flags' => $names,
                    ...$request->toArray(),
                ],
            ],
            headers: [
                'X-Flipt-Namespace' => $this->client->namespace,
            ],
            cacheTags: ['flipt.' . $request->entityId],
        );

        $body = $this->client->decodeResponse($response);
        /** @var Bulk $result */
        /** @var Bulk $mappedBulk */
        $mappedBulk = $this->client->map(Bulk::class, $body);
        return $mappedBulk;
    }
}
