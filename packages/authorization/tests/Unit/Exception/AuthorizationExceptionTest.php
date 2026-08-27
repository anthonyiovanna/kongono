<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit\Exception;

use Kongono\Authorization\Exception\AuthorizationEvaluationException;
use Kongono\Authorization\Exception\ExceptionInterface;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthorizationExceptionTest extends TestCase
{
    public function testValidationFactoriesUseExactBoundedDiagnostics(): void
    {
        $failures = [
            [InvalidAuthorizationValueException::validation(), 'KGN-AUTHORIZATION-1001'],
            [InvalidAuthorizationValueException::registration(), 'KGN-AUTHORIZATION-1002'],
            [InvalidAuthorizationValueException::receipt(), 'KGN-AUTHORIZATION-1006'],
        ];

        foreach ($failures as [$failure, $identifier]) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertSame($identifier, $failure->diagnosticId());
            self::assertSame($identifier . ': ' . $failure->diagnosticTitle(), (string) $failure);
            self::assertNotSame('', $failure->diagnosticRemediation());
            self::assertSame(
                ['package', 'failure_category', 'remediation'],
                array_map(static fn($field): string => $field->key(), $failure->diagnosticContext()->fields()),
            );
        }
    }

    public function testEvaluationWrapperPreservesOnlyNativePreviousWithoutDisclosure(): void
    {
        $cause = new RuntimeException('synthetic evaluator detail');
        $failure = AuthorizationEvaluationException::from($cause);

        self::assertSame($cause, $failure->getPrevious());
        self::assertSame('KGN-AUTHORIZATION-1005', $failure->diagnosticId());
        self::assertStringNotContainsString($cause->getMessage(), (string) $failure);
        self::assertStringNotContainsString($cause->getMessage(), $failure->getMessage());
    }
}
