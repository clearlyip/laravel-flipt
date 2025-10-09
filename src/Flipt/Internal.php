<?php

namespace Clearlyip\LaravelFlipt\Flipt;

use Clearlyip\LaravelFlipt\Flipt;

readonly class Internal
{
    public function __construct(public Flipt $client)
    {
        //
    }

    public function snapshot()
    {
        $r = $this->client->apiRequest(
            method: 'GET',
            path: '/internal/v1/evaluation/snapshot/namespace/' .
                $this->client->namespace,
        );
    }
}
