<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use Throwable;

/** Retains the exact primary and every exact close failure as separate evidence. */
#[Api(ApiStatus::Experimental)]
final class InvocationOperationAndCleanupException extends ModelInvocationRuntimeException
{
    /** @var non-empty-list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param list<Throwable> $cleanupFailures */
    public function __construct(
        private readonly InvocationReceipt $receipt,
        private readonly Throwable $primaryFailure,
        array $cleanupFailures,
    ) {
        if ([] === $cleanupFailures) {
            throw InvalidInvocationRuntimeException::invalid('cleanup_failures_empty');
        }
        if (InvocationOutcome::Uncertain !== $receipt->outcome() || 1 !== $receipt->attemptCount() || $receipt->cleanupComplete()) {
            throw InvalidInvocationRuntimeException::invalid('cleanup_receipt_relation');
        }
        $this->cleanupFailures = $cleanupFailures;
        parent::__construct(ModelInvocationRuntimeDiagnostic::OperationAndCleanupFailed, ['primary_present' => true, 'cleanup_count' => count($cleanupFailures)], $primaryFailure);
    }

    public function receipt(): InvocationReceipt
    {
        return $this->receipt;
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
