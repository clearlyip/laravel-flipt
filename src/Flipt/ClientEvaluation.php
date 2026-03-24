<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\EvaluationNamespaceSnapshot;

readonly class ClientEvaluation
{
    public function __construct(
        public Flipt $client,
    ) {
    }

    /**
     * Get the evaluation snapshot for a namespace within a given environment.
     *
     * @param string $environmentKey The environment identifier, e.g. 'production'.
     * @param string $key The namespace key.
     * @param string|null $reference Optional reference.
     *
     * @return EvaluationNamespaceSnapshot
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function snapshot(
        string $environmentKey,
        string $key,
        ?string $reference = null,
    ): EvaluationNamespaceSnapshot {
        $path =
            '/client/v2/environments/'
            . $environmentKey
            . '/namespaces/'
            . $key
            . '/snapshot';

        if ($reference !== null) {
            $path .= '?reference=' . urlencode($reference);
        }

        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var EvaluationNamespaceSnapshot $result */
        /** @var EvaluationNamespaceSnapshot $mappedEvaluationnamespacesnapshot */
        $mappedEvaluationnamespacesnapshot = $this->client->map(
            EvaluationNamespaceSnapshot::class,
            $body,
        );
        return $mappedEvaluationnamespacesnapshot;
    }

    /**
     * Stream evaluation snapshots for a namespace within a given environment.
     *
     * @param string $environmentKey The environment identifier, e.g. 'production'.
     * @param string $key The namespace key.
     *
     * @return EvaluationNamespaceSnapshot
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function stream(
        string $environmentKey,
        string $key,
    ): EvaluationNamespaceSnapshot {
        $path =
            '/client/v2/environments/'
            . $environmentKey
            . '/namespaces/'
            . $key
            . '/stream';
        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var EvaluationNamespaceSnapshot $result */
        /** @var EvaluationNamespaceSnapshot $mappedEvaluationnamespacesnapshot */
        $mappedEvaluationnamespacesnapshot = $this->client->map(
            EvaluationNamespaceSnapshot::class,
            $body,
        );
        return $mappedEvaluationnamespacesnapshot;
    }
}
