<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Tests\Unit;

use Kongono\SecurityHttp\BrowserSessionCookieEmitter;
use Kongono\SecurityHttp\BrowserSessionCsrfProof;
use Kongono\SecurityHttp\BrowserSessionEvidence;
use Kongono\SecurityHttp\BrowserSessionIdentifier;
use Kongono\SecurityHttp\BrowserSessionProfile;
use Kongono\SecurityHttp\Exception\InvalidBrowserSessionValueException;
use Kongono\SecurityHttp\TrustedOrigin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class BrowserSessionProfileTest extends TestCase
{
    public function testFixedProfileEvidenceAndCookieContract(): void
    {
        $identifier = new BrowserSessionIdentifier(str_repeat('A', 43));
        $proof = new BrowserSessionCsrfProof(str_repeat('b', 43));
        $evidence = new BrowserSessionEvidence($identifier, true, $proof);
        $emitter = new BrowserSessionCookieEmitter();

        self::assertSame('browser.session.v1', BrowserSessionProfile::NAME);
        self::assertSame('__Host-kongono_session', BrowserSessionProfile::COOKIE_NAME);
        self::assertSame('X-Kongono-CSRF', BrowserSessionProfile::CSRF_HEADER_NAME);
        self::assertSame(BrowserSessionProfile::NAME, $evidence->mechanism()->value());
        self::assertSame($identifier, $evidence->sessionIdentifier());
        self::assertSame($proof, $evidence->csrfProof());
        self::assertTrue($evidence->requiresCsrfProof());
        self::assertSame(
            '__Host-kongono_session=' . str_repeat('A', 43) . '; Path=/; Secure; HttpOnly; SameSite=Strict',
            $emitter->issue($identifier),
        );
        self::assertSame(
            '__Host-kongono_session=; Max-Age=0; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/; Secure; HttpOnly; SameSite=Strict',
            $emitter->expire(),
        );
    }

    public function testOpaqueValuesRejectEveryNonProfileShapeWithoutImplicitDisclosure(): void
    {
        foreach ([
            '', str_repeat('A', 42), str_repeat('A', 44), str_repeat('A', 42) . '=',
            str_repeat('A', 42) . '+', str_repeat('A', 42) . '/', str_repeat('A', 42) . ' ',
            str_repeat('A', 42) . "\n", str_repeat('A', 42) . "\r", str_repeat('A', 42) . 'é',
        ] as $invalid) {
            try {
                new BrowserSessionIdentifier($invalid);
                self::fail('invalid session identifier accepted');
            } catch (InvalidBrowserSessionValueException $exception) {
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, (string) $exception);
                }
            }
            try {
                new BrowserSessionCsrfProof($invalid);
                self::fail('invalid CSRF proof accepted');
            } catch (InvalidBrowserSessionValueException $exception) {
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, (string) $exception);
                }
            }
        }

        $identifier = new BrowserSessionIdentifier(str_repeat('_', 43));
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(BrowserSessionIdentifier::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
        );
        self::assertNotContains('__toString', $methods);
        self::assertNotContains('jsonSerialize', $methods);
        self::assertNotContains('__debugInfo', $methods);
        self::assertNotContains('__serialize', $methods);
    }

    public function testEvidenceShapeAndTrustedOriginFailClosed(): void
    {
        $identifier = new BrowserSessionIdentifier(str_repeat('A', 43));
        new BrowserSessionEvidence($identifier, false, null);
        self::assertSame('https://example.test:8443', (new TrustedOrigin('https://example.test:8443'))->value());
        self::assertSame('https://[2001:db8::1]', (new TrustedOrigin('https://[2001:db8::1]'))->value());

        foreach (['http://example.test', 'https://user@example.test', 'https://example.test/', 'HTTPS://example.test', 'https://example.test:0', "https://example.test\r"] as $origin) {
            try {
                new TrustedOrigin($origin);
                self::fail('invalid trusted origin accepted');
            } catch (InvalidBrowserSessionValueException $exception) {
                self::assertStringNotContainsString($origin, (string) $exception);
            }
        }

        $this->expectException(InvalidBrowserSessionValueException::class);
        new BrowserSessionEvidence($identifier, true, null);
    }
}
