<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Contracts\Response;

readonly class ListEnvironmentsResponse implements Response
{
    public function __construct(
        /** @var Environment[] */
        public array $environments,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(environments: array_map(
            Environment::fromArray(...),
            is_array($data['environments'] ?? null)
                ? $data['environments']
                : [],
        ));
    }
}
