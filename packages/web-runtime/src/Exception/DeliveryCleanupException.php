<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use Throwable;

/** Preserves both failures when delivery and exact transport cleanup fail. */
#[Api(ApiStatus::Experimental)]
final class DeliveryCleanupException extends WebRuntimeException
{
    private readonly Throwable $primaryFailure;
    private readonly Throwable $cleanupFailure;

    public function __construct(Throwable $primaryFailure, Throwable $cleanupFailure)
    {
        $this->primaryFailure = $primaryFailure;
        $this->cleanupFailure = $cleanupFailure;
        parent::__construct(WebRuntimeDiagnostic::DeliveryAndCleanupFailed, $primaryFailure);
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    public function cleanupFailure(): Throwable
    {
        return $this->cleanupFailure;
    }
}
