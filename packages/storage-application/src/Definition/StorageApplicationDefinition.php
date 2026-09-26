<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Definition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Exception\InvalidStorageApplicationException;

/** Carries one explicit application root, contained source path, and publication root. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageApplicationDefinition
{
    public function __construct(
        private string $applicationRoot,
        private string $sourceFile,
        private string $outputDirectory,
    ) {
        foreach ([$applicationRoot, $sourceFile, $outputDirectory] as $value) {
            if ('' === $value || str_contains($value, "\0")) {
                throw InvalidStorageApplicationException::invalid('invalid_path');
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
    public function outputDirectory(): string
    {
        return $this->outputDirectory;
    }
}
