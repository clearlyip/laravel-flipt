<?php

namespace Clearlyip\LaravelFlipt\Models;

readonly class Change
{
    public function __construct(
        public string $revision,
        public string $message,
        public string $authorName,
        public string $authorEmail,
        public string $timestamp,
        public string $scmUrl,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            revision: (string) ($data['revision'] ?? ''),
            message: (string) ($data['message'] ?? ''),
            authorName: (string) ($data['authorName'] ?? ''),
            authorEmail: (string) ($data['authorEmail'] ?? ''),
            timestamp: (string) ($data['timestamp'] ?? ''),
            scmUrl: (string) ($data['scmUrl'] ?? ''),
        );
    }
}
