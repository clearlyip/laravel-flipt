<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\ProposalState;

readonly class EnvironmentProposalDetails
{
    public function __construct(
        public string $url,
        public ProposalState $state,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: (string) ($data['url'] ?? ''),
            state: ProposalState::from((string) ($data['state'] ?? '')),
        );
    }
}
