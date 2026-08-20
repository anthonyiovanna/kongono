<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic;
use Throwable;

/** Preserves both failures when application work and exact scope cleanup fail. */
#[Api(ApiStatus::Experimental)]
final class OperationCleanupException extends WebApplicationException
{
    private readonly Throwable $primaryFailure;
    private readonly Throwable $cleanupFailure;

    public function __construct(Throwable $primaryFailure, Throwable $cleanupFailure)
    {
        $this->primaryFailure = $primaryFailure;
        $this->cleanupFailure = $cleanupFailure;
        parent::__construct(WebApplicationDiagnostic::OperationAndCleanupFailed, $primaryFailure);
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
