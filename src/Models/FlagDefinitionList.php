<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class FlagDefinitionList
{
    public function __construct(
        /** @var FlagDefinition[] */
        public array $flags,
        #[\SensitiveParameter]
        public string $nextPageToken,
        public int $totalCount,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            flags: array_map(
                FlagDefinition::fromArray(...),
                is_array($data['flags'] ?? null) ? $data['flags'] : [],
            ),
            nextPageToken: (string) ($data['nextPageToken'] ?? ''),
            totalCount: (int) ($data['totalCount'] ?? 0),
        );
    }
}
