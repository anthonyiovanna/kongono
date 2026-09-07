<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use Throwable;

/** Retains every exact close failure after an otherwise completed attempt. */
#[Api(ApiStatus::Experimental)]
final class InvocationCleanupException extends ModelInvocationRuntimeException
{
    /** @var non-empty-list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param list<Throwable> $cleanupFailures */
    public function __construct(
        private readonly InvocationReceipt $receipt,
        array $cleanupFailures,
    ) {
        if ([] === $cleanupFailures) {
            throw InvalidInvocationRuntimeException::invalid('cleanup_failures_empty');
        }
        if (InvocationOutcome::Uncertain !== $receipt->outcome() || 1 !== $receipt->attemptCount() || $receipt->cleanupComplete()) {
            throw InvalidInvocationRuntimeException::invalid('cleanup_receipt_relation');
        }
        $this->cleanupFailures = $cleanupFailures;
        parent::__construct(ModelInvocationRuntimeDiagnostic::CleanupFailed, ['outcome' => 'uncertain', 'cleanup_count' => count($cleanupFailures)], $cleanupFailures[0]);
    }

    public function receipt(): InvocationReceipt
    {
        return $this->receipt;
    }

    /** @return non-empty-list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
