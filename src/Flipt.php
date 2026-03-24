<?php

namespace Clearlyip\LaravelFlipt;

use BadMethodCallException;
use Clearlyip\LaravelFlipt\Flipt\ClientEvaluation;
use Clearlyip\LaravelFlipt\Flipt\Environments;
use Clearlyip\LaravelFlipt\Flipt\Evaluate;
use Clearlyip\LaravelFlipt\Flipt\Flags;
use Clearlyip\LaravelFlipt\Flipt\Internal;
use Clearlyip\LaravelFlipt\Flipt\OpenFeature;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Cache\Repository;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Cloneable\Cloneable;

/**
 * @property-read Evaluate $evaluate
 * @property-read OpenFeature $openfeature
 * @property-read Internal $internal
 * @property-read ClientEvaluation $clientevaluation
 * @property-read Environments $environments
 * @property-read Flags $flags
 */
readonly class Flipt
{
    use Cloneable;

    public function __construct(
        public string $host,
        public string $namespace = 'default',
        public ?string $environment = null,
        public ?Repository $cache = null,
        public ?bool $skipCache = false,
        public ClientInterface $client = new GuzzleClient(),
    ) {
    }

    public function __get($property)
    {
        return match ($property) {
            'evaluate' => new Evaluate($this),
            'openfeature' => new OpenFeature($this),
            'internal' => new Internal($this),
            'clientevaluation' => new ClientEvaluation($this),
            'environments' => new Environments($this),
            'flags' => new Flags($this),
            default => throw new BadMethodCallException(
                "Unknown property: {$property}",
            ),
        };
    }

    /**
     * Create a new instance of the Flipt client with caching disabled.
     *
     * @return static
     */
    public function withSkipCache(): static
    {
        return $this->with(skipCache: true);
    }

    /**
     * Returns the cache tags used for caching Flipt API responses.
     *
     * The cache tags are used to identify the cache items that should be
     * invalidated when the cache is cleared.
     *
     * @return array The cache tags as an array of strings.
     */
    public function getCacheTags(): array
    {
        /** @var array<string> $tags */
        $tags = config('flipt.cache.tags', ['flipt']);
        return $tags;
    }

    /**
     * Determines whether the cache should be used when making requests to the Flipt API.
     *
     * This method returns true if a cache repository has been set, and false otherwise.
     *
     * @return bool Whether the cache should be used.
     */
    public function shouldCache(): bool
    {
        return $this->cache !== null;
    }

    /**
     * Returns the prefix used for caching Flipt API responses.
     *
     * The value returned by this method is used to prefix the cache keys
     * used when caching Flipt API responses. It defaults to 'flipt'.
     *
     * @return string The cache prefix.
     */
    public function getCachePrefix(): string
    {
        return (string) config('flipt.cache.prefix', 'flipt');
    }

    /**
     * Returns the cache TTL (time to live) in seconds.
     *
     * This value is used when caching Flipt API responses.
     *
     * @return int The cache TTL in seconds.
     */
    public function getCacheTTL(): int
    {
        return (int) config('flipt.cache.ttl', 60);
    }

    public function map(string $signature, array $source): object
    {
        /** @var object $mapped */
        $mapped = $signature::fromArray($source);
        return $mapped;
    }

    /**
     * Sends a request to the Flipt API
     *
     * @param string $path The path of the API endpoint
     * @param string $method The HTTP method to use (default: GET)
     * @param array|null $body The request body (default: null)
     * @param array $headers The request headers (default: [])
     * @param array $cacheTags The cache tags (default: [])
     *
     * @return ResponseInterface The response from the Flipt API
     */
    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function apiRequest(
        string $path,
        string $method = 'GET',
        ?array $body = null,
        ?array $headers = [],
        ?array $cacheTags = [],
    ): ResponseInterface {
        /** @var array<string, string|null> $headers */
        $headers = [
            'Accept' => 'application/json',
            'X-Flipt-Environment' => $this->environment,
            ...($headers ?? []),
        ];

        $request = new Request($method, $this->host . $path);

        if ($body !== null) {
            $json = json_encode($body, JSON_THROW_ON_ERROR);
            //Hacky-Hack
            $json = str_replace('"context":[]', '"context":{}', $json);
            $request = $request->withBody(Utils::streamFor($json));
        }

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, (string) $value);
        }

        if (!$this->shouldCache()) {
            return $this->client->sendRequest($request);
        }

        $cache = $this->cache;
        if ($cache === null) {
            return $this->client->sendRequest($request);
        }

        $cacheTags ??= [];
        $cacheKey = $this->getCachePrefix() . sha1(Message::toString($request));
        /** @var string|null $cachedResponse */
        $cachedResponse = $cache->tags([
            ...$this->getCacheTags(),
            ...$cacheTags,
        ])->get($cacheKey);

        if (!$this->skipCache && $cachedResponse !== null) {
            return Message::parseResponse($cachedResponse);
        }

        // execute request
        $response = $this->client->sendRequest($request);

        $cache->tags([...$this->getCacheTags(), ...$cacheTags])->put(
            $cacheKey,
            Message::toString($response),
            $this->getCacheTTL(),
        );

        return $response;
    }

    /**
     * Decode the JSON response from the Flipt API into an associative array
     *
     * @param ResponseInterface $response The response from the Flipt API
     *
     * @return array The decoded response as an associative array
     */
    public function decodeResponse(ResponseInterface $response): array
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        return $decoded ?? [];
    }
}
