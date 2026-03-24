<?php

use BadMethodCallException;
use Clearlyip\LaravelFlipt\Flipt;
use Clearlyip\LaravelFlipt\Flipt\ClientEvaluation;
use Clearlyip\LaravelFlipt\Flipt\Environments;
use Clearlyip\LaravelFlipt\Flipt\Evaluate;
use Clearlyip\LaravelFlipt\Flipt\Flags;
use Clearlyip\LaravelFlipt\Flipt\Internal;
use Clearlyip\LaravelFlipt\Flipt\OpenFeature;

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
});
