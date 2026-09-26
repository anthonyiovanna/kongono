<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Configuration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Internal\LocalAdapterGuard;

/** Admits one explicit existing ordinary directory as bounded authority. */
#[Api(ApiStatus::Experimental)]
final readonly class LocalStorageRoot
{
    private string $resolved;

    public function __construct(string $root)
    {
        $this->resolved = LocalAdapterGuard::root($root);
    }

    /** @internal Adapter-private capability material; never diagnostic data. */
    public function resolvedPath(): string
    {
        return $this->resolved;
    }
}
