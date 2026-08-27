<?php

declare(strict_types=1);

namespace Kongono\Authentication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Diagnostic\AuthenticationDiagnostic;
use Throwable;

/** Retains one cleanup failure while keeping safe presentation bounded. */
#[Api(ApiStatus::Experimental)]
final class AuthenticationCleanupException extends AuthenticationException
{
    private function __construct(private readonly Throwable $cleanupFailure)
    {
        parent::__construct(AuthenticationDiagnostic::Cleanup, $cleanupFailure);
    }

    public static function from(Throwable $cleanupFailure): self
    {
        return new self($cleanupFailure);
    }

    public function cleanupFailure(): Throwable
    {
        return $this->cleanupFailure;
    }
}
