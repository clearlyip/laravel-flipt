<?php

use Clearlyip\LaravelFlipt\Models\EvaluationFlag;
use Clearlyip\LaravelFlipt\Models\EvaluationNamespace;
use Clearlyip\LaravelFlipt\Models\EvaluationNamespaceSnapshot;

function clientSnapshotResponseData(string $namespaceKey = 'default'): array
{
    return [
        'namespace' => ['key' => $namespaceKey],
        'flags' => [
            [
                'key' => 'feature-x',
                'name' => 'Feature X',
                'description' => 'A test feature',
                'enabled' => true,
                'type' => 'BOOLEAN_FLAG_TYPE',
                'createdAt' => '2024-01-01T00:00:00Z',
                'updatedAt' => '2024-01-02T00:00:00Z',
                'rules' => [],
                'rollouts' => [],
            ],
        ],
        'digest' => 'sha256:abc123',
    ];
}

describe('ClientEvaluation::snapshot', function () {
    it('returns an EvaluationNamespaceSnapshot', function () {
        $flipt = makeFliptClient([jsonResponse(clientSnapshotResponseData())]);

        $result = $flipt->clientevaluation->snapshot('production', 'default');

        expect($result)->toBeInstanceOf(EvaluationNamespaceSnapshot::class);
        expect($result->namespace)->toBeInstanceOf(EvaluationNamespace::class);
        expect($result->namespace->key)->toBe('default');
        expect($result->digest)->toBe('sha256:abc123');
        expect($result->flags)->toHaveCount(1);
        expect($result->flags[0])->toBeInstanceOf(EvaluationFlag::class);
        expect($result->flags[0]->key)->toBe('feature-x');
        expect($result->flags[0]->enabled)->toBeTrue();
    });

    it('calls GET /client/v2/environments/{env}/namespaces/{key}/snapshot', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            clientSnapshotResponseData(),
        )], $captured);

        $flipt->clientevaluation->snapshot('production', 'default');

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe(
                '/client/v2/environments/production/namespaces/default/snapshot',
            );
    });

    it('appends reference query param when provided', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            clientSnapshotResponseData(),
        )], $captured);

        $flipt->clientevaluation->snapshot(
            'production',
            'default',
            reference: 'v1.2.3',
        );

        expect($captured[0]->getUri()->getQuery())
            ->toContain('reference=v1.2.3');
    });
});

describe('ClientEvaluation::stream', function () {
    it('returns an EvaluationNamespaceSnapshot', function () {
        $flipt = makeFliptClient([jsonResponse(clientSnapshotResponseData(
            'staging',
        ))]);

        $result = $flipt->clientevaluation->stream('staging', 'my-ns');

        expect($result)->toBeInstanceOf(EvaluationNamespaceSnapshot::class);
        expect($result->namespace->key)->toBe('staging');
    });

    it('calls GET /client/v2/environments/{env}/namespaces/{key}/stream', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            clientSnapshotResponseData(),
        )], $captured);

        $flipt->clientevaluation->stream('staging', 'my-ns');

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/client/v2/environments/staging/namespaces/my-ns/stream');
    });
});
