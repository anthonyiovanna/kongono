<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Unit\Diagnostic;

use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelInvocationRuntimeDiagnosticTest extends TestCase
{
    /** @return iterable<string, array{ModelInvocationRuntimeDiagnostic, string}> */
    public static function identifiers(): iterable
    {
        yield 'input' => [ModelInvocationRuntimeDiagnostic::InvalidInput, 'KGN-MODEL-RUNTIME-1417'];
        yield 'rejected' => [ModelInvocationRuntimeDiagnostic::Rejected, 'KGN-MODEL-RUNTIME-1418'];
        yield 'adapter' => [ModelInvocationRuntimeDiagnostic::AdapterFailed, 'KGN-MODEL-RUNTIME-1419'];
        yield 'protocol' => [ModelInvocationRuntimeDiagnostic::ProtocolViolated, 'KGN-MODEL-RUNTIME-1420'];
        yield 'budget' => [ModelInvocationRuntimeDiagnostic::BudgetExceeded, 'KGN-MODEL-RUNTIME-1421'];
        yield 'probe' => [ModelInvocationRuntimeDiagnostic::ProbeFailed, 'KGN-MODEL-RUNTIME-1422'];
        yield 'cleanup' => [ModelInvocationRuntimeDiagnostic::CleanupFailed, 'KGN-MODEL-RUNTIME-1423'];
        yield 'combined' => [ModelInvocationRuntimeDiagnostic::OperationAndCleanupFailed, 'KGN-MODEL-RUNTIME-1424'];
    }

    #[DataProvider('identifiers')]
    public function testClosedIdentifiersAreActionable(ModelInvocationRuntimeDiagnostic $diagnostic, string $identifier): void
    {
        self::assertSame($identifier, $diagnostic->value);
        self::assertNotSame('', $diagnostic->title());
        self::assertNotSame('', $diagnostic->remediation());
    }
}
