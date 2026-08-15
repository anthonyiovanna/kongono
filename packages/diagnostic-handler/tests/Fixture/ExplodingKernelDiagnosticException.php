<?php

declare(strict_types=1);

namespace Kongono\DiagnosticHandler\Tests\Fixture;

use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Kernel\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;

final class ExplodingKernelDiagnosticException extends RuntimeException implements ExceptionInterface
{
    public function __construct(private readonly Throwable $sentinel)
    {
        parent::__construct('Diagnostic access must fail.');
    }

    public function diagnosticId(): string
    {
        throw $this->sentinel;
    }

    public function diagnosticTitle(): string
    {
        throw $this->sentinel;
    }

    public function diagnosticRemediation(): string
    {
        throw $this->sentinel;
    }

    public function diagnosticContext(): DiagnosticContext
    {
        throw $this->sentinel;
    }
}
