<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Grant;

use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Grant\CapabilityDisposition;
use Kongono\ExtensionComposition\Grant\CapabilityProviderLifetime;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

interface DecisionProvider {}

final class CapabilityDecisionTest extends TestCase
{
    public function testGrantAndDenialHaveExactNonNullableState(): void
    {
        $identity = new ExtensionIdentity('example/search');
        $version = new ExtensionVersion('v1');
        $capability = new CapabilityIdentity('catalog.read', 1);
        $purpose = new CapabilityPurpose('render_catalog');
        $grant = CapabilityDecision::grant($identity, $version, $capability, $purpose, CapabilityRequirement::Required, 'catalog', DecisionProvider::class, CapabilityProviderLifetime::Application);
        $denial = CapabilityDecision::deny($identity, $version, new CapabilityIdentity('metrics.emit', 1), $purpose, CapabilityRequirement::Optional, 'metrics', DecisionProvider::class);

        self::assertSame(CapabilityDisposition::Granted, $grant->disposition());
        self::assertSame(CapabilityProviderLifetime::Application, $grant->providerLifetime());
        self::assertSame('catalog', $grant->constructorParameter());
        self::assertSame(DecisionProvider::class, $grant->providerContract());
        self::assertSame(CapabilityDisposition::Denied, $denial->disposition());
        self::assertNull($denial->providerLifetime());
        self::assertSame([['Granted', 'granted'], ['Denied', 'denied']], array_map(static fn(CapabilityDisposition $case): array => [$case->name, $case->value], CapabilityDisposition::cases()));
        self::assertSame([['Application', 'application'], ['Operation', 'operation']], array_map(static fn(CapabilityProviderLifetime $case): array => [$case->name, $case->value], CapabilityProviderLifetime::cases()));
    }
}
