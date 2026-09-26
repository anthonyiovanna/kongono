<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageRuntime\Diagnostic\StorageRuntimeDiagnostic;
use Throwable;

/** Retains one primary failure and every separate cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class StorageRuntimeOperationAndCleanupException extends StorageRuntimeException
{
    /** @param non-empty-list<Throwable> $cleanupFailures */
    public function __construct(string $operation, private readonly Throwable $primaryFailure, private readonly array $cleanupFailures)
    {
        parent::__construct(StorageRuntimeDiagnostic::OperationAndCleanupFailed, ['violation' => 'operation_and_cleanup_failed', 'operation' => $operation, 'cleanup_count' => count($cleanupFailures), 'primary_present' => true], $primaryFailure);
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
