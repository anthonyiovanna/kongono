<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Tests\Unit;

use Kongono\SecurityHttp\Diagnostic\SecurityHttpDiagnostic;
use Kongono\SecurityHttp\Exception\BrowserSessionAuthenticationException;
use Kongono\SecurityHttp\Exception\BrowserSessionRequestException;
use Kongono\SecurityHttp\Exception\InvalidBrowserSessionValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SecurityHttpDiagnosticTest extends TestCase
{
    public function testDiagnosticsAndExceptionsAreBoundedAndNondisclosing(): void
    {
        self::assertSame([
            'KGN-SECURITY-HTTP-1001',
            'KGN-SECURITY-HTTP-1002',
            'KGN-SECURITY-HTTP-1003',
            'KGN-SECURITY-HTTP-1004',
            'KGN-SECURITY-HTTP-1005',
            'KGN-SECURITY-HTTP-1006',
        ], array_column(SecurityHttpDiagnostic::cases(), 'value'));

        $secret = 'adversarial-session-origin-resource-action-failure';
        foreach ([
            InvalidBrowserSessionValueException::wireSyntax(),
            InvalidBrowserSessionValueException::trustedOrigin(),
            InvalidBrowserSessionValueException::evidenceShape(),
            BrowserSessionRequestException::cookie(),
            BrowserSessionRequestException::transport(),
            BrowserSessionRequestException::csrf(),
            BrowserSessionAuthenticationException::rejected(),
        ] as $exception) {
            self::assertStringNotContainsString($secret, (string) $exception);
            self::assertStringNotContainsString($secret, json_encode($exception->diagnosticContext(), JSON_THROW_ON_ERROR));
        }

        foreach ([
            \Kongono\SecurityHttp\BrowserSessionIdentifier::class,
            \Kongono\SecurityHttp\BrowserSessionCsrfProof::class,
            \Kongono\SecurityHttp\BrowserSessionEvidence::class,
        ] as $class) {
            $reflection = new ReflectionClass($class);
            self::assertFalse($reflection->hasMethod('__toString'));
            self::assertFalse($reflection->hasMethod('jsonSerialize'));
            self::assertFalse($reflection->hasMethod('__debugInfo'));
        }
    }
}
