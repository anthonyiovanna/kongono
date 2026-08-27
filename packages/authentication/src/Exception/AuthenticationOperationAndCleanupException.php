<?php

declare(strict_types=1);

namespace Kongono\Authentication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Diagnostic\AuthenticationDiagnostic;
use Throwable;

/** Preserves one operation failure together with one later cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class AuthenticationOperationAndCleanupException extends AuthenticationException
{
    private function __construct(
        private readonly Throwable $operationFailure,
        private readonly Throwable $cleanupFailure,
    ) {
        parent::__construct(AuthenticationDiagnostic::CombinedFailure, $operationFailure);
    }

    public static function from(Throwable $operationFailure, Throwable $cleanupFailure): self
    {
        return new self($operationFailure, $cleanupFailure);
    }

    public function operationFailure(): Throwable
    {
        return $this->operationFailure;
    }

    public function cleanupFailure(): Throwable
    {
        return $this->cleanupFailure;
    }
}
