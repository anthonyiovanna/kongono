<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Owns fixed non-disclosing presentation for command-build failures. */
#[Api(ApiStatus::Internal)]
abstract class ConsoleBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly ConsoleBuildDiagnostic $diagnostic,
        ConsoleBuildFailureCategory $failureCategory,
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
