<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Tests\Unit\Exception;

use Kongono\SecurityAudit\Exception\ExceptionInterface;
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditSchemaException;
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditValueException;
use Kongono\SecurityAudit\Exception\SecurityAuditException;
use Kongono\SecurityAudit\Exception\SecurityAuditRecordingException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class SecurityAuditExceptionTest extends TestCase
{
    public function testCatchBoundaryFactoriesAndNondisclosureAreExact(): void
    {
        self::assertContains(\Throwable::class, (new ReflectionClass(ExceptionInterface::class))->getInterfaceNames());
        self::assertTrue((new ReflectionClass(SecurityAuditException::class))->isAbstract());
        $previous = new RuntimeException('protected-audit-recorder-message');
        $exceptions = [InvalidSecurityAuditSchemaException::field(), InvalidSecurityAuditSchemaException::schema(), InvalidSecurityAuditValueException::attributes(), SecurityAuditRecordingException::failed($previous)];
        foreach ($exceptions as $exception) {
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            self::assertStringNotContainsString('protected-audit-recorder-message', $exception->getMessage());
            self::assertStringNotContainsString('protected-audit-recorder-message', (string) $exception);
        }
        self::assertSame($previous, $exceptions[3]->getPrevious());
    }
}
