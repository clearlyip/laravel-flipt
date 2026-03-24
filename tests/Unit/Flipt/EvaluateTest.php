<?php

use Clearlyip\LaravelFlipt\Enums\Reason;
use Clearlyip\LaravelFlipt\Models\BatchResponse;
use Clearlyip\LaravelFlipt\Models\Boolean;
use Clearlyip\LaravelFlipt\Models\BooleanResponse;
use Clearlyip\LaravelFlipt\Models\ErrorResponse;
use Clearlyip\LaravelFlipt\Models\EvaluationRequest;
use Clearlyip\LaravelFlipt\Models\Variant;
use Clearlyip\LaravelFlipt\Models\VariantResponse;

function booleanResponseData(
    string $flagKey = 'my-flag',
    bool $enabled = true,
): array {
    return [
        'enabled' => $enabled,
        'reason' => 'MATCH_EVALUATION_REASON',
        'requestId' => 'req-1',
        'requestDurationMillis' => 1.5,
        'timestamp' => '2024-01-01T00:00:00Z',
        'flagKey' => $flagKey,
    ];
}

function variantResponseData(string $flagKey = 'my-flag'): array
{
    return [
        'match' => true,
        'segmentKeys' => ['segment-a'],
        'reason' => 'MATCH_EVALUATION_REASON',
        'variantKey' => 'variant-1',
        'variantAttachment' => '{"foo":"bar"}',
        'requestId' => 'req-1',
        'requestDurationMillis' => 1.5,
        'timestamp' => '2024-01-01T00:00:00Z',
        'flagKey' => $flagKey,
    ];
}

describe('Evaluate::boolean', function () {
    it('returns a Boolean model from the API response', function () {
        $flipt = makeFliptClient([jsonResponse(booleanResponseData())]);
        $request = new EvaluationRequest('my-flag', 'user-1');

        $result = $flipt->evaluate->boolean($request);

        expect($result)->toBeInstanceOf(Boolean::class);
        expect($result->enabled)->toBeTrue();
        expect($result->flagKey)->toBe('my-flag');
        expect($result->reason)->toBe(Reason::MATCH_EVALUATION_REASON);
    });

    it('posts to /evaluate/v1/boolean', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            booleanResponseData(),
        )], $captured);

        $flipt->evaluate->boolean(new EvaluationRequest('my-flag', 'user-1'));

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())->toBe('/evaluate/v1/boolean');
    });

    it('sends the namespace and flag key in the request body', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            booleanResponseData(),
        )], $captured);

        $flipt->evaluate->boolean(new EvaluationRequest('my-flag', 'user-1', [
            'env' => 'prod',
        ]));

        $body = json_decode((string) $captured[0]->getBody(), true);
        expect($body['namespaceKey'])->toBe('default');
        expect($body['flagKey'])->toBe('my-flag');
        expect($body['entityId'])->toBe('user-1');
        expect($body['context'])->toBe(['env' => 'prod']);
    });

    it('returns false when flag is disabled', function () {
        $flipt = makeFliptClient([jsonResponse(booleanResponseData(
            enabled: false,
        ))]);
        $result = $flipt->evaluate->boolean(new EvaluationRequest(
            'my-flag',
            'user-1',
        ));

        expect($result->enabled)->toBeFalse();
    });
});

describe('Evaluate::variant', function () {
    it('returns a Variant model from the API response', function () {
        $flipt = makeFliptClient([jsonResponse(variantResponseData())]);
        $request = new EvaluationRequest('my-flag', 'user-1');

        $result = $flipt->evaluate->variant($request);

        expect($result)->toBeInstanceOf(Variant::class);
        expect($result->match)->toBeTrue();
        expect($result->variantKey)->toBe('variant-1');
        expect($result->variantAttachment)->toBe('{"foo":"bar"}');
        expect($result->segmentKeys)->toBe(['segment-a']);
    });

    it('posts to /evaluate/v1/variant', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse(
            variantResponseData(),
        )], $captured);

        $flipt->evaluate->variant(new EvaluationRequest('my-flag', 'user-1'));

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())->toBe('/evaluate/v1/variant');
    });
});

describe('Evaluate::batch', function () {
    it('returns a BatchResponse with boolean responses', function () {
        $flipt = makeFliptClient([jsonResponse([
            'requestId' => 'batch-req-1',
            'requestDurationMillis' => 2.0,
            'responses' => [
                [
                    'type' => 'BOOLEAN_EVALUATION_RESPONSE_TYPE',
                    'booleanResponse' => booleanResponseData('flag-a'),
                ],
            ],
        ])]);

        $result = $flipt->evaluate->batch([
            new EvaluationRequest('flag-a', 'user-1'),
        ]);

        expect($result)->toBeInstanceOf(BatchResponse::class);
        expect($result->requestId)->toBe('batch-req-1');
        expect($result->responses)->toHaveCount(1);
        expect($result->responses[0])->toBeInstanceOf(BooleanResponse::class);
        expect($result->responses[0]->booleanResponse->flagKey)->toBe('flag-a');
    });

    it('returns a BatchResponse with variant responses', function () {
        $flipt = makeFliptClient([jsonResponse([
            'requestId' => 'batch-req-2',
            'requestDurationMillis' => 1.0,
            'responses' => [
                [
                    'type' => 'VARIANT_EVALUATION_RESPONSE_TYPE',
                    'variantResponse' => variantResponseData('flag-b'),
                ],
            ],
        ])]);

        $result = $flipt->evaluate->batch([
            new EvaluationRequest('flag-b', 'user-1'),
        ]);

        expect($result->responses[0])->toBeInstanceOf(VariantResponse::class);
        expect($result->responses[0]->variantResponse->variantKey)
            ->toBe('variant-1');
    });

    it('returns a BatchResponse with error responses', function () {
        $flipt = makeFliptClient([jsonResponse([
            'requestId' => 'batch-req-3',
            'requestDurationMillis' => 0.5,
            'responses' => [
                [
                    'type' => 'ERROR_EVALUATION_RESPONSE_TYPE',
                    'errorResponse' => [
                        'flagKey' => 'missing-flag',
                        'namespaceKey' => 'default',
                        'reason' => 'NOT_FOUND_ERROR_EVALUATION_REASON',
                    ],
                ],
            ],
        ])]);

        $result = $flipt->evaluate->batch([
            new EvaluationRequest('missing-flag', 'user-1'),
        ]);

        expect($result->responses[0])->toBeInstanceOf(ErrorResponse::class);
        expect($result->responses[0]->errorResponse->flagKey)
            ->toBe('missing-flag');
    });

    it('throws when multiple entity IDs are used', function () {
        $flipt = makeFliptClient();
        $flipt->evaluate->batch([
            new EvaluationRequest('flag-a', 'user-1'),
            new EvaluationRequest('flag-b', 'user-2'),
        ]);
    })->throws(InvalidArgumentException::class);

    it('posts to /evaluate/v1/batch', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'requestId' => 'r',
            'requestDurationMillis' => 0.1,
            'responses' => [],
        ])], $captured);

        $flipt->evaluate->batch([new EvaluationRequest('flag-a', 'user-1')]);

        expect($captured[0]->getMethod())->toBe('POST');
        expect($captured[0]->getUri()->getPath())->toBe('/evaluate/v1/batch');
    });

    it('includes all requests in the batch body', function () {
        $captured = [];
        $flipt = makeFliptClient([jsonResponse([
            'requestId' => 'r',
            'requestDurationMillis' => 0.1,
            'responses' => [],
        ])], $captured);

        $flipt->evaluate->batch([
            new EvaluationRequest('flag-a', 'user-1'),
            new EvaluationRequest('flag-b', 'user-1'),
        ]);

        $body = json_decode((string) $captured[0]->getBody(), true);
        expect($body['requests'])->toHaveCount(2);
        expect($body['requests'][0]['flagKey'])->toBe('flag-a');
        expect($body['requests'][1]['flagKey'])->toBe('flag-b');
    });
});
