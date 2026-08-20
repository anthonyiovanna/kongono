<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Kernel\Exception\ExceptionInterface;
use RuntimeException;

/** Test-only supported diagnostic source with no request-derived context. */
final class FixtureKernelDiagnosticException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $context;

    public function __construct()
    {
        parent::__construct('fixture-kernel-diagnostic-failure-sentinel');
        $this->context = new DiagnosticContext(new DiagnosticContextSchema(
            'Run Phase 2 fixture diagnostic route',
            'Trusted fixture output requires both typed permissions',
        ));
    }

    public function diagnosticContext(): DiagnosticContext
    {
        return $this->context;
    }

    public function diagnosticId(): string
    {
        return 'KGN-KERNEL-9001';
    }

    public function diagnosticTitle(): string
    {
        return 'Phase 2 fixture diagnostic failure';
    }

    public function diagnosticRemediation(): string
    {
        return 'Inspect the deterministic Phase 2 fixture';
    }
}
