<?php

namespace Clearlyip\LaravelFlipt;

use BadMethodCallException;
use Clearlyip\LaravelFlipt\Contracts\Response;
use Clearlyip\LaravelFlipt\Flipt\Evaluate;
use Clearlyip\LaravelFlipt\Flipt\OpenFeature;
use Clearlyip\LaravelFlipt\Models\BooleanResponse;
use Clearlyip\LaravelFlipt\Models\ErrorResponse;
use Clearlyip\LaravelFlipt\Models\VariantResponse;
use DomainException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Cache\Repository;
use GuzzleHttp\Psr7\Message;
use Spatie\Cloneable\Cloneable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;

/**
 * @property-read Evaluate $evaluate
 * @property-read OpenFeature $openfeature
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
        //
    }

    public function __get($property)
    {
        return match ($property) {
            'evaluate' => new Evaluate($this),
            'openfeature' => new OpenFeature($this),
            default => throw new BadMethodCallException(
                "Unknown property: $property",
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
        return config('flipt.cache.tags', ['flipt']);
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
        return config('flipt.cache.prefix', 'flipt');
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

    /**
     * Map an array to a class using valinor
     *
     * @template T of object
     *
     * @param class-string<T> $signature
     * @return T
     */
    public function map(string $signature, array $source)
    {
        return new \CuyZ\Valinor\MapperBuilder()
            /** @return class-string<BooleanResponse|VariantResponse|ErrorResponse> */
            ->infer(
                Response::class,
                fn(string $type) => match ($type) {
                    'BOOLEAN_EVALUATION_RESPONSE_TYPE'
                        => BooleanResponse::class,
                    'VARIANT_EVALUATION_RESPONSE_TYPE'
                        => VariantResponse::class,
                    'ERROR_EVALUATION_RESPONSE_TYPE' => ErrorResponse::class,
                    default => throw new DomainException(
                        "Unhandled type `$type`.",
                    ),
                },
            )
            ->mapper()
            ->map(
                $signature,
                \CuyZ\Valinor\Mapper\Source\Source::array($source),
            );
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
    public function apiRequest(
        string $path,
        string $method = 'GET',
        ?array $body = null,
        ?array $headers = [],
        ?array $cacheTags = [],
    ): ResponseInterface {
        $headers = [
            'Accept' => 'application/json',
            'X-Flipt-Environment' => $this->environment,
            ...$headers,
        ];

        $request = new Request($method, $this->host . $path);

        if ($body !== null) {
            $json = json_encode($body, JSON_THROW_ON_ERROR);
            //Hacky-Hack
            $json = str_replace('"context":[]', '"context":{}', $json);
            $request = $request->withBody(Utils::streamFor($json));
        }

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if (!$this->shouldCache()) {
            return $this->client->sendRequest($request);
        }

        $response = null;
        $cacheKey = $this->getCachePrefix() . sha1(Message::toString($request));
        $cachedResponse = $this->cache
            ->tags([...$this->getCacheTags(), ...$cacheTags])
            ->get($cacheKey);

        if (!$this->skipCache && $cachedResponse !== null) {
            return Message::parseResponse($cachedResponse);
        }

        // execute request
        $response = $this->client->sendRequest($request);

        $this->cache
            ->tags([...$this->getCacheTags(), ...$cacheTags])
            ->put(
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
        return json_decode(
            $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
