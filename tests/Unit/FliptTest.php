<?php

use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Flipt\ClientEvaluation;
use Clearlyip\LaravelFlipt\Flipt\Environments;
use Clearlyip\LaravelFlipt\Flipt\Evaluate;
use Clearlyip\LaravelFlipt\Flipt\Flags;
use Clearlyip\LaravelFlipt\Flipt\Internal;
use Clearlyip\LaravelFlipt\Flipt\OpenFeature;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;

/**
 * Create a mock cache repository whose reads/writes go through a shared
 * mutable store so tests can seed and inspect cached values.
 */
function mockFliptCache(array &$store): array
{
    $cache = Mockery::mock(\Illuminate\Cache\Repository::class);
    $tagged = Mockery::mock(\Illuminate\Cache\TaggedCache::class);

    $cache->shouldReceive('tags')->andReturn($tagged);
    $tagged
        ->shouldReceive('get')
        ->andReturnUsing(static function () use (&$store): mixed {
            return $store['values'] ?? null;
        });
    $tagged
        ->shouldReceive('put')
        ->andReturnUsing(static function (
            string $key,
            mixed $value,
            mixed $ttl,
        ) use (&$store): bool {
            $store['values'] = $value;

            return true;
        });

    return [$cache, $tagged];
}

/**
 * Build a Flipt client backed by a mock cache and a mocked HTTP client.
 */
function makeCachedFlipt(
    array &$store,
    array $responses = [],
    ?\Throwable $thrown = null,
): Flipt {
    [$cache] = mockFliptCache($store);

    $client = Mockery::mock(ClientInterface::class);

    if ($thrown !== null) {
        $client->shouldReceive('sendRequest')->once()->andThrow($thrown);
    } else {
        foreach ($responses as $i => $response) {
            $client->shouldReceive('sendRequest')->once()->andReturn($response);
        }
    }

    return new Flipt(
        host: 'http://localhost:8080',
        namespace: 'default',
        environment: 'test',
        cache: $cache,
        client: $client,
    );
}

describe('Flipt', function () {
    it('exposes evaluate service via __get', function () {
        $flipt = makeFliptClient();
        expect($flipt->evaluate)->toBeInstanceOf(Evaluate::class);
    });

    it('exposes openfeature service via __get', function () {
        $flipt = makeFliptClient();
        expect($flipt->openfeature)->toBeInstanceOf(OpenFeature::class);
    });

    it('exposes internal service via __get', function () {
        $flipt = makeFliptClient();
        expect($flipt->internal)->toBeInstanceOf(Internal::class);
    });

    it('exposes clientevaluation service via __get', function () {
        $flipt = makeFliptClient();
        expect($flipt->clientevaluation)
            ->toBeInstanceOf(ClientEvaluation::class);
    });

    it('exposes environments service via __get', function () {
        $flipt = makeFliptClient();
        expect($flipt->environments)->toBeInstanceOf(Environments::class);
    });

    it('exposes flags service via __get', function () {
        $flipt = makeFliptClient();
        expect($flipt->flags)->toBeInstanceOf(Flags::class);
    });

    it('throws BadMethodCallException for unknown properties', function () {
        $flipt = makeFliptClient();
        $_ = $flipt->unknown;
    })->throws(BadMethodCallException::class);

    it('withSkipCache returns a new instance with skipCache true', function () {
        $flipt = makeFliptClient();
        $skipped = $flipt->withSkipCache();

        expect($skipped)->not->toBe($flipt);
        expect($skipped->skipCache)->toBeTrue();
        expect($flipt->skipCache)->toBeFalse();
    });

    it('shouldCache returns false when no cache is configured', function () {
        $flipt = makeFliptClient();
        expect($flipt->shouldCache())->toBeFalse();
    });

    it('shouldCache returns true when cache is configured', function () {
        $cache = Mockery::mock(\Illuminate\Cache\Repository::class);
        $flipt = new Flipt(
            host: 'http://localhost:8080',
            namespace: 'default',
            cache: $cache,
        );
        expect($flipt->shouldCache())->toBeTrue();
    });

    it('getCacheTags returns configured tags', function () {
        expect(makeFliptClient()->getCacheTags())->toBe(['flipt']);
    });

    it('getCachePrefix returns configured prefix', function () {
        expect(makeFliptClient()->getCachePrefix())->toBe('flipt');
    });

    it('getCacheTTL returns configured ttl', function () {
        expect(makeFliptClient()->getCacheTTL())->toBe(60);
    });

    it('sets the X-Flipt-Environment header on requests', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'enabled' => true,
            'reason' => 'DEFAULT_EVALUATION_REASON',
            'requestId' => 'r',
            'requestDurationMillis' => 0.1,
            'timestamp' => '2024-01-01T00:00:00Z',
            'flagKey' => 'f',
        ])], $captured);

        $flipt->evaluate->boolean(new \Clearlyip\LaravelFlipt\Models\EvaluationRequest(
            'f',
            'e',
        ));

        expect($captured[0]->getHeader('X-Flipt-Environment'))->toBe(['test']);
    });

    it('logs the status and body when decodeResponse fails on invalid JSON', function () {
        \Illuminate\Support\Facades\Log::spy();
        $flipt = makeFliptClient([new \GuzzleHttp\Psr7\Response(
            503,
            [],
            '<html>Service Unavailable</html>',
        )]);

        try {
            $flipt->flags->list();
        } catch (\JsonException $e) {
            expect($e)->toBeInstanceOf(\JsonException::class);
        }

        \Illuminate\Support\Facades\Log::shouldHaveReceived(
            'warning',
        )->withArgs(
            fn(string $message, array $context): bool => (
                $message === 'Flipt response could not be decoded as JSON'
                && ($context['status'] ?? null) === 503
                && isset($context['body'])
            ),
        );
    });
});

describe('Flipt cache resilience', function () {
    it('serves a fresh cached response without a network request', function () {
        $good = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"ok":true}',
        );
        $store = [
            'values' => [
                'response' => Message::toString($good),
                'timestamp' => time(),
            ],
        ];
        $flipt = makeCachedFlipt($store);

        $response = $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($response->getStatusCode())->toBe(200);
        expect((string) $response->getBody())->toBe('{"ok":true}');
    });

    it('caches successful responses for stale fallback', function () {
        $store = [];
        $flipt = makeCachedFlipt($store, [jsonResponse([
            'flags' => [],
            'nextPageToken' => '',
            'totalCount' => 0,
        ])]);

        $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($store['values'])->toBeArray();
        expect($store['values']['response'])->toBeString();
        expect($store['values']['timestamp'])->toBeInt();
        expect($store['values']['timestamp'])->toBeLessThanOrEqual(time());
    });

    it('does not cache non-successful responses', function () {
        $store = [];
        $flipt = makeCachedFlipt($store, [new Response(500, [], 'boom')]);

        $response = $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($response->getStatusCode())->toBe(500);
        expect($store['values'] ?? null)->toBeNull();
    });

    it('falls back to the stale response when Flipt is unreachable', function () {
        $good = new Response(200, [], '{"ok":true}');
        $store = [
            'values' => [
                'response' => Message::toString($good),
                'timestamp' => time() - 10_000,
            ],
        ];
        $flipt = makeCachedFlipt(
            $store,
            thrown: new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request(
                    'GET',
                    'http://localhost:8080/api/v1/namespaces/default/flags',
                ),
            ),
        );

        $response = $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($response->getStatusCode())->toBe(200);
        expect((string) $response->getBody())->toBe('{"ok":true}');
    });

    it('falls back to the stale response when Flipt returns an error', function () {
        $good = new Response(200, [], '{"ok":true}');
        $store = [
            'values' => [
                'response' => Message::toString($good),
                'timestamp' => time() - 10_000,
            ],
        ];
        $flipt = makeCachedFlipt($store, [new Response(503, [], 'down')]);

        $response = $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($response->getStatusCode())->toBe(200);
        expect((string) $response->getBody())->toBe('{"ok":true}');
    });
});

describe('Flipt cache revalidation throttling', function () {
    it('serves stale without a network call when revalidated within the TTL window', function () {
        $good = new Response(200, [], '{"ok":true}');
        $store = [
            'values' => [
                'response' => Message::toString($good),
                'timestamp' => time() - 10_000,
                'revalidated_at' => time(),
            ],
        ];
        $flipt = makeCachedFlipt($store);

        $response = $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($response->getStatusCode())->toBe(200);
        expect((string) $response->getBody())->toBe('{"ok":true}');
    });

    it('records the revalidation attempt on failure so the next request is throttled', function () {
        $good = new Response(200, [], '{"ok":true}');
        $store = [
            'values' => [
                'response' => Message::toString($good),
                'timestamp' => time() - 10_000,
            ],
        ];
        $flipt = makeCachedFlipt($store, [new Response(503, [], 'down')]);

        $flipt->apiRequest('/api/v1/namespaces/default/flags');

        expect($store['values']['response'])->toBe(Message::toString($good));
        expect($store['values']['revalidated_at'])->toBeInt();
    });
});
