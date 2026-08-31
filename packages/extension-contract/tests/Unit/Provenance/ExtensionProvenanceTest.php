<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Provenance;

use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExtensionProvenanceTest extends TestCase
{
    public function testProvenancePreservesExactlyTwoBoundedFields(): void
    {
        $identity = new ExtensionSourceIdentity(str_repeat('a', 64));
        $provenance = new ExtensionProvenance($identity, str_repeat('b', 64));
        self::assertSame($identity, $provenance->sourceIdentity());
        self::assertSame(str_repeat('b', 64), $provenance->sourceSha256());
        self::assertTrue($identity->equals(new ExtensionSourceIdentity(str_repeat('a', 64))));
        $properties = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), (new ReflectionClass($provenance))->getProperties());
        sort($properties);
        self::assertSame(['sourceIdentity', 'sourceSha256'], $properties);
    }

    public function testMalformedIdentityAndDigestAreRejectedWithoutDisclosure(): void
    {
        foreach (['C:\\secret\\source.json', str_repeat('A', 64), str_repeat('a', 63), str_repeat('g', 64)] as $raw) {
            try {
                new ExtensionSourceIdentity($raw);
                self::fail('Malformed source identity was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertSame('KGN-EXTENSION-1203', $exception->diagnosticId());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
