<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Version;

use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionVersionTest extends TestCase
{
    public function testOpaqueVersionBoundsAndEqualityAreExact(): void
    {
        self::assertSame('a', (new ExtensionVersion('a'))->value());
        self::assertSame(str_repeat('z', 64), (new ExtensionVersion(str_repeat('z', 64)))->value());
        self::assertTrue((new ExtensionVersion('v1_0'))->equals(new ExtensionVersion('v1_0')));
        self::assertFalse((new ExtensionVersion('v1'))->equals(new ExtensionVersion('v2')));
    }

    public function testMalformedOpaqueVersionsAreRejectedWithoutSemverInterpretation(): void
    {
        foreach (['', 'V1', '.1', '1-', '1..2', '1+build', 'é', str_repeat('a', 65)] as $raw) {
            try {
                new ExtensionVersion($raw);
                self::fail('Malformed extension version was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertSame('KGN-EXTENSION-1202', $exception->diagnosticId());
                if ('' !== $raw) {
                    self::assertStringNotContainsString($raw, (string) $exception);
                }
            }
        }
    }
}
