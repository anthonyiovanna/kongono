<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Profile;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ModelProfileReferenceTest extends TestCase
{
    public function testProfileCarriesOnlyExactNeutralComponents(): void
    {
        $profile = new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('primary'), new ProviderIdentity('example'), new ModelIdentity('model.v1'), new ProviderCapabilitySet([ProviderCapability::TextResult]));
        self::assertSame('primary', $profile->identity()->value());
        self::assertSame('example', $profile->providerIdentity()->value());
        self::assertSame('model.v1', $profile->modelIdentity()->value());
        self::assertTrue($profile->equals(new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('primary'), new ProviderIdentity('example'), new ModelIdentity('model.v1'), new ProviderCapabilitySet([ProviderCapability::TextResult]))));
        self::assertSame(['version', 'identity', 'providerIdentity', 'modelIdentity', 'capabilities'], array_map(static fn(\ReflectionProperty $property): string => $property->name, (new ReflectionClass($profile))->getProperties()));
    }
}
