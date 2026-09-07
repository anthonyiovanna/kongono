<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use Throwable;

/** Carries exact zero-attempt rejection evidence without disclosing source values. */
#[Api(ApiStatus::Experimental)]
final class InvocationRejectedException extends ModelInvocationRuntimeException
{
    public function __construct(
        private readonly InvocationReceipt $receipt,
        ?Throwable $previous = null,
    ) {
        if (InvocationOutcome::Rejected !== $receipt->outcome() || 0 !== $receipt->attemptCount() || !$receipt->cleanupComplete()) {
            throw InvalidInvocationRuntimeException::invalid('rejected_receipt_relation');
        }
        parent::__construct(ModelInvocationRuntimeDiagnostic::Rejected, ['stage' => 'preflight', 'outcome' => 'rejected', 'attempt_count' => 0], $previous);
    }

    public function receipt(): InvocationReceipt
    {
        return $this->receipt;
    }
}
