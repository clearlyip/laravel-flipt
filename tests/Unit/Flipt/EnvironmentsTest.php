<?php

use Clearlyip\LaravelFlipt\Models\BranchEnvironment;
use Clearlyip\LaravelFlipt\Models\Change;
use Clearlyip\LaravelFlipt\Models\DeleteNamespaceResponse;
use Clearlyip\LaravelFlipt\Models\DeleteResourceResponse;
use Clearlyip\LaravelFlipt\Models\Environment;
use Clearlyip\LaravelFlipt\Models\EnvironmentProposalDetails;
use Clearlyip\LaravelFlipt\Models\FliptNamespace;
use Clearlyip\LaravelFlipt\Models\ListBranchedEnvironmentChangesResponse;
use Clearlyip\LaravelFlipt\Models\ListEnvironmentBranchesResponse;
use Clearlyip\LaravelFlipt\Models\ListEnvironmentsResponse;
use Clearlyip\LaravelFlipt\Models\ListNamespacesResponse;
use Clearlyip\LaravelFlipt\Models\ListResourcesResponse;
use Clearlyip\LaravelFlipt\Models\NamespaceResponse;
use Clearlyip\LaravelFlipt\Models\Resource;
use Clearlyip\LaravelFlipt\Models\ResourceResponse;

function environmentData(string $key = 'production'): array
{
    return [
        'key' => $key,
        'name' => ucfirst($key),
        'default' => $key === 'production',
    ];
}

function branchData(
    string $envKey = 'production',
    string $key = 'my-branch',
): array {
    return [
        'environmentKey' => $envKey,
        'key' => $key,
        'ref' => 'refs/heads/' . $key,
    ];
}

function namespaceData(string $key = 'default'): array
{
    return [
        'key' => $key,
        'name' => ucfirst($key),
        'description' => 'A namespace',
        'protected' => false,
    ];
}

function resourceData(): array
{
    return [
        'namespaceKey' => 'default',
        'key' => 'my-flag',
        'payload' => ['@type' => 'flipt.core.Flag', 'key' => 'my-flag'],
    ];
}

describe('Environments::list', function () {
    it('returns a ListEnvironmentsResponse', function () {
        $flipt = makeFliptClient([jsonResponse(['environments' => [environmentData()]])]);

        $result = $flipt->environments->list();

        expect($result)->toBeInstanceOf(ListEnvironmentsResponse::class);
        expect($result->environments)->toHaveCount(1);
        expect($result->environments[0])->toBeInstanceOf(Environment::class);
        expect($result->environments[0]->key)->toBe('production');
    });

    it('calls GET /api/v2/environments', function () {
        $captured = [];
        $flipt = makeFliptClient(
            [jsonResponse(['environments' => []])],
            $captured,
        );

        $flipt->environments->list();

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())->toBe('/api/v2/environments');
    });
});

describe('Environments::listBranches', function () {
    it('returns a ListEnvironmentBranchesResponse', function () {
        $flipt = makeFliptClient([jsonResponse(['branches' => [branchData()]])]);

        $result = $flipt->environments->listBranches('production');

        expect($result)->toBeInstanceOf(ListEnvironmentBranchesResponse::class);
        expect($result->branches)->toHaveCount(1);
        expect($result->branches[0])->toBeInstanceOf(BranchEnvironment::class);
        expect($result->branches[0]->key)->toBe('my-branch');
    });

    it('calls GET /api/v2/environments/{key}/branches', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['branches' => []])], $captured);

        $flipt->environments->listBranches('production');

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/branches');
    });
});

describe('Environments::createBranch', function () {
    it('returns an Environment', function () {
        $flipt = makeFliptClient([jsonResponse(environmentData(
            'production/my-branch',
        ))]);

        $result = $flipt->environments->createBranch('production', 'my-branch');

        expect($result)->toBeInstanceOf(Environment::class);
    });

    it('posts to /api/v2/environments/{key}/branches', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(environmentData())], $captured);

        $flipt->environments->createBranch('production', 'my-branch');

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/branches');
    });

    it('sends environmentKey and key in the body', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(environmentData())], $captured);

        $flipt->environments->createBranch('production', 'my-branch');

        $body = json_decode((string) $captured[0]->getBody(), true);
        expect($body['environmentKey'])->toBe('production');
        expect($body['key'])->toBe('my-branch');
    });
});

describe('Environments::proposeBranch', function () {
    it('returns an EnvironmentProposalDetails', function () {
        $flipt = makeFliptClient([jsonResponse([
            'url' => 'https://github.com/org/repo/pull/1',
            'state' => 'PROPOSAL_STATE_OPEN',
        ])]);

        $result = $flipt->environments->proposeBranch(
            'production',
            'my-branch',
            'My PR',
            'Description',
        );

        expect($result)->toBeInstanceOf(EnvironmentProposalDetails::class);
        expect($result->url)->toBe('https://github.com/org/repo/pull/1');
        expect($result->state->value)->toBe('PROPOSAL_STATE_OPEN');
    });

    it('posts to /api/v2/environments/{env}/branches/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'url' => 'https://github.com/org/repo/pull/1',
            'state' => 'PROPOSAL_STATE_OPEN',
        ])], $captured);

        $flipt->environments->proposeBranch('production', 'my-branch');

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/branches/my-branch');
    });
});

describe('Environments::deleteBranch', function () {
    it('calls DELETE /api/v2/environments/{env}/branches/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([])], $captured);

        $flipt->environments->deleteBranch('production', 'my-branch');

        expect($captured[0]->getMethod())->toBe('DELETE');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/branches/my-branch');
    });
});

describe('Environments::listBranchChanges', function () {
    it('returns a ListBranchedEnvironmentChangesResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'changes' => [
                [
                    'revision' => 'abc123',
                    'message' => 'feat: add flag',
                    'authorName' => 'Alice',
                    'authorEmail' => 'alice@example.com',
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'scmUrl' => 'https://github.com/org/repo/commit/abc123',
                ],
            ],
        ])]);

        $result = $flipt->environments->listBranchChanges(
            'production',
            'my-branch',
        );

        expect(
            $result,
        )->toBeInstanceOf(ListBranchedEnvironmentChangesResponse::class);
        expect($result->changes)->toHaveCount(1);
        expect($result->changes[0])->toBeInstanceOf(Change::class);
        expect($result->changes[0]->revision)->toBe('abc123');
        expect($result->changes[0]->authorName)->toBe('Alice');
    });

    it('calls GET /api/v2/environments/{env}/branches/{key}/changes', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['changes' => []])], $captured);

        $flipt->environments->listBranchChanges('production', 'my-branch');

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe(
                '/api/v2/environments/production/branches/my-branch/changes',
            );
    });

    it('appends from and limit query params', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['changes' => []])], $captured);

        $flipt->environments->listBranchChanges(
            'production',
            'my-branch',
            from: 'deadbeef',
            limit: 5,
        );

        $query = $captured[0]->getUri()->getQuery();
        expect($query)->toContain('from=deadbeef');
        expect($query)->toContain('limit=5');
    });
});

describe('Environments::listNamespaces', function () {
    it('returns a ListNamespacesResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'items' => [namespaceData()],
            'revision' => 'rev-1',
        ])]);

        $result = $flipt->environments->listNamespaces('production');

        expect($result)->toBeInstanceOf(ListNamespacesResponse::class);
        expect($result->items)->toHaveCount(1);
        expect($result->items[0])->toBeInstanceOf(FliptNamespace::class);
        expect($result->items[0]->key)->toBe('default');
        expect($result->revision)->toBe('rev-1');
    });

    it('calls GET /api/v2/environments/{env}/namespaces', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'items' => [],
            'revision' => '',
        ])], $captured);

        $flipt->environments->listNamespaces('production');

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/namespaces');
    });
});

describe('Environments::createNamespace', function () {
    it('returns a NamespaceResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'namespace' => namespaceData('my-ns'),
            'revision' => 'rev-1',
        ])]);

        $result = $flipt->environments->createNamespace(
            'production',
            'my-ns',
            'My Namespace',
        );

        expect($result)->toBeInstanceOf(NamespaceResponse::class);
        expect($result->namespace->key)->toBe('my-ns');
        expect($result->revision)->toBe('rev-1');
    });

    it('posts to /api/v2/environments/{env}/namespaces', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'namespace' => namespaceData(),
            'revision' => '',
        ])], $captured);

        $flipt->environments->createNamespace(
            'production',
            'my-ns',
            'My Namespace',
        );

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/namespaces');

        $body = json_decode((string) $captured[0]->getBody(), true);
        expect($body['key'])->toBe('my-ns');
        expect($body['name'])->toBe('My Namespace');
    });
});

describe('Environments::updateNamespace', function () {
    it('returns a NamespaceResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'namespace' => namespaceData('my-ns'),
            'revision' => 'rev-2',
        ])]);

        $result = $flipt->environments->updateNamespace(
            'production',
            'my-ns',
            'Updated Name',
        );

        expect($result)->toBeInstanceOf(NamespaceResponse::class);
    });

    it('puts to /api/v2/environments/{env}/namespaces', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'namespace' => namespaceData(),
            'revision' => '',
        ])], $captured);

        $flipt->environments->updateNamespace('production', 'my-ns', 'Updated');

        expect($captured[0]->getMethod())->toBe('PUT');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/namespaces');
    });
});

describe('Environments::getNamespace', function () {
    it('returns a NamespaceResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'namespace' => namespaceData('default'),
            'revision' => 'rev-1',
        ])]);

        $result = $flipt->environments->getNamespace('production', 'default');

        expect($result)->toBeInstanceOf(NamespaceResponse::class);
        expect($result->namespace->key)->toBe('default');
    });

    it('calls GET /api/v2/environments/{env}/namespaces/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'namespace' => namespaceData(),
            'revision' => '',
        ])], $captured);

        $flipt->environments->getNamespace('production', 'default');

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/namespaces/default');
    });
});

describe('Environments::deleteNamespace', function () {
    it('returns a DeleteNamespaceResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'revision' => 'rev-after-delete',
        ])]);

        $result = $flipt->environments->deleteNamespace('production', 'my-ns');

        expect($result)->toBeInstanceOf(DeleteNamespaceResponse::class);
        expect($result->revision)->toBe('rev-after-delete');
    });

    it('calls DELETE /api/v2/environments/{env}/namespaces/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['revision' => ''])], $captured);

        $flipt->environments->deleteNamespace('production', 'my-ns');

        expect($captured[0]->getMethod())->toBe('DELETE');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v2/environments/production/namespaces/my-ns');
    });

    it('appends revision query param when provided', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['revision' => ''])], $captured);

        $flipt->environments->deleteNamespace(
            'production',
            'my-ns',
            revision: 'rev-xyz',
        );

        expect($captured[0]->getUri()->getQuery())
            ->toContain('revision=rev-xyz');
    });
});

describe('Environments::listResources', function () {
    it('returns a ListResourcesResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'resources' => [resourceData()],
            'revision' => 'rev-1',
        ])]);

        $result = $flipt->environments->listResources(
            'production',
            'default',
            'flipt.core.Flag',
        );

        expect($result)->toBeInstanceOf(ListResourcesResponse::class);
        expect($result->resources)->toHaveCount(1);
        expect($result->resources[0])->toBeInstanceOf(Resource::class);
        expect($result->resources[0]->key)->toBe('my-flag');
    });

    it('calls GET /api/v2/environments/{env}/namespaces/{ns}/resources/{type}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'resources' => [],
            'revision' => '',
        ])], $captured);

        $flipt->environments->listResources(
            'production',
            'default',
            'flipt.core.Flag',
        );

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe(
                '/api/v2/environments/production/namespaces/default/resources/flipt.core.Flag',
            );
    });
});

describe('Environments::getResource', function () {
    it('returns a ResourceResponse', function () {
        $flipt = makeFliptClient([jsonResponse([
            'resource' => resourceData(),
            'revision' => 'rev-1',
        ])]);

        $result = $flipt->environments->getResource(
            'production',
            'default',
            'flipt.core.Flag',
            'my-flag',
        );

        expect($result)->toBeInstanceOf(ResourceResponse::class);
        expect($result->resource->key)->toBe('my-flag');
        expect($result->revision)->toBe('rev-1');
    });

    it('calls GET /api/v2/environments/{env}/namespaces/{ns}/resources/{type}/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'resource' => resourceData(),
            'revision' => '',
        ])], $captured);

        $flipt->environments->getResource(
            'production',
            'default',
            'flipt.core.Flag',
            'my-flag',
        );

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe(
                '/api/v2/environments/production/namespaces/default/resources/flipt.core.Flag/my-flag',
            );
    });
});

describe('Environments::createResource', function () {
    it('returns a ResourceResponse', function () {
        $payload = ['@type' => 'flipt.core.Flag', 'key' => 'new-flag'];
        $flipt = makeFliptClient([jsonResponse([
            'resource' => [
                'namespaceKey' => 'default',
                'key' => 'new-flag',
                'payload' => $payload,
            ],
            'revision' => 'rev-1',
        ])]);

        $result = $flipt->environments->createResource(
            'production',
            'default',
            'new-flag',
            $payload,
        );

        expect($result)->toBeInstanceOf(ResourceResponse::class);
        expect($result->resource->key)->toBe('new-flag');
    });

    it('posts to /api/v2/environments/{env}/namespaces/{ns}/resources', function () {
        $captured = [];
        $payload = ['@type' => 'flipt.core.Flag', 'key' => 'new-flag'];
        $flipt = makeFliptClient([jsonResponse([
            'resource' => resourceData(),
            'revision' => '',
        ])], $captured);

        $flipt->environments->createResource(
            'production',
            'default',
            'new-flag',
            $payload,
        );

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())
            ->toBe(
                '/api/v2/environments/production/namespaces/default/resources',
            );
    });
});

describe('Environments::updateResource', function () {
    it('returns a ResourceResponse', function () {
        $payload = [
            '@type' => 'flipt.core.Flag',
            'key' => 'my-flag',
            'enabled' => true,
        ];
        $flipt = makeFliptClient([jsonResponse([
            'resource' => resourceData(),
            'revision' => 'rev-2',
        ])]);

        $result = $flipt->environments->updateResource(
            'production',
            'default',
            'my-flag',
            $payload,
        );

        expect($result)->toBeInstanceOf(ResourceResponse::class);
    });

    it('puts to /api/v2/environments/{env}/namespaces/{ns}/resources', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'resource' => resourceData(),
            'revision' => '',
        ])], $captured);

        $flipt->environments->updateResource(
            'production',
            'default',
            'my-flag',
            [],
        );

        expect($captured[0]->getMethod())->toBe('PUT');
    });
});

describe('Environments::deleteResource', function () {
    it('returns a DeleteResourceResponse', function () {
        $flipt = makeFliptClient([jsonResponse(['revision' => 'rev-after'])]);

        $result = $flipt->environments->deleteResource(
            'production',
            'default',
            'flipt.core.Flag',
            'my-flag',
        );

        expect($result)->toBeInstanceOf(DeleteResourceResponse::class);
        expect($result->revision)->toBe('rev-after');
    });

    it('calls DELETE /api/v2/environments/{env}/namespaces/{ns}/resources/{type}/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['revision' => ''])], $captured);

        $flipt->environments->deleteResource(
            'production',
            'default',
            'flipt.core.Flag',
            'my-flag',
        );

        expect($captured[0]->getMethod())->toBe('DELETE');
        expect($captured[0]->getUri()->getPath())
            ->toBe(
                '/api/v2/environments/production/namespaces/default/resources/flipt.core.Flag/my-flag',
            );
    });

    it('appends revision query param when provided', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['revision' => ''])], $captured);

        $flipt->environments->deleteResource(
            'production',
            'default',
            'flipt.core.Flag',
            'my-flag',
            revision: 'rev-xyz',
        );

        expect($captured[0]->getUri()->getQuery())
            ->toContain('revision=rev-xyz');
    });
});
