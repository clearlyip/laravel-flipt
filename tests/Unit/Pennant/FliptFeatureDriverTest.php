<?php

use Clearlyip\LaravelFlipt\Pennant\FliptFeatureDriver;
use Illuminate\Contracts\Events\Dispatcher;

function makeDriver(array $responses = []): FliptFeatureDriver
{
    $captured = [];
    $flipt = makeFliptClient($responses, $captured);
    $events = Mockery::mock(Dispatcher::class);
    return new FliptFeatureDriver($flipt, $events);
}

function batchResponseWithBoolean(
    string $flagKey,
    bool $enabled,
    string $requestId = '0',
): array {
    return [
        'requestId' => 'batch-1',
        'requestDurationMillis' => 0.5,
        'responses' => [
            [
                'type' => 'BOOLEAN_EVALUATION_RESPONSE_TYPE',
                'booleanResponse' => [
                    'enabled' => $enabled,
                    'reason' => 'MATCH_EVALUATION_REASON',
                    'requestId' => $requestId,
                    'requestDurationMillis' => 0.5,
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'flagKey' => $flagKey,
                ],
            ],
        ],
    ];
}

function batchResponseWithVariant(
    string $flagKey,
    string $attachment = '{"key":"value"}',
    string $requestId = '0',
): array {
    return [
        'requestId' => 'batch-1',
        'requestDurationMillis' => 0.5,
        'responses' => [
            [
                'type' => 'VARIANT_EVALUATION_RESPONSE_TYPE',
                'variantResponse' => [
                    'match' => true,
                    'segmentKeys' => [],
                    'reason' => 'MATCH_EVALUATION_REASON',
                    'variantKey' => 'v1',
                    'variantAttachment' => $attachment,
                    'requestId' => $requestId,
                    'requestDurationMillis' => 0.5,
                    'timestamp' => '2024-01-01T00:00:00Z',
                    'flagKey' => $flagKey,
                ],
            ],
        ],
    ];
}

function batchResponseWithError(
    string $flagKey,
    string $reason = 'NOT_FOUND_ERROR_EVALUATION_REASON',
): array {
    return [
        'requestId' => 'batch-1',
        'requestDurationMillis' => 0.5,
        'responses' => [
            [
                'type' => 'ERROR_EVALUATION_RESPONSE_TYPE',
                'errorResponse' => [
                    'flagKey' => $flagKey,
                    'namespaceKey' => 'default',
                    'reason' => $reason,
                ],
            ],
        ],
    ];
}

describe('FliptFeatureDriver::get', function () {
    it('returns true for an enabled boolean flag', function () {
        $driver = makeDriver([jsonResponse(batchResponseWithBoolean(
            'my-flag',
            true,
        ))]);
        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        $result = $driver->get('my-flag', $scope);

        expect($result)->toBeTrue();
    });

    it('returns false for a disabled boolean flag', function () {
        $driver = makeDriver([jsonResponse(batchResponseWithBoolean(
            'my-flag',
            false,
        ))]);
        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        $result = $driver->get('my-flag', $scope);

        expect($result)->toBeFalse();
    });

    it('returns variantAttachment for a variant flag', function () {
        $driver = makeDriver([jsonResponse(batchResponseWithVariant(
            'my-flag',
            '{"plan":"pro"}',
        ))]);
        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        $result = $driver->get('my-flag', $scope);

        expect($result)->toBe('{"plan":"pro"}');
    });

    it('returns null for a NOT_FOUND error', function () {
        $driver = makeDriver([jsonResponse(batchResponseWithError(
            'missing-flag',
        ))]);
        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        $result = $driver->get('missing-flag', $scope);

        expect($result)->toBeNull();
    });

    it('throws an exception for a non-NOT_FOUND error', function () {
        $driver = makeDriver([jsonResponse(batchResponseWithError(
            'my-flag',
            'UNKNOWN_ERROR_EVALUATION_REASON',
        ))]);
        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        $driver->get('my-flag', $scope);
    })->throws(Exception::class);

    it('uses "anon" entity ID when scope is null', function () {
        $driver = makeDriver([jsonResponse(batchResponseWithBoolean(
            'my-flag',
            true,
        ))]);

        $result = $driver->get('my-flag', null);

        expect($result)->toBeTrue();
    });
});

describe('FliptFeatureDriver::getAll', function () {
    it('resolves multiple boolean flags', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'requestId' => 'batch-1',
            'requestDurationMillis' => 0.5,
            'responses' => [
                [
                    'type' => 'BOOLEAN_EVALUATION_RESPONSE_TYPE',
                    'booleanResponse' => [
                        'enabled' => true,
                        'reason' => 'MATCH_EVALUATION_REASON',
                        'requestId' => '0',
                        'requestDurationMillis' => 0.5,
                        'timestamp' => '2024-01-01T00:00:00Z',
                        'flagKey' => 'flag-a',
                    ],
                ],
                [
                    'type' => 'BOOLEAN_EVALUATION_RESPONSE_TYPE',
                    'booleanResponse' => [
                        'enabled' => false,
                        'reason' => 'FLAG_DISABLED_EVALUATION_REASON',
                        'requestId' => '1',
                        'requestDurationMillis' => 0.5,
                        'timestamp' => '2024-01-01T00:00:00Z',
                        'flagKey' => 'flag-b',
                    ],
                ],
            ],
        ])], $captured);

        $events = Mockery::mock(Dispatcher::class);
        $driver = new FliptFeatureDriver($flipt, $events);
        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        $features = [
            'flag-a' => [0 => $scope],
            'flag-b' => [1 => $scope],
        ];

        $result = $driver->getAll($features);

        expect($result['flag-a'][0])->toBeTrue();
        expect($result['flag-b'][1])->toBeFalse();
    });
});

describe('FliptFeatureDriver::defined', function () {
    it('returns flag keys from the Flipt namespace', function () {
        $driver = makeDriver([jsonResponse([
            'flags' => [
                [
                    'key' => 'flag-a',
                    'type' => 'BOOLEAN_FLAG_TYPE',
                    'name' => 'Flag A',
                    'description' => '',
                    'enabled' => true,
                    'variants' => [],
                    'rules' => [],
                    'rollouts' => [],
                    'defaultVariant' => '',
                    'metadata' => [],
                ],
                [
                    'key' => 'flag-b',
                    'type' => 'VARIANT_FLAG_TYPE',
                    'name' => 'Flag B',
                    'description' => '',
                    'enabled' => false,
                    'variants' => [],
                    'rules' => [],
                    'rollouts' => [],
                    'defaultVariant' => '',
                    'metadata' => [],
                ],
            ],
            'nextPageToken' => '',
            'totalCount' => 2,
        ])]);

        expect($driver->defined())->toBe(['flag-a', 'flag-b']);
    });

    it('returns an empty array when no flags are defined', function () {
        $driver = makeDriver([jsonResponse([
            'flags' => [],
            'nextPageToken' => '',
            'totalCount' => 0,
        ])]);

        expect($driver->defined())->toBe([]);
    });
});

describe('FliptFeatureDriver::definedFeaturesForScope', function () {
    it('returns the same flag keys as defined() regardless of scope', function () {
        $driver = makeDriver([
            jsonResponse([
                'flags' => [
                    [
                        'key' => 'flag-x',
                        'type' => 'BOOLEAN_FLAG_TYPE',
                        'name' => 'X',
                        'description' => '',
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
            ]),
            jsonResponse([
                'flags' => [
                    [
                        'key' => 'flag-x',
                        'type' => 'BOOLEAN_FLAG_TYPE',
                        'name' => 'X',
                        'description' => '',
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
            ]),
        ]);

        $scope = (object) ['id' => 'user-1', 'email' => 'user@example.com'];

        expect($driver->definedFeaturesForScope($scope))->toBe(['flag-x']);
        expect($driver->definedFeaturesForScope(null))->toBe(['flag-x']);
    });
});

describe('FliptFeatureDriver::purge', function () {
    it('flushes the Flipt cache when caching is enabled', function () {
        $cache = Mockery::mock(\Illuminate\Cache\Repository::class);
        $taggedCache = Mockery::mock(\Illuminate\Cache\TaggedCache::class);

        $cache
            ->shouldReceive('tags')
            ->once()
            ->with(['flipt'])
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')->once();

        $flipt = new \Clearlyip\LaravelFlipt\Flipt(
            host: 'http://localhost:8080',
            namespace: 'default',
            cache: $cache,
        );

        $events = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $driver = new \Clearlyip\LaravelFlipt\Pennant\FliptFeatureDriver(
            $flipt,
            $events,
        );

        $driver->purge(null);
    });

    it('does nothing when caching is disabled', function () {
        $driver = makeDriver();

        $driver->purge(null);
        $driver->purge(['flag-a', 'flag-b']);

        expect(true)->toBeTrue(); // no exception thrown
    });

    it('flushes the entire cache even when specific features are named', function () {
        $cache = Mockery::mock(\Illuminate\Cache\Repository::class);
        $taggedCache = Mockery::mock(\Illuminate\Cache\TaggedCache::class);

        $cache
            ->shouldReceive('tags')
            ->once()
            ->with(['flipt'])
            ->andReturn($taggedCache);
        $taggedCache->shouldReceive('flush')->once();

        $flipt = new \Clearlyip\LaravelFlipt\Flipt(
            host: 'http://localhost:8080',
            namespace: 'default',
            cache: $cache,
        );

        $events = Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $driver = new \Clearlyip\LaravelFlipt\Pennant\FliptFeatureDriver(
            $flipt,
            $events,
        );

        $driver->purge(['flag-a']);
    });
});

describe('FliptFeatureDriver unsupported write operations', function () {
    it('throws BadMethodCallException on define', function () {
        $driver = makeDriver();
        $driver->define('flag', fn() => true);
    })->throws(BadMethodCallException::class);

    it('throws BadMethodCallException on set', function () {
        $driver = makeDriver();
        $driver->set('flag', null, true);
    })->throws(BadMethodCallException::class);

    it('throws BadMethodCallException on setForAllScopes', function () {
        $driver = makeDriver();
        $driver->setForAllScopes('flag', true);
    })->throws(BadMethodCallException::class);

    it('throws BadMethodCallException on delete', function () {
        $driver = makeDriver();
        $driver->delete('flag', null);
    })->throws(BadMethodCallException::class);
});
