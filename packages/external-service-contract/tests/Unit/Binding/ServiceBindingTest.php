<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Binding;

use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use PHPUnit\Framework\TestCase;

final class ServiceBindingTest extends TestCase
{
    public function testBindingRetainsOnlyTypedSecretFreeEvidence(): void
    {
        $binding = self::binding();
        self::assertSame('binding.one', $binding->identity()->value());
        self::assertSame('profile.one', $binding->profile()->value());
        self::assertSame('/v1', $binding->endpoint()->basePath()->value());
        self::assertSame('credential.reference', $binding->credentialReference()->reference());
        self::assertTrue($binding->requiredCapabilities()->contains(TransportCapability::Http11));
        self::assertTrue($binding->equals(self::binding()));
    }

    private static function binding(): ServiceBinding
    {
        return new ServiceBinding(
            new ServiceBindingIdentity('binding.one'),
            new ServiceProfileIdentity('profile.one'),
            new ServiceEndpoint(new CanonicalOrigin(OriginScheme::Https, 'service.test', 443), new EndpointBasePath('/v1')),
            new CredentialReference(new CredentialSourceIdentity('source.one'), 'credential.reference'),
            new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]),
        );
    }
}
