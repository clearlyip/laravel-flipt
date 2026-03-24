<?php

namespace Clearlyip\LaravelFlipt\Models;

use Clearlyip\LaravelFlipt\Enums\ScmType;

readonly class EnvironmentConfiguration
{
    public function __construct(
        public string $ref,
        public string $directory,
        public string $remote,
        public string $base,
        public ScmType $scm,
    ) {
    }

    /**
     * @throws \ValueError
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ref: (string) ($data['ref'] ?? ''),
            directory: (string) ($data['directory'] ?? ''),
            remote: (string) ($data['remote'] ?? ''),
            base: (string) ($data['base'] ?? ''),
            scm: ScmType::from((string) ($data['scm'] ?? '')),
        );
    }
}
