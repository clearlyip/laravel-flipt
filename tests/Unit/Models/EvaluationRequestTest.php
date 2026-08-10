<?php

use Clearlyip\LaravelFlipt\Models\EvaluationRequest;

describe('EvaluationRequest', function () {
    it('converts string context values unchanged', function () {
        $request = new EvaluationRequest('flag', 'entity-1', [
            'key' => 'value',
        ]);
        expect($request->toArray()['context'])->toBe(['key' => 'value']);
    });

    it('converts integer context values to strings', function () {
        $request = new EvaluationRequest('flag', 'entity-1', ['age' => 42]);
        expect($request->toArray()['context'])->toBe(['age' => '42']);
    });

    it('converts float context values to strings', function () {
        $request = new EvaluationRequest('flag', 'entity-1', ['score' => 3.14]);
        expect($request->toArray()['context']['score'])->toBe('3.14');
    });

    it('converts boolean true context values to "1"', function () {
        $request = new EvaluationRequest('flag', 'entity-1', [
            'active' => true,
        ]);
        expect($request->toArray()['context'])->toBe(['active' => '1']);
    });

    it('converts boolean false context values to "0"', function () {
        $request = new EvaluationRequest('flag', 'entity-1', [
            'active' => false,
        ]);
        expect($request->toArray()['context'])->toBe(['active' => '0']);
    });

    it('throws for unsupported context value types', function () {
        $request = new EvaluationRequest('flag', 'entity-1', [
            'obj' => new stdClass(),
        ]);
        $request->toArray();
    })->throws(\DomainException::class);

    it('includes namespace in toBody', function () {
        $request = new EvaluationRequest('my-flag', 'user-1', ['foo' => 'bar']);
        $body = $request->toBody('my-namespace');

        expect($body['namespaceKey'])->toBe('my-namespace');
        expect($body['flagKey'])->toBe('my-flag');
        expect($body['entityId'])->toBe('user-1');
    });

    it('includes optional requestId and reference in toArray', function () {
        $request = new EvaluationRequest(
            'flag',
            'entity-1',
            [],
            'req-123',
            'ref-abc',
        );
        $array = $request->toArray();

        expect($array['requestId'])->toBe('req-123');
        expect($array['reference'])->toBe('ref-abc');
    });
});
