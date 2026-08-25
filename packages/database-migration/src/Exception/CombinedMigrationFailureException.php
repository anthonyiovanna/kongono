<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Preserves one migration primary failure together with one cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class CombinedMigrationFailureException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $context;

    public function __construct(
        private readonly Throwable $primaryFailure,
        private readonly Throwable $cleanupFailure,
        ?string $profile = null,
    ) {
        $this->context = MigrationDiagnostic::UncertainOrCleanup->context(lifecyclePhase: 'cleanup', profile: $profile);
        parent::__construct(MigrationDiagnostic::UncertainOrCleanup->title(), 0, $primaryFailure);
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
        return MigrationDiagnostic::UncertainOrCleanup->value;
    }

    public function diagnosticTitle(): string
    {
        return MigrationDiagnostic::UncertainOrCleanup->title();
    }

    public function diagnosticRemediation(): string
    {
        return MigrationDiagnostic::UncertainOrCleanup->remediation();
    }

    public function diagnosticContext(): DiagnosticContext
    {
        return $this->context;
    }

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnosticId(), $this->diagnosticTitle());
    }
}
