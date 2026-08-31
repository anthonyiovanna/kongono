<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Identity;

use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use PHPUnit\Framework\TestCase;

final class ExtensionIdentityTest extends TestCase
{
    public function testMinimumMaximumAndEqualityAreExact(): void
    {
        self::assertSame('a/b', (new ExtensionIdentity('a/b'))->value());
        $maximum = str_repeat('a', 64) . '/' . str_repeat('b', 64);
        self::assertSame($maximum, (new ExtensionIdentity($maximum))->value());
        self::assertTrue((new ExtensionIdentity('vendor/name'))->equals(new ExtensionIdentity('vendor/name')));
        self::assertFalse((new ExtensionIdentity('vendor/name'))->equals(new ExtensionIdentity('vendor/other')));
    }

    public function testMalformedGrammarClassesAreRejectedWithoutDisclosure(): void
    {
        foreach (['', 'a', '/a', 'a/', 'A/b', 'a/B', 'a//b', 'a..b/c', 'a/b-', 'a b/c', 'é/b', str_repeat('a', 65) . '/b'] as $raw) {
            try {
                new ExtensionIdentity($raw);
                self::fail('Malformed extension identity was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertSame('KGN-EXTENSION-1201', $exception->diagnosticId());
                if (strlen($raw) > 8) {
                    self::assertStringNotContainsString($raw, (string) $exception);
                }
            }
        }
    }
}
