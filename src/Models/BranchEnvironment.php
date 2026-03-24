<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class BranchEnvironment
{
    public function __construct(
        public string $environmentKey,
        public string $key,
        public string $ref,
        public ?EnvironmentProposalDetails $proposal = null,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            environmentKey: (string) ($data['environmentKey'] ?? ''),
            key: (string) ($data['key'] ?? ''),
            ref: (string) ($data['ref'] ?? ''),
            proposal: array_key_exists('proposal', $data)
                ? EnvironmentProposalDetails::fromArray(
                    is_array($data['proposal']) ? $data['proposal'] : [],
                )
                : null,
        );
    }
}
