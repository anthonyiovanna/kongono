<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use RuntimeException;
use Throwable;

/** Owns fixed non-disclosing presentation for persistence-build failures. */
#[Api(ApiStatus::Internal)]
abstract class PersistenceBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly PersistenceBuildDiagnostic $diagnostic,
        PersistenceBuildFailureCategory $failureCategory,
        bool $artifactCurrent = false,
        ?Throwable $previous = null,
    ) {
        $this->diagnosticContext = $diagnostic->context($failureCategory->value, $artifactCurrent);
        parent::__construct($diagnostic->title(), 0, $previous);
    }

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    final public function diagnosticId(): string
    {
        return $this->diagnostic->value;
    }
    final public function diagnosticTitle(): string
    {
        return $this->diagnostic->title();
    }
    final public function diagnosticRemediation(): string
    {
        return $this->diagnostic->remediation();
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
