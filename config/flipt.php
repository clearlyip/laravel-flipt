<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flipt Host
    |--------------------------------------------------------------------------
    | Value: String
    |
    | The Flipt API host. This is the host that will be used to make
    | requests to the Flipt API.
    |
    */

    'host' => env('FLIPT_HOST', null),

    /*
    |--------------------------------------------------------------------------
    | Flipt Namespace
    |--------------------------------------------------------------------------
    | Value: String
    |
    | The Flipt namespace. This is the namespace that will be used to make
    | requests to the Flipt API.
    |
    */

    'namespace' => env('FLIPT_NAMESPACE', null),

    /*
    |--------------------------------------------------------------------------
    | Flipt Environment
    |--------------------------------------------------------------------------
    | Value: String
    |
    | The Flipt environment. This is the environment that will be used to make
    | requests to the Flipt API.
    |
    */

    'environment' => env('FLIPT_ENVIRONMENT', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Flipt Identity Settings
    |--------------------------------------------------------------------------
    |
    */
    'identity' => [
        /*
        |--------------------------------------------------------------------------
        | Identifier
        |--------------------------------------------------------------------------
        | Value: string
        |
        | The Eloquent Attribute to use as the identifier for the user.
        |
        */

        'identifier' => 'id',

        /*
        |--------------------------------------------------------------------------
        | Context Mapping
        |--------------------------------------------------------------------------
        | Value: array<string, string>
        |
        | The Eloquent Attribute Traits on a user to send back to the Flipt API
        |
        | Dot notation is supported
        |
        */

        'context' => [
            'email' => 'email',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The following is a list of Laravel Caching options.
    |
    | All Global and User features will be cached
    |
    */

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Store
        |--------------------------------------------------------------------------
        | Value: String|null
        |
        | The caching (see cache.php in config) store to use.
        |
        | Set to null to disable caching
        |
        | Caching is recommended for performance reasons.
        |
        */

        'store' => 'default',

        /*
        |--------------------------------------------------------------------------
        | Prefix
        |--------------------------------------------------------------------------
        | Value: String
        |
        | The prefix used for storing the cache. This is used to namespace
        |
        */

        'prefix' => 'flipt',

        /*
        |--------------------------------------------------------------------------
        | Tags
        |--------------------------------------------------------------------------
        | Value: Array
        |
        | The tags used for storing the cache. This is used to namespace
        |
        */

        'tags' => ['flipt'],

        /*
        |--------------------------------------------------------------------------
        | TTL (Time To Live)
        |--------------------------------------------------------------------------
        | Value: Integer (in Seconds)
        |
        | Time to live or hop limit is a mechanism which limits the lifespan or
        | lifetime of data in a computer or network. TTL may be implemented as
        | a counter or timestamp attached to or embedded in the data.
        | Once the prescribed event count or timespan has elapsed, data is
        | discarded or revalidated
        |
        */
        'ttl' => env('FLIPT_CACHE_TTL', 60),
    ],
];
