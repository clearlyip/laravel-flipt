<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\FlagDefinitionList;

readonly class Flags
{
    public function __construct(
        public Flipt $client,
    ) {
    }

    /**
     * List flags for a namespace.
     *
     * @param string|null $namespaceKey The namespace key (defaults to client namespace).
     * @param int|null $limit Maximum number of results to return.
     * @param int|null $offset Offset for pagination.
     * @param string|null $pageToken Page token for cursor-based pagination.
     * @param string|null $reference Optional reference.
     * @param string|null $environmentKey Optional environment key.
     *
     * @return FlagDefinitionList
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function list(
        ?string $namespaceKey = null,
        ?int $limit = null,
        ?int $offset = null,
        #[\SensitiveParameter]
        ?string $pageToken = null,
        ?string $reference = null,
        ?string $environmentKey = null,
    ): FlagDefinitionList {
        $ns = $namespaceKey ?? $this->client->namespace;
        $query = array_filter(
            [
                'limit' => $limit,
                'offset' => $offset,
                'pageToken' => $pageToken,
                'reference' => $reference,
                'environmentKey' => $environmentKey,
            ],
            static fn($v) => $v !== null,
        );

        $path = '/api/v1/namespaces/' . urlencode($ns) . '/flags';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var FlagDefinitionList $result */
        /** @var FlagDefinitionList $mappedFlagdefinitionlist */
        $mappedFlagdefinitionlist = $this->client->map(
            FlagDefinitionList::class,
            $body,
        );
        return $mappedFlagdefinitionlist;
    }
}
