<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;

/** Creates bounded receipts using only the exact caller-supplied identity. */
#[Api(ApiStatus::Internal)]
final class ReceiptFactory
{
    public static function create(
        InvocationRuntimeRequest $request,
        InvocationOutcome $outcome,
        int $attemptCount,
        ?ModelResult $result,
        bool $cleanupComplete,
    ): InvocationReceipt {
        $succeeded = InvocationOutcome::Succeeded === $outcome;

        return new InvocationReceipt(
            $request->receiptIdentity(),
            $request->source()->request()->identity(),
            $request->source()->request()->profile(),
            $outcome,
            $attemptCount,
            $succeeded ? $result?->identity() : null,
            $succeeded ? $result?->usage() : null,
            $succeeded ? $result?->cost() : null,
            $cleanupComplete,
        );
    }

    private function __construct() {}
}
