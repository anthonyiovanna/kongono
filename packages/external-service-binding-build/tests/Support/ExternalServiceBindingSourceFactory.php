<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Support;

use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
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

final class ExternalServiceBindingSourceFactory
{
    /** @param list<ServiceBinding>|null $bindings */
    public static function create(string $identity = 'application.bindings', ?array $bindings = null): ExternalServiceBindingSource
    {
        return new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity($identity), $bindings ?? [self::binding()]);
    }

    /** @param list<TransportCapability>|null $capabilities */
    public static function binding(
        string $identity = 'alpha.binding',
        string $profile = 'alpha.profile',
        string $host = 'api.example.test',
        int $port = 443,
        string $basePath = '/v1',
        string $credentialSource = 'fixture.credentials',
        string $credentialReference = 'fixtures/alpha',
        ?array $capabilities = null,
        OriginScheme $scheme = OriginScheme::Https,
    ): ServiceBinding {
        return new ServiceBinding(
            new ServiceBindingIdentity($identity),
            new ServiceProfileIdentity($profile),
            new ServiceEndpoint(new CanonicalOrigin($scheme, $host, $port), new EndpointBasePath($basePath)),
            new CredentialReference(new CredentialSourceIdentity($credentialSource), $credentialReference),
            new TransportCapabilitySet($capabilities ?? [TransportCapability::Http11, TransportCapability::Tls]),
        );
    }

    private function __construct() {}
}
