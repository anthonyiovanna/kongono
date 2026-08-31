<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Identity;

use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use PHPUnit\Framework\TestCase;

final class DeclarationSetIdentityTest extends TestCase
{
    public function testExactLowercaseDigestIsPreservedAndComparable(): void
    {
        $digest = str_repeat('a', 64);
        $identity = new DeclarationSetIdentity($digest);

        self::assertSame($digest, $identity->sha256());
        self::assertSame($digest, $identity->value());
        self::assertTrue($identity->equals(new DeclarationSetIdentity($digest)));
        self::assertFalse($identity->equals(new DeclarationSetIdentity(str_repeat('b', 64))));
    }

    public function testMalformedDigestsAreRejectedWithoutDisclosure(): void
    {
        foreach (['', str_repeat('a', 63), str_repeat('A', 64), str_repeat('g', 64)] as $rejected) {
            try {
                new DeclarationSetIdentity($rejected);
                self::fail('Malformed declaration-set identity was accepted.');
            } catch (InvalidExtensionBuildException $exception) {
                self::assertSame('KGN-EXTENSION-1208', $exception->diagnosticId());
                if ('' !== $rejected) {
                    self::assertStringNotContainsString($rejected, (string) $exception);
                }
            }
        }
    }
}
