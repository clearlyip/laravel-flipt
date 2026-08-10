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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
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

        return $this->resolveWithCache($request, $cache, $cacheTags, $cacheKey);
    }

    /**
     * Resolves a request through the Flipt cache, serving fresh entries
     * immediately, revalidating stale entries on a throttled cadence, and
     * falling back to a stale response when Flipt is unreachable.
     *
     * @param \GuzzleHttp\Psr7\Request $request The request to send.
     * @param \Illuminate\Cache\Repository $cache The cache repository.
     * @param array $cacheTags Additional cache tags.
     * @param string $cacheKey The cache key for the request.
     *
     * @return ResponseInterface The response from the Flipt API
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function resolveWithCache(
        Request $request,
        Repository $cache,
        array $cacheTags,
        string $cacheKey,
    ): ResponseInterface {
        /** @var array{response: string, timestamp: int, revalidated_at: int|null}|null $stored */
        $stored = $cache->tags([
            ...$this->getCacheTags(),
            ...$cacheTags,
        ])->get($cacheKey);

        if (!$this->skipCache && is_array($stored)) {
            $cached = Message::parseResponse((string) $stored['response']);

            // Serve the cached response immediately while it is still fresh.
            if (((int) $stored['timestamp'] + $this->getCacheTTL()) > time()) {
                return $cached;
            }

            // Stale: throttle revalidation so a down Flipt isn't hammered on
            // every request. Only try to refresh once per TTL window, serving
            // the stale response in between.
            $lastCheck = (int) ($stored['revalidated_at'] ?? 0);
            if (($lastCheck + $this->getCacheTTL()) > time()) {
                return $cached;
            }

            $now = time();

            try {
                $fresh = $this->client->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                $this->markRevalidated(
                    $cache,
                    $cacheTags,
                    $cacheKey,
                    $stored,
                    $now,
                );

                return $cached;
            }

            if ($this->isSuccess($fresh)) {
                $this->putInCache($cache, $cacheTags, $cacheKey, $fresh, $now);

                return $fresh;
            }

            $this->markRevalidated(
                $cache,
                $cacheTags,
                $cacheKey,
                $stored,
                $now,
            );

            return $cached;
        }

        // No usable cached response: perform the request directly.
        $response = $this->client->sendRequest($request);

        if ($this->isSuccess($response)) {
            $this->putInCache($cache, $cacheTags, $cacheKey, $response, time());
        }

        return $response;
    }

    /**
     * Returns whether the given response represents a successful result.
     *
     * Failed (non-2xx) responses are deliberately not cached so a transient
     * Flipt blip never poisons an otherwise good cache entry.
     */
    private function isSuccess(ResponseInterface $response): bool
    {
        return (
            $response->getStatusCode() >= 200
            && $response->getStatusCode() < 300
        );
    }

    /**
     * Stores a successful response in the Flipt cache under the given key,
     * retaining it indefinitely so it can be served as a stale fallback after
     * its freshness window has lapsed.
     *
     * @param \Illuminate\Cache\Repository $cache The cache repository.
     * @param array $cacheTags Additional cache tags.
     * @param string $cacheKey The cache key for the response.
     * @param ResponseInterface $response The successful response to cache.
     * @param int $now The current unix timestamp.
     */
    private function putInCache(
        Repository $cache,
        array $cacheTags,
        string $cacheKey,
        ResponseInterface $response,
        int $now,
    ): void {
        $cache->tags([
            ...$this->getCacheTags(),
            ...$cacheTags,
        ])->put(
            $cacheKey,
            [
                'response' => Message::toString($response),
                'timestamp' => $now,
                'revalidated_at' => $now,
            ],
            null,
        );
    }

    /**
     * Records that a revalidation attempt was made without overwriting the
     * stored (stale) response, so the throttling window is respected.
     *
     * @param \Illuminate\Cache\Repository $cache The cache repository.
     * @param array $cacheTags Additional cache tags.
     * @param string $cacheKey The cache key for the response.
     * @param array<string, mixed> $stored The currently stored response data.
     * @param int $now The current unix timestamp.
     */
    private function markRevalidated(
        Repository $cache,
        array $cacheTags,
        string $cacheKey,
        array $stored,
        int $now,
    ): void {
        $cache->tags([
            ...$this->getCacheTags(),
            ...$cacheTags,
        ])->put(
            $cacheKey,
            [
                ...$stored,
                'revalidated_at' => $now,
            ],
            null,
        );
    }

    /**
     * Decode the JSON response from the Flipt API into an associative array
     *
     * If the response body is not valid JSON, the failure is logged with the
     * HTTP status and a snippet of the body for visibility, and the original
     * {@see JsonException} is rethrown so callers can decide how to handle it.
     *
     * @param ResponseInterface $response The response from the Flipt API
     *
     * @return array The decoded response as an associative array
     * @throws \JsonException If the response body is not valid JSON
     */
    public function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        try {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::warning('Flipt response could not be decoded as JSON', [
                'status' => $response->getStatusCode(),
                'body' => Str::limit($body, 500),
            ]);

            throw $e;
        }

        return $decoded ?? [];
    }
}
