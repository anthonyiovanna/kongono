<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageRuntime\Diagnostic\StorageRuntimeDiagnostic;
use Throwable;

/** Retains every cleanup failure without disclosing provider data. */
#[Api(ApiStatus::Experimental)]
final class StorageRuntimeCleanupException extends StorageRuntimeException
{
    /** @param non-empty-list<Throwable> $cleanupFailures */
    public function __construct(string $operation, private readonly array $cleanupFailures)
    {
        parent::__construct(StorageRuntimeDiagnostic::CleanupFailed, ['violation' => 'cleanup_failed', 'operation' => $operation, 'cleanup_count' => count($cleanupFailures)], $cleanupFailures[0]);
    }

    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
