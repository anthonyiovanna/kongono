<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Retains a primary lifecycle failure together with every observed cleanup failure. */
#[Api(ApiStatus::Public)]
final class CompositionLifecycleException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param list<Throwable> $cleanupFailures */
    public function __construct(
        private readonly Throwable $primaryFailure,
        private readonly array $cleanupFailures,
        private readonly string $boundary,
    ) {
        $this->diagnosticContext = CompositionDiagnostic::LifecycleFailuresCombined->lifecycleContext($boundary, count($cleanupFailures));
        parent::__construct(CompositionDiagnostic::LifecycleFailuresCombined->title(), 0, $primaryFailure);
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }
    /** @return list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
    public function boundary(): string
    {
        return $this->boundary;
    }
    public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    public function diagnosticId(): string
    {
        return CompositionDiagnostic::LifecycleFailuresCombined->value;
    }
    public function diagnosticTitle(): string
    {
        return CompositionDiagnostic::LifecycleFailuresCombined->title();
    }
    public function diagnosticRemediation(): string
    {
        return CompositionDiagnostic::LifecycleFailuresCombined->remediation();
    }
    public function __toString(): string
    {
        return sprintf('%s: %s', CompositionDiagnostic::LifecycleFailuresCombined->value, CompositionDiagnostic::LifecycleFailuresCombined->title());
    }
}
