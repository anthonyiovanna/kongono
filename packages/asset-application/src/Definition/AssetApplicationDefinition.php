<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Definition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Exception\InvalidAssetApplicationException;

/** Carries one explicit application root source definition and publication root. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetApplicationDefinition
{
    public function __construct(
        private string $applicationRoot,
        private string $sourceFile,
        private string $outputDirectory,
    ) {
        foreach ([$applicationRoot, $sourceFile, $outputDirectory] as $value) {
            if ('' === $value) {
                throw InvalidAssetApplicationException::invalid('empty_value');
            }
            if (str_contains($value, "\0")) {
                throw InvalidAssetApplicationException::invalid('nul_byte');
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
