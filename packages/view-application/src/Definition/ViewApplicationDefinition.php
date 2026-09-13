<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Definition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Exception\InvalidViewApplicationException;

/** Carries one explicit application root source file and artifact output root. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewApplicationDefinition
{
    public function __construct(
        private string $applicationRoot,
        private string $sourceFile,
        private string $artifactOutputRoot,
    ) {
        foreach ([$applicationRoot, $sourceFile, $artifactOutputRoot] as $value) {
            if ('' === $value) {
                throw InvalidViewApplicationException::invalid('empty_value');
            }
            if (str_contains($value, "\0")) {
                throw InvalidViewApplicationException::invalid('nul_byte');
            }
        }
    }

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }

    public function sourceFile(): string
    {
        return $this->sourceFile;
    }

    public function artifactOutputRoot(): string
    {
        return $this->artifactOutputRoot;
    }
}
