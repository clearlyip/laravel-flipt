<?php

use Clearlyip\LaravelFlipt\Models\EvaluationNamespaceSnapshot;

function snapshotResponseData(): array
{
    return [
        'namespace' => ['key' => 'default'],
        'flags' => [],
        'digest' => 'abc123',
    ];
}

describe('Internal::snapshot', function () {
    it('returns an EvaluationNamespaceSnapshot', function () {
        $flipt = makeFliptClient([jsonResponse(snapshotResponseData())]);

        $result = $flipt->internal->snapshot();

        expect($result)->toBeInstanceOf(EvaluationNamespaceSnapshot::class);
        expect($result->namespace->key)->toBe('default');
        expect($result->digest)->toBe('abc123');
        expect($result->flags)->toBe([]);
    });

    it('calls GET /internal/v1/evaluation/snapshot/namespace/{key}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            snapshotResponseData(),
        )], $captured);

        $flipt->internal->snapshot();

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/internal/v1/evaluation/snapshot/namespace/default');
    });

    it('appends reference query param when provided', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            snapshotResponseData(),
        )], $captured);

        $flipt->internal->snapshot(reference: 'my-ref');

        expect($captured[0]->getUri()->getQuery())
            ->toContain('reference=my-ref');
    });

    it('appends environmentKey query param when provided', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            snapshotResponseData(),
        )], $captured);

        $flipt->internal->snapshot(environmentKey: 'production');

        expect($captured[0]->getUri()->getQuery())
            ->toContain('environmentKey=production');
    });
});
