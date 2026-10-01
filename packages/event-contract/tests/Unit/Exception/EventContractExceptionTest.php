<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Unit\Exception;

use Kongono\EventContract\Exception\ExceptionInterface;
use Kongono\EventContract\Exception\InvalidEventContractException;
use PHPUnit\Framework\TestCase;

final class EventContractExceptionTest extends TestCase
{
    public function testFactoriesHaveDistinctCodesAndClosedViolationContext(): void
    {
        $failures = [
            [InvalidEventContractException::invalidEventType('event_type_grammar'), 'KGN-EVENT-CONTRACT-2211', 'event_type_grammar'],
            [InvalidEventContractException::invalidRegistration('registrations_not_list'), 'KGN-EVENT-CONTRACT-2212', 'registrations_not_list'],
            [InvalidEventContractException::duplicateBinding(), 'KGN-EVENT-CONTRACT-2213', 'duplicate_binding'],
            [InvalidEventContractException::contractBoundExceeded('registration_count'), 'KGN-EVENT-CONTRACT-2214', 'registration_count'],
        ];
        foreach ($failures as [$failure, $id, $violation]) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertSame($id, $failure->diagnosticId());
            self::assertSame($violation, $failure->diagnosticContext()->fields()[0]->value());
            self::assertSame($failure->diagnosticTitle() . '.', $failure->getMessage());
            self::assertStringContainsString($id, (string) $failure);
            self::assertStringContainsString($violation, (string) $failure);
            self::assertNotSame('', $failure->diagnosticRemediation());
        }
    }

    public function testUnsupportedFactoryViolationBecomesFixedToken(): void
    {
        foreach ([
            InvalidEventContractException::invalidEventType('SECRET'),
            InvalidEventContractException::invalidRegistration('SECRET'),
            InvalidEventContractException::contractBoundExceeded('SECRET'),
        ] as $failure) {
            self::assertSame('unsupported_violation', $failure->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString('SECRET', $failure->getMessage());
            self::assertStringNotContainsString('SECRET', (string) $failure);
        }
    }
}
