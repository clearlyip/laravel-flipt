<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Models\DeleteNamespaceResponse;
use Clearlyip\LaravelFlipt\Models\DeleteResourceResponse;
use Clearlyip\LaravelFlipt\Models\Environment;
use Clearlyip\LaravelFlipt\Models\EnvironmentProposalDetails;
use Clearlyip\LaravelFlipt\Models\ListBranchedEnvironmentChangesResponse;
use Clearlyip\LaravelFlipt\Models\ListEnvironmentBranchesResponse;
use Clearlyip\LaravelFlipt\Models\ListEnvironmentsResponse;
use Clearlyip\LaravelFlipt\Models\ListNamespacesResponse;
use Clearlyip\LaravelFlipt\Models\ListResourcesResponse;
use Clearlyip\LaravelFlipt\Models\NamespaceResponse;
use Clearlyip\LaravelFlipt\Models\ResourceResponse;

readonly class Environments
{
    public function __construct(
        public Flipt $client,
    ) {
    }

    /**
     * List all environments.
     *
     * @return ListEnvironmentsResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function list(): ListEnvironmentsResponse
    {
        $response = $this->client->apiRequest(
            method: 'GET',
            path: '/api/v2/environments',
        );
        $body = $this->client->decodeResponse($response);
        /** @var ListEnvironmentsResponse $result */
        /** @var ListEnvironmentsResponse $mappedListenvironmentsresponse */
        $mappedListenvironmentsresponse = $this->client->map(
            ListEnvironmentsResponse::class,
            $body,
        );
        return $mappedListenvironmentsresponse;
    }

    /**
     * List all branch environments for a given environment.
     *
     * @param string $environmentKey The environment identifier, e.g. 'production'.
     *
     * @return ListEnvironmentBranchesResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function listBranches(string $environmentKey): ListEnvironmentBranchesResponse
    {
        $path =
            '/api/v2/environments/' . urlencode($environmentKey) . '/branches';
        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ListEnvironmentBranchesResponse $result */
        /** @var ListEnvironmentBranchesResponse $mappedListenvironmentbranchesresponse */
        $mappedListenvironmentbranchesresponse = $this->client->map(
            ListEnvironmentBranchesResponse::class,
            $body,
        );
        return $mappedListenvironmentbranchesresponse;
    }

    /**
     * Create a branch environment.
     *
     * @param string $environmentKey The environment identifier, e.g. 'production'.
     * @param string $key The branch key.
     *
     * @return Environment
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function createBranch(
        string $environmentKey,
        string $key,
    ): Environment {
        $path =
            '/api/v2/environments/' . urlencode($environmentKey) . '/branches';
        $response = $this->client->apiRequest(
            method: 'POST',
            path: $path,
            body: [
                'environmentKey' => $environmentKey,
                'key' => $key,
            ],
        );
        $body = $this->client->decodeResponse($response);
        /** @var Environment $result */
        /** @var Environment $mappedEnvironment */
        $mappedEnvironment = $this->client->map(Environment::class, $body);
        return $mappedEnvironment;
    }

    /**
     * Propose changes from a branch environment.
     *
     * @param string $environmentKey The parent environment identifier, e.g. 'production'.
     * @param string $key The branch key.
     * @param string|null $title The title of the proposal.
     * @param string|null $body The body of the proposal.
     * @param bool|null $draft Whether the proposal is a draft.
     *
     * @return EnvironmentProposalDetails
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \ValueError
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function proposeBranch(
        string $environmentKey,
        string $key,
        ?string $title = null,
        ?string $body = null,
        ?bool $draft = null,
    ): EnvironmentProposalDetails {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/branches/'
            . urlencode($key);
        $requestBody = array_filter(
            [
                'environmentKey' => $environmentKey,
                'key' => $key,
                'title' => $title,
                'body' => $body,
                'draft' => $draft,
            ],
            static fn($v) => $v !== null,
        );

        $response = $this->client->apiRequest(
            method: 'POST',
            path: $path,
            body: $requestBody,
        );
        $responseBody = $this->client->decodeResponse($response);
        /** @var EnvironmentProposalDetails $result */
        /** @var EnvironmentProposalDetails $mappedEnvironmentproposaldetails */
        $mappedEnvironmentproposaldetails = $this->client->map(
            EnvironmentProposalDetails::class,
            $responseBody,
        );
        return $mappedEnvironmentproposaldetails;
    }

    /**
     * Delete a branch environment.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $key The branch key.
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function deleteBranch(string $environmentKey, string $key): void
    {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/branches/'
            . urlencode($key);
        $this->client->apiRequest(
            method: 'DELETE',
            path: $path,
        );
    }

    /**
     * List changes for a branch environment.
     *
     * @param string $environmentKey The parent environment identifier.
     * @param string $key The branch environment key.
     * @param string|null $from The revision to list changes from (exclusive).
     * @param int|null $limit Maximum number of changes to return.
     *
     * @return ListBranchedEnvironmentChangesResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function listBranchChanges(
        string $environmentKey,
        string $key,
        ?string $from = null,
        ?int $limit = null,
    ): ListBranchedEnvironmentChangesResponse {
        $query = array_filter(
            [
                'from' => $from,
                'limit' => $limit,
            ],
            static fn($v) => $v !== null,
        );

        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/branches/'
            . urlencode($key)
            . '/changes';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ListBranchedEnvironmentChangesResponse $result */
        /** @var ListBranchedEnvironmentChangesResponse $mappedListbranchedenvironmentchangesresponse */
        $mappedListbranchedenvironmentchangesresponse = $this->client->map(
            ListBranchedEnvironmentChangesResponse::class,
            $body,
        );
        return $mappedListbranchedenvironmentchangesresponse;
    }

    /**
     * List all namespaces within a given environment.
     *
     * @param string $environmentKey The environment identifier, e.g. 'production'.
     *
     * @return ListNamespacesResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function listNamespaces(string $environmentKey): ListNamespacesResponse
    {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces';
        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ListNamespacesResponse $result */
        /** @var ListNamespacesResponse $mappedListnamespacesresponse */
        $mappedListnamespacesresponse = $this->client->map(
            ListNamespacesResponse::class,
            $body,
        );
        return $mappedListnamespacesresponse;
    }

    /**
     * Create a new namespace within a given environment.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $key The namespace key.
     * @param string $name The namespace name.
     * @param string|null $description The namespace description.
     * @param bool|null $protected The namespace protection status.
     * @param string|null $revision The resource revision for optimistic concurrency.
     *
     * @return NamespaceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function createNamespace(
        string $environmentKey,
        string $key,
        string $name,
        ?string $description = null,
        ?bool $protected = null,
        ?string $revision = null,
    ): NamespaceResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces';
        $requestBody = array_filter(
            [
                'environmentKey' => $environmentKey,
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'protected' => $protected,
                'revision' => $revision,
            ],
            static fn($v) => $v !== null,
        );

        $response = $this->client->apiRequest(
            method: 'POST',
            path: $path,
            body: $requestBody,
        );
        $body = $this->client->decodeResponse($response);
        /** @var NamespaceResponse $result */
        /** @var NamespaceResponse $mappedNamespaceresponse */
        $mappedNamespaceresponse = $this->client->map(
            NamespaceResponse::class,
            $body,
        );
        return $mappedNamespaceresponse;
    }

    /**
     * Update an existing namespace within a given environment.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $key The namespace key.
     * @param string $name The namespace name.
     * @param string|null $description The namespace description.
     * @param bool|null $protected The namespace protection status.
     * @param string|null $revision The resource revision for optimistic concurrency.
     *
     * @return NamespaceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function updateNamespace(
        string $environmentKey,
        string $key,
        string $name,
        ?string $description = null,
        ?bool $protected = null,
        ?string $revision = null,
    ): NamespaceResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces';
        $requestBody = array_filter(
            [
                'environmentKey' => $environmentKey,
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'protected' => $protected,
                'revision' => $revision,
            ],
            static fn($v) => $v !== null,
        );

        $response = $this->client->apiRequest(
            method: 'PUT',
            path: $path,
            body: $requestBody,
        );
        $body = $this->client->decodeResponse($response);
        /** @var NamespaceResponse $result */
        /** @var NamespaceResponse $mappedNamespaceresponse */
        $mappedNamespaceresponse = $this->client->map(
            NamespaceResponse::class,
            $body,
        );
        return $mappedNamespaceresponse;
    }

    /**
     * Get a specific namespace within a given environment.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $key The namespace key.
     *
     * @return NamespaceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function getNamespace(
        string $environmentKey,
        string $key,
    ): NamespaceResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($key);
        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var NamespaceResponse $result */
        /** @var NamespaceResponse $mappedNamespaceresponse */
        $mappedNamespaceresponse = $this->client->map(
            NamespaceResponse::class,
            $body,
        );
        return $mappedNamespaceresponse;
    }

    /**
     * Delete a namespace within a given environment.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $key The namespace key.
     * @param string|null $revision The resource revision for optimistic concurrency.
     *
     * @return DeleteNamespaceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function deleteNamespace(
        string $environmentKey,
        string $key,
        ?string $revision = null,
    ): DeleteNamespaceResponse {
        $query = $revision !== null ? '?revision=' . urlencode($revision) : '';
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($key)
            . $query;
        $response = $this->client->apiRequest(
            method: 'DELETE',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var DeleteNamespaceResponse $result */
        /** @var DeleteNamespaceResponse $mappedDeletenamespaceresponse */
        $mappedDeletenamespaceresponse = $this->client->map(
            DeleteNamespaceResponse::class,
            $body,
        );
        return $mappedDeletenamespaceresponse;
    }

    /**
     * List all resources within a given namespace.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $namespaceKey The namespace key.
     * @param string $typeUrl The resource type, e.g. 'flipt.core.Flag'.
     *
     * @return ListResourcesResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function listResources(
        string $environmentKey,
        string $namespaceKey,
        string $typeUrl,
    ): ListResourcesResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($namespaceKey)
            . '/resources/'
            . urlencode($typeUrl);
        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ListResourcesResponse $result */
        /** @var ListResourcesResponse $mappedListresourcesresponse */
        $mappedListresourcesresponse = $this->client->map(
            ListResourcesResponse::class,
            $body,
        );
        return $mappedListresourcesresponse;
    }

    /**
     * Get a specific resource within a given namespace.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $namespaceKey The namespace key.
     * @param string $typeUrl The resource type, e.g. 'flipt.core.Flag'.
     * @param string $key The resource key.
     *
     * @return ResourceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function getResource(
        string $environmentKey,
        string $namespaceKey,
        string $typeUrl,
        string $key,
    ): ResourceResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($namespaceKey)
            . '/resources/'
            . urlencode($typeUrl)
            . '/'
            . urlencode($key);
        $response = $this->client->apiRequest(
            method: 'GET',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ResourceResponse $result */
        /** @var ResourceResponse $mappedResourceresponse */
        $mappedResourceresponse = $this->client->map(
            ResourceResponse::class,
            $body,
        );
        return $mappedResourceresponse;
    }

    /**
     * Create a new resource within a given namespace.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $namespaceKey The namespace key.
     * @param string $key The resource key.
     * @param array $payload The arbitrary typed resource payload.
     * @param string|null $revision The resource revision for optimistic concurrency.
     *
     * @return ResourceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function createResource(
        string $environmentKey,
        string $namespaceKey,
        string $key,
        array $payload,
        ?string $revision = null,
    ): ResourceResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($namespaceKey)
            . '/resources';
        $requestBody = array_filter(
            [
                'environmentKey' => $environmentKey,
                'namespaceKey' => $namespaceKey,
                'key' => $key,
                'payload' => $payload,
                'revision' => $revision,
            ],
            static fn($v) => $v !== null,
        );

        $response = $this->client->apiRequest(
            method: 'POST',
            path: $path,
            body: $requestBody,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ResourceResponse $result */
        /** @var ResourceResponse $mappedResourceresponse */
        $mappedResourceresponse = $this->client->map(
            ResourceResponse::class,
            $body,
        );
        return $mappedResourceresponse;
    }

    /**
     * Update an existing resource within a given namespace.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $namespaceKey The namespace key.
     * @param string $key The resource key.
     * @param array $payload The arbitrary typed resource payload.
     * @param string|null $revision The resource revision for optimistic concurrency.
     *
     * @return ResourceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function updateResource(
        string $environmentKey,
        string $namespaceKey,
        string $key,
        array $payload,
        ?string $revision = null,
    ): ResourceResponse {
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($namespaceKey)
            . '/resources';
        $requestBody = array_filter(
            [
                'environmentKey' => $environmentKey,
                'namespaceKey' => $namespaceKey,
                'key' => $key,
                'payload' => $payload,
                'revision' => $revision,
            ],
            static fn($v) => $v !== null,
        );

        $response = $this->client->apiRequest(
            method: 'PUT',
            path: $path,
            body: $requestBody,
        );
        $body = $this->client->decodeResponse($response);
        /** @var ResourceResponse $result */
        /** @var ResourceResponse $mappedResourceresponse */
        $mappedResourceresponse = $this->client->map(
            ResourceResponse::class,
            $body,
        );
        return $mappedResourceresponse;
    }

    /**
     * Delete a resource within a given namespace.
     *
     * @param string $environmentKey The environment identifier.
     * @param string $namespaceKey The namespace key.
     * @param string $typeUrl The resource type, e.g. 'flipt.core.Flag'.
     * @param string $key The resource key.
     * @param string|null $revision The resource revision for optimistic concurrency.
     *
     * @return DeleteResourceResponse
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function deleteResource(
        string $environmentKey,
        string $namespaceKey,
        string $typeUrl,
        string $key,
        ?string $revision = null,
    ): DeleteResourceResponse {
        $query = $revision !== null ? '?revision=' . urlencode($revision) : '';
        $path =
            '/api/v2/environments/'
            . urlencode($environmentKey)
            . '/namespaces/'
            . urlencode($namespaceKey)
            . '/resources/'
            . urlencode($typeUrl)
            . '/'
            . urlencode($key)
            . $query;
        $response = $this->client->apiRequest(
            method: 'DELETE',
            path: $path,
        );
        $body = $this->client->decodeResponse($response);
        /** @var DeleteResourceResponse $result */
        /** @var DeleteResourceResponse $mappedDeleteresourceresponse */
        $mappedDeleteresourceresponse = $this->client->map(
            DeleteResourceResponse::class,
            $body,
        );
        return $mappedDeleteresourceresponse;
    }
}
