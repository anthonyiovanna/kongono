<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Profile;

use Kongono\ExternalServiceApplication\Exception\InvalidExternalServiceApplicationProfileException;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileIdentity;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use PHPUnit\Framework\TestCase;

final class ExternalServiceApplicationProfileFactoryTest extends TestCase
{
    public function testExactCurrentBindingAndPolicyProduceDeterministicProfile(): void
    {
        [$source, $bytes, $identity, $networkProfile] = $this->fixture();
        $factory = new ExternalServiceApplicationProfileFactory();
        $first = $factory->create($source, $bytes, $identity, $networkProfile);
        $second = $factory->create($source, $bytes, $identity, $networkProfile);

        self::assertTrue($first->identity()->equals($second->identity()));
        self::assertSame($source, $first->bindingSource());
        self::assertTrue($identity->equals($first->binding()->identity()));
        self::assertSame($networkProfile, $first->networkEgressProfile());
        self::assertSame($bytes, $first->bindingArtifact()->canonicalBytes());
    }

    public function testStaleBindingArtifactAndForgedNetworkProfileFailClosed(): void
    {
        [$source, $bytes, $identity, $networkProfile] = $this->fixture();
        [$otherSource] = $this->fixture('other-source');
        try {
            (new ExternalServiceApplicationProfileFactory())->create($otherSource, $bytes, $identity, $networkProfile);
            self::fail('Stale binding artifact must fail.');
        } catch (InvalidExternalServiceApplicationProfileException $failure) {
            self::assertSame('binding_artifact_mismatch', $failure->diagnosticContext()->fields()[0]->value());
        }

        $forged = new NetworkEgressApplicationProfile(
            $networkProfile->source(),
            $networkProfile->artifact(),
            new NetworkEgressApplicationProfileIdentity(str_repeat('0', 64)),
        );
        $this->expectException(InvalidExternalServiceApplicationProfileException::class);
        (new ExternalServiceApplicationProfileFactory())->create($source, $bytes, $identity, $forged);
    }

    public function testMissingBindingOriginCredentialModeAndCapabilityFailClosed(): void
    {
        [$source, $bytes, $identity, $profile] = $this->fixture();
        $factory = new ExternalServiceApplicationProfileFactory();

        try {
            $factory->create($source, $bytes, new ServiceBindingIdentity('binding.missing'), $profile);
            self::fail('Missing binding must fail.');
        } catch (InvalidExternalServiceApplicationProfileException $failure) {
            self::assertSame('binding_selection', $failure->diagnosticContext()->fields()[0]->value());
        }

        [$otherOriginSource, $otherBytes, $otherIdentity] = $this->fixture(bindingHost: 'other.example.test');
        try {
            $factory->create($otherOriginSource, $otherBytes, $otherIdentity, $profile);
            self::fail('Origin mismatch must fail.');
        } catch (InvalidExternalServiceApplicationProfileException $failure) {
            self::assertSame('binding_origin', $failure->diagnosticContext()->fields()[0]->value());
        }

        [, , , $noneProfile] = $this->fixture(credentialMode: CredentialMode::None);
        try {
            $factory->create($source, $bytes, $identity, $noneProfile);
            self::fail('Credential mode mismatch must fail.');
        } catch (InvalidExternalServiceApplicationProfileException $failure) {
            self::assertSame('credential_mode', $failure->diagnosticContext()->fields()[0]->value());
        }

        [$capabilitySource, $capabilityBytes, $capabilityIdentity] = $this->fixture(bindingCapabilities: new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls, TransportCapability::Cancellation]));
        try {
            $factory->create($capabilitySource, $capabilityBytes, $capabilityIdentity, $profile);
            self::fail('Capability mismatch must fail.');
        } catch (InvalidExternalServiceApplicationProfileException $failure) {
            self::assertSame('required_capability', $failure->diagnosticContext()->fields()[0]->value());
        }
    }

    /** @return array{ExternalServiceBindingSource, string, ServiceBindingIdentity, NetworkEgressApplicationProfile} */
    private function fixture(
        string $sourceIdentity = 'bindings.current',
        string $bindingHost = 'api.example.test',
        CredentialMode $credentialMode = CredentialMode::OperationScoped,
        ?TransportCapabilitySet $bindingCapabilities = null,
    ): array {
        $origin = new CanonicalOrigin(OriginScheme::Https, $bindingHost, 443);
        $bindingIdentity = new ServiceBindingIdentity('binding.primary');
        $binding = new ServiceBinding(
            $bindingIdentity,
            new ServiceProfileIdentity('profile.primary'),
            new ServiceEndpoint($origin, new EndpointBasePath('/api')),
            new CredentialReference(new CredentialSourceIdentity('credentials.primary'), 'reference.primary'),
            $bindingCapabilities ?? new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]),
        );
        $source = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity($sourceIdentity), [$binding]);
        $artifactBytes = (new ExternalServiceBindingArtifactBuilder())->build($source)->canonicalBytes();
        $policyOrigin = new CanonicalOrigin(OriginScheme::Https, 'api.example.test', 443);
        $policy = new EgressPolicySource(
            new EgressPolicyIdentity('policy.external-service'),
            [$policyOrigin],
            AddressScope::PublicOnly,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            $credentialMode,
            new EgressBudget(1, 0, 8, 4096, 4096, 32, 4096, 4096, 4096, 1000, 1000, 5000),
            new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]),
        );
        $networkProfile = (new NetworkEgressApplicationProfileFactory())->create(
            $policy,
            (new EgressPolicyArtifactBuilder())->build($policy)->canonicalBytes(),
        );

        return [$source, $artifactBytes, $bindingIdentity, $networkProfile];
    }
}
