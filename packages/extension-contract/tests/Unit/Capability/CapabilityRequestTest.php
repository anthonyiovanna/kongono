<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Capability;

use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use PHPUnit\Framework\TestCase;

final class CapabilityRequestTest extends TestCase
{
    public function testRequestPreservesExactInertFieldsAndIdentityEquality(): void
    {
        $identity = new CapabilityIdentity('filesystem.read', 65535);
        $purpose = new CapabilityPurpose('read_public_assets');
        $request = new CapabilityRequest($identity, $purpose, CapabilityRequirement::Required);

        self::assertSame($identity, $request->identity());
        self::assertSame($purpose, $request->purpose());
        self::assertSame(CapabilityRequirement::Required, $request->requirement());
        self::assertTrue($identity->equals(new CapabilityIdentity('filesystem.read', 65535)));
        self::assertFalse($identity->equals(new CapabilityIdentity('filesystem.read', 1)));
        self::assertTrue($purpose->equals(new CapabilityPurpose('read_public_assets')));
        self::assertSame([['Required', 'required'], ['Optional', 'optional']], array_map(static fn(CapabilityRequirement $case): array => [$case->name, $case->value], CapabilityRequirement::cases()));
    }

    public function testIdentityPurposeAndVersionBoundsRejectWithoutDisclosure(): void
    {
        foreach (['', 'Upper', '.leading', 'trailing-', 'a..b', 'é', str_repeat('a', 129)] as $value) {
            try {
                new CapabilityIdentity($value, 1);
                self::fail('Malformed capability identity was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertSame('KGN-EXTENSION-1201', $exception->diagnosticId());
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, $exception->getMessage());
                }
            }
        }

        foreach ([0, 65536] as $version) {
            try {
                new CapabilityIdentity('a', $version);
                self::fail('Invalid capability version was accepted.');
            } catch (InvalidExtensionContractException $exception) {
                self::assertSame('KGN-EXTENSION-1202', $exception->diagnosticId());
            }
        }

        self::assertSame(str_repeat('a', 128), (new CapabilityIdentity(str_repeat('a', 128), 1))->value());
        self::assertSame(str_repeat('b', 128), (new CapabilityPurpose(str_repeat('b', 128)))->value());
    }
}
