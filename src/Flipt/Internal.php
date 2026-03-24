<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\EvaluationNamespaceSnapshot;

readonly class Internal
{
    public function __construct(
        public Flipt $client,
    ) {
    }

    /**
     * Get the evaluation snapshot for the current namespace.
     *
     * @param string|null $reference Optional reference.
     * @param string|null $environmentKey Optional environment key.
     *
     * @return EvaluationNamespaceSnapshot
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function snapshot(
        ?string $reference = null,
        ?string $environmentKey = null,
    ): EvaluationNamespaceSnapshot {
        $query = array_filter(
            [
                'reference' => $reference,
                'environmentKey' => $environmentKey,
            ],
            static fn($v) => $v !== null,
        );

        $path =
            '/internal/v1/evaluation/snapshot/namespace/'
            . urlencode($this->client->namespace);
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
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
}
