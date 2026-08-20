<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Fixture;

use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Kernel\Exception\ExceptionInterface;
use RuntimeException;

/** Test-only supported source whose first diagnostic access fails observably. */
final class ExplosiveKernelDiagnosticException extends RuntimeException implements ExceptionInterface
{
    private int $accessorCalls = 0;

    public function diagnosticContext(): DiagnosticContext
    {
        ++$this->accessorCalls;

        throw new RuntimeException('explosive-diagnostic-context-sentinel');
    }

    public function diagnosticId(): string
    {
        ++$this->accessorCalls;

        throw new RuntimeException('explosive-diagnostic-id-sentinel');
    }

    public function diagnosticTitle(): string
    {
        ++$this->accessorCalls;

        throw new RuntimeException('explosive-diagnostic-title-sentinel');
    }

    public function diagnosticRemediation(): string
    {
        ++$this->accessorCalls;

        throw new RuntimeException('explosive-diagnostic-remediation-sentinel');
    }

    public function accessorCalls(): int
    {
        return $this->accessorCalls;
    }
}
