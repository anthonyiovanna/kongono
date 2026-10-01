<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Security;

use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use PHPUnit\Framework\TestCase;

final class EventContractDisclosureTest extends TestCase
{
    public function testDeliberateFailuresHaveOnlyFixedSafePresentationWithTraceArgumentsEnabled(): void
    {
        $original = ini_get('zend.exception_ignore_args');
        self::assertIsString($original);

        try {
            $previous = ini_set('zend.exception_ignore_args', '0');
            self::assertIsString($previous);
            self::assertSame($original, $previous);
            self::assertSame('0', ini_get('zend.exception_ignore_args'));

            $handler = new class implements EventHandler {
                public int $calls = 0;

                public function handle(ApplicationEvent $event): void
                {
                    ++$this->calls;
                }

                public function __toString(): string
                {
                    throw new \LogicException('Handler must not be stringified.');
                }
            };
            $secret = 'PRIVATE-EVENT-PAYLOAD-AND-HANDLER';
            $failures = [
                static fn(): EventRegistration => new EventRegistration($secret, $handler),
                static fn(): never => throw InvalidEventContractException::invalidEventType($secret),
                static fn(): never => throw InvalidEventContractException::invalidRegistration($secret),
                static fn(): never => throw InvalidEventContractException::contractBoundExceeded($secret),
            ];
            foreach ($failures as $make) {
                try {
                    $make();
                    self::fail('Expected a deliberate contract failure.');
                } catch (InvalidEventContractException $failure) {
                    self::assertStringNotContainsString($secret, $failure->getMessage());
                    self::assertStringNotContainsString($secret, (string) $failure);
                    self::assertCount(1, $failure->diagnosticContext()->fields());
                    foreach ($failure->diagnosticContext()->fields() as $field) {
                        self::assertSame('violation', $field->key());
                        self::assertStringNotContainsString($secret, (string) $field->value());
                    }
                }
            }
            self::assertSame(0, $handler->calls);
        } finally {
            $restored = ini_set('zend.exception_ignore_args', $original);
            self::assertIsString($restored);
            self::assertSame($original, ini_get('zend.exception_ignore_args'));
        }
    }
}
