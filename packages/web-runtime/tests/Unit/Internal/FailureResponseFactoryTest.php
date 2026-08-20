<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Unit\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Web\Runtime\Internal\FailureResponseFactory;
use PHPUnit\Framework\TestCase;

final class FailureResponseFactoryTest extends TestCase
{
    public function testGenericResponseIsFreshFixedAndDisclosureFree(): void
    {
        $factory = new FailureResponseFactory();
        $first = $factory->generic();
        $second = $factory->generic();

        self::assertSame(500, $first->getStatusCode());
        self::assertSame([
            'Content-Type' => ['text/plain; charset=utf-8'],
            'Cache-Control' => ['no-store'],
            'X-Content-Type-Options' => ['nosniff'],
        ], $first->getHeaders());
        self::assertSame("Internal Server Error\n", (string) $first->getBody());
        self::assertNotSame($first, $second);
        self::assertNotSame($first->getBody(), $second->getBody());
        $this->assertForbiddenHeadersAbsent($first->getHeaders());
        foreach (['KGN-', 'Exception', 'trace', 'request-sentinel', 'Kongono', 'PHP'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, (string) $first->getBody());
        }
    }

    public function testTrustedResponseIsFreshAndPreservesExactDocumentBytes(): void
    {
        $document = "<!doctype html>\n<p>exact-renderer-document</p>\n";
        $factory = new FailureResponseFactory();
        $first = $factory->trusted($document);
        $second = $factory->trusted($document);

        self::assertSame(500, $first->getStatusCode());
        self::assertSame([
            'Content-Type' => ['text/html; charset=utf-8'],
            'Cache-Control' => ['no-store'],
            'X-Content-Type-Options' => ['nosniff'],
            'Content-Security-Policy' => ["default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'"],
        ], $first->getHeaders());
        self::assertSame($document, (string) $first->getBody());
        self::assertNotSame($first, $second);
        self::assertNotSame($first->getBody(), $second->getBody());
        $this->assertForbiddenHeadersAbsent($first->getHeaders());
    }

    public function testFactoryShapeStatusPropertiesAndMethodsAreExact(): void
    {
        $reflection = new \ReflectionClass(FailureResponseFactory::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => FailureResponseFactory::class === $property->getDeclaringClass()->getName()));
        self::assertCount(1, $properties);
        self::assertSame('streamFactory', $properties[0]->getName());
        self::assertSame(StreamFactory::class, (string) $properties[0]->getType());
        self::assertTrue($properties[0]->isPrivate());
        self::assertTrue($properties[0]->isReadOnly());
        self::assertSame([], $reflection->getConstructor()?->getParameters());
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => FailureResponseFactory::class === $method->getDeclaringClass()->getName()),
        );
        sort($methods);
        self::assertSame(['__construct', 'generic', 'trusted'], $methods);
    }

    /** @param array<array-key, array<array-key, string>> $headers */
    private function assertForbiddenHeadersAbsent(array $headers): void
    {
        foreach (['Content-Length', 'Server', 'Set-Cookie', 'Location', 'X-Diagnostic-Id', 'X-Incident-Id'] as $forbidden) {
            self::assertArrayNotHasKey($forbidden, $headers);
        }
    }
}
