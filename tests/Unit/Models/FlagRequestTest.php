<?php

use Clearlyip\LaravelFlipt\Models\FlagRequest;

describe('FlagRequest', function () {
    it('converts context values to strings in toArray', function () {
        $request = new FlagRequest('entity-1', ['active' => true, 'age' => 30]);
        $array = $request->toArray();

        expect($array['context']['active'])->toBe('1');
        expect($array['context']['age'])->toBe('30');
    });

    it('builds toBody with targetingKey at the top level of context', function () {
        $request = new FlagRequest('user-42', ['env' => 'prod']);
        $body = $request->toBody();

        // toBody() spreads toArray() (minus entityId) into the context,
        // so the context values end up nested under a 'context' key.
        expect($body['context']['targetingKey'])->toBe('user-42');
        expect($body['context']['context'])->toBe(['env' => 'prod']);
    });

    it('does not include a top-level entityId key', function () {
        $request = new FlagRequest('user-1');
        $body = $request->toBody();

        expect($body)->not->toHaveKey('entityId');
    });

    it('handles empty context', function () {
        $request = new FlagRequest('user-1');
        $body = $request->toBody();

        expect($body['context']['targetingKey'])->toBe('user-1');
        expect($body['context']['context'])->toBe([]);
    });
});
