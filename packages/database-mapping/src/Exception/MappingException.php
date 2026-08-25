<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Owns fixed non-disclosing presentation for mapping failures. */
#[Api(ApiStatus::Internal)]
abstract class MappingException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly MappingDiagnostic $diagnostic,
        MappingFailureCategory $failureCategory,
        int $ordinal = 0,
        int $count = 0,
        bool $artifactCurrent = false,
        string $operationKind = 'build',
        bool $cleanupEvidence = false,
        ?Throwable $previous = null,
    ) {
        $this->diagnosticContext = $diagnostic->context(
            $failureCategory->value,
            $ordinal,
            $count,
            $artifactCurrent,
            $operationKind,
            $cleanupEvidence,
        );
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
