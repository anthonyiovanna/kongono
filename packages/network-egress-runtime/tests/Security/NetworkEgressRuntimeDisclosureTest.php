<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Security;

use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use PHPUnit\Framework\TestCase;

final class NetworkEgressRuntimeDisclosureTest extends TestCase
{
    public function testProtectedCallerDataNeverEntersFailurePresentation(): void
    {
        $protected = ['secret.example.test', '192.0.2.99', 'Bearer secret', 'C:\\protected\\path', 'foreign throwable detail'];
        $failure = InvalidEgressRuntimeException::invalid('address_grammar');
        foreach ($protected as $value) {
            self::assertStringNotContainsString($value, $failure->getMessage());
            self::assertStringNotContainsString($value, (string) $failure);
            self::assertStringNotContainsString($value, $failure->diagnosticRemediation());
        }
        self::assertLessThanOrEqual(256, strlen((string) $failure));
    }
}
