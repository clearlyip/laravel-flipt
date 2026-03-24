<?php

use CIP\Tests\TestCase;
use Clearlyip\LaravelFlipt\Flipt;
use GuzzleHttp\Psr7\Response;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

uses(TestCase::class)->in('Unit');

afterEach(fn() => Mockery::close());

/**
 * Create a PSR-7 JSON response for testing.
 */
function jsonResponse(array $data, int $status = 200): ResponseInterface
{
    return new Response(
        $status,
        ['Content-Type' => 'application/json'],
        json_encode($data, JSON_THROW_ON_ERROR),
    );
}

/**
 * Create a mock PSR-18 HTTP client that returns the given responses in order.
 * Optionally captures each request into the provided array.
 *
 * @param ResponseInterface[] $responses
 * @param RequestInterface[]  $captured  Pass an array by reference to capture sent requests.
 */
function mockHttpClient(
    array $responses = [],
    array &$captured = [],
): ClientInterface {
    /** @var ClientInterface|MockInterface $mock */
    $mock = Mockery::mock(ClientInterface::class);

    foreach ($responses as $i => $response) {
        $mock
            ->shouldReceive('sendRequest')
            ->once()
            ->withArgs(function (RequestInterface $req) use (
                &$captured,
                $i,
            ): bool {
                $captured[$i] = $req;
                return true;
            })
            ->andReturn($response);
    }

    return $mock;
}

/**
 * Create a Flipt client with a mocked HTTP client.
 *
 * @param ResponseInterface[] $responses
 * @param RequestInterface[]  $captured
 */
function makeFliptClient(array $responses = [], array &$captured = []): Flipt
{
    return new Flipt(
        host: 'http://localhost:8080',
        namespace: 'default',
        environment: 'test',
        client: mockHttpClient($responses, $captured),
    );
}
