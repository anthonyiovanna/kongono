<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Retains one primary failure together with one later cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class CombinedDatabaseFailureException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    public function __construct(
        private readonly Throwable $primaryFailure,
        private readonly Throwable $cleanupFailure,
    ) {
        $this->diagnosticContext = DatabaseDiagnostic::Cleanup->context();
        parent::__construct(DatabaseDiagnostic::Cleanup->title(), 0, $primaryFailure);
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    public function cleanupFailure(): Throwable
    {
        return $this->cleanupFailure;
    }

    public function diagnosticId(): string
    {
        return DatabaseDiagnostic::Cleanup->value;
    }
    public function diagnosticTitle(): string
    {
        return DatabaseDiagnostic::Cleanup->title();
    }
    public function diagnosticRemediation(): string
    {
        return DatabaseDiagnostic::Cleanup->remediation();
    }
    public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnosticId(), $this->diagnosticTitle());
    }
}
