<?php

use Clearlyip\LaravelFlipt\Models\FlagDefinition;
use Clearlyip\LaravelFlipt\Models\FlagDefinitionList;

function flagListResponseData(): array
{
    return [
        'flags' => [
            [
                'key' => 'my-flag',
                'type' => 'BOOLEAN_FLAG_TYPE',
                'name' => 'My Flag',
                'description' => 'A feature flag',
                'enabled' => true,
                'variants' => [],
                'rules' => [],
                'rollouts' => [],
                'defaultVariant' => '',
                'metadata' => [],
            ],
        ],
        'nextPageToken' => '',
        'totalCount' => 1,
    ];
}

describe('Flags::list', function () {
    it('returns a FlagDefinitionList', function () {
        $flipt = makeFliptClient([jsonResponse(flagListResponseData())]);

        $result = $flipt->flags->list();

        expect($result)->toBeInstanceOf(FlagDefinitionList::class);
        expect($result->flags)->toHaveCount(1);
        expect($result->flags[0])->toBeInstanceOf(FlagDefinition::class);
        expect($result->flags[0]->key)->toBe('my-flag');
        expect($result->flags[0]->enabled)->toBeTrue();
        expect($result->totalCount)->toBe(1);
    });

    it('calls GET /api/v1/namespaces/{key}/flags', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            flagListResponseData(),
        )], $captured);

        $flipt->flags->list();

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v1/namespaces/default/flags');
    });

    it('uses a custom namespace key when provided', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            flagListResponseData(),
        )], $captured);

        $flipt->flags->list(namespaceKey: 'my-namespace');

        expect($captured[0]->getUri()->getPath())
            ->toBe('/api/v1/namespaces/my-namespace/flags');
    });

    it('appends pagination query params', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            flagListResponseData(),
        )], $captured);

        $flipt->flags->list(
            limit: 10,
            offset: 20,
            pageToken: 'tok123',
        );

        $query = $captured[0]->getUri()->getQuery();
        expect($query)->toContain('limit=10');
        expect($query)->toContain('offset=20');
        expect($query)->toContain('pageToken=tok123');
    });

    it('appends reference and environmentKey query params', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            flagListResponseData(),
        )], $captured);

        $flipt->flags->list(
            reference: 'main',
            environmentKey: 'prod',
        );

        $query = $captured[0]->getUri()->getQuery();
        expect($query)->toContain('reference=main');
        expect($query)->toContain('environmentKey=prod');
    });
});
