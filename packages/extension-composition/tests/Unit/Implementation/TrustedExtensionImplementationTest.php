<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Implementation;

use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class TrustedExtensionImplementationTest extends TestCase
{
    public function testExactApplicationOwnedSymbolIsPreservedWithoutResolution(): void
    {
        $identity = new ExtensionIdentity('example/search');
        $version = new ExtensionVersion('v1');
        $implementation = new TrustedExtensionImplementation($identity, $version, 'Example\\Extension\\SearchExtension');
        self::assertSame($identity, $implementation->identity());
        self::assertSame($version, $implementation->version());
        self::assertSame('Example\\Extension\\SearchExtension', $implementation->className());
    }

    public function testAliasesLeadingSlashesAndMalformedSymbolsAreRejectedWithoutDisclosure(): void
    {
        foreach (['SearchExtension', '\\Example\\Search', 'Example\\bad-name', 'C:\\private\\secret.php', str_repeat('A', 256)] as $raw) {
            try {
                new TrustedExtensionImplementation(new ExtensionIdentity('example/search'), new ExtensionVersion('v1'), $raw);
                self::fail('Malformed symbol was accepted.');
            } catch (InvalidExtensionGrantPlanException $exception) {
                self::assertSame('KGN-EXTENSION-COMPOSITION-1201', $exception->diagnosticId());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
