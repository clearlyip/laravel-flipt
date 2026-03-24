<?php

use Clearlyip\LaravelFlipt\Enums\FlagReason;
use Clearlyip\LaravelFlipt\Models\Bulk;
use Clearlyip\LaravelFlipt\Models\Configuration;
use Clearlyip\LaravelFlipt\Models\Flag;
use Clearlyip\LaravelFlipt\Models\FlagRequest;

describe('OpenFeature::configuration', function () {
    it('returns a Configuration model', function () {
        $flipt = makeFliptClient([jsonResponse([
            'name' => 'flipt',
            'capabilities' => [
                'cacheInvalidation' => [
                    'polling' => [
                        'enabled' => true,
                        'minPollingIntervalMs' => 1000,
                    ],
                ],
                'flagEvaluation' => [
                    'supportedTypes' => [
                        'BOOLEAN_FLAG_TYPE',
                        'VARIANT_FLAG_TYPE',
                    ],
                ],
            ],
        ])]);

        $result = $flipt->openfeature->configuration();

        expect($result)->toBeInstanceOf(Configuration::class);
        expect($result->name)->toBe('flipt');
        expect($result->capabilities->cacheInvalidation->polling->enabled)->toBeTrue();
        expect($result->capabilities->cacheInvalidation->polling->minPollingIntervalMs)
            ->toBe(1000);
    });

    it('calls GET /ofrep/v1/configuration', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'name' => 'flipt',
            'capabilities' => [
                'cacheInvalidation' => ['polling' => [
                    'enabled' => false,
                    'minPollingIntervalMs' => 500,
                ]],
                'flagEvaluation' => ['supportedTypes' => []],
            ],
        ])], $captured);

        $flipt->openfeature->configuration();

        expect($captured[0]->getMethod())->toBe('GET');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/ofrep/v1/configuration');
    });
});

describe('OpenFeature::flag', function () {
    it('returns a Flag model', function () {
        $flipt = makeFliptClient([jsonResponse([
            'key' => 'my-flag',
            'reason' => 'TARGETING_MATCH',
            'variant' => 'v1',
            'metadata' => [],
            'value' => true,
        ])]);

        $result = $flipt->openfeature->flag(
            'my-flag',
            new FlagRequest('user-1'),
        );

        expect($result)->toBeInstanceOf(Flag::class);
        expect($result->key)->toBe('my-flag');
        expect($result->reason)->toBe(FlagReason::TARGETING_MATCH);
        expect($result->variant)->toBe('v1');
    });

    it('posts to /ofrep/v1/evaluate/flags/{name}', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'key' => 'test-flag',
            'reason' => 'DEFAULT',
            'variant' => '',
            'metadata' => [],
            'value' => false,
        ])], $captured);

        $flipt->openfeature->flag('test-flag', new FlagRequest('user-1'));

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/ofrep/v1/evaluate/flags/test-flag');
    });

    it('sets X-Flipt-Namespace header', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'key' => 'f',
            'reason' => 'DEFAULT',
            'variant' => '',
            'metadata' => [],
            'value' => false,
        ])], $captured);

        $flipt->openfeature->flag('f', new FlagRequest('user-1'));

        expect($captured[0]->getHeader('X-Flipt-Namespace'))->toBe(['default']);
    });

    it('sends targetingKey in request body context', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'key' => 'f',
            'reason' => 'DEFAULT',
            'variant' => '',
            'metadata' => [],
            'value' => false,
        ])], $captured);

        $flipt->openfeature->flag('f', new FlagRequest('user-42', [
            'env' => 'prod',
        ]));

        $body = json_decode((string) $captured[0]->getBody(), true);
        expect($body['context']['targetingKey'])->toBe('user-42');
        // Context values are nested under a 'context' key due to FlagRequest::toBody() spread
        expect($body['context']['context'])->toBe(['env' => 'prod']);
    });
});

describe('OpenFeature::bulk', function () {
    it('returns a Bulk model', function () {
        $flipt = makeFliptClient([jsonResponse([
            'flags' => [
                [
                    'key' => 'flag-a',
                    'reason' => 'DEFAULT',
                    'variant' => '',
                    'metadata' => [],
                    'value' => false,
                ],
            ],
        ])]);

        $result = $flipt->openfeature->bulk(
            ['flag-a'],
            new FlagRequest('user-1'),
        );

        expect($result)->toBeInstanceOf(Bulk::class);
        expect($result->flags)->toHaveCount(1);
        expect($result->flags[0])->toBeInstanceOf(Flag::class);
        expect($result->flags[0]->key)->toBe('flag-a');
    });

    it('posts to /ofrep/v1/evaluate/flags', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['flags' => []])], $captured);

        $flipt->openfeature->bulk(['flag-a'], new FlagRequest('user-1'));

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())
            ->toBe('/ofrep/v1/evaluate/flags');
    });

    it('sets X-Flipt-Namespace header', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(['flags' => []])], $captured);

        $flipt->openfeature->bulk([], new FlagRequest('user-1'));

        expect($captured[0]->getHeader('X-Flipt-Namespace'))->toBe(['default']);
    });
});
