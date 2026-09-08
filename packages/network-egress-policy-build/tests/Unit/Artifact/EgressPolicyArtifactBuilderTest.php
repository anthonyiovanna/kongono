<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Artifact;

use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use Kongono\NetworkEgressPolicyBuild\Tests\Support\EgressPolicySourceFactory;
use PHPUnit\Framework\TestCase;

final class EgressPolicyArtifactBuilderTest extends TestCase
{
    public function testBuildIsDeterministicCanonicalAndDigestBound(): void
    {
        $builder = new EgressPolicyArtifactBuilder();
        $first = $builder->build(EgressPolicySourceFactory::create());
        $second = $builder->build(EgressPolicySourceFactory::create());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->artifactSha256());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $first->sourceSha256());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
        self::assertStringStartsWith('{"schema":"kongono.network-egress-policy-artifact","artifact_version":1,"contract_version":1,"builder":{"package":"kongono/network-egress-policy-build","version":1},"policy":{"policy_id":', $first->canonicalBytes());
        $data = json_decode($first->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsArray($data['policy']);
        self::assertSame(['schema', 'artifact_version', 'contract_version', 'builder', 'policy', 'source_sha256'], array_keys($data));
        self::assertSame(['policy_id', 'allowed_origins', 'address_scope', 'transport_security', 'redirect_mode', 'proxy_mode', 'cookie_mode', 'credential_mode', 'budget', 'required_capabilities'], array_keys($data['policy']));
    }

    public function testEverySemanticSourceFieldChangesCurrentEvidence(): void
    {
        $builder = new EgressPolicyArtifactBuilder();
        $base = $builder->build(EgressPolicySourceFactory::create())->canonicalBytes();
        $changes = [
            EgressPolicySourceFactory::create(identity: 'application.changed'),
            EgressPolicySourceFactory::create(origins: [new CanonicalOrigin(OriginScheme::Https, 'other.example.test', 443)]),
            EgressPolicySourceFactory::create(addressScope: AddressScope::ExplicitPrivateAllowed),
            EgressPolicySourceFactory::create(origins: [new CanonicalOrigin(OriginScheme::Http, 'api.example.test', 80)], transportSecurity: TransportSecurity::ExplicitHttpAllowed),
            EgressPolicySourceFactory::create(redirectMode: RedirectMode::SameOrigin, budget: EgressPolicySourceFactory::budget(1)),
            EgressPolicySourceFactory::create(proxyMode: ProxyMode::ExplicitOnly, capabilities: [TransportCapability::Http11, TransportCapability::Tls, TransportCapability::Proxy]),
            EgressPolicySourceFactory::create(cookieMode: CookieMode::ExplicitOnly),
            EgressPolicySourceFactory::create(credentialMode: CredentialMode::OperationScoped),
            EgressPolicySourceFactory::create(budget: EgressPolicySourceFactory::budget(responseBytes: 2_000_001)),
            EgressPolicySourceFactory::create(capabilities: [TransportCapability::Http11, TransportCapability::Http2, TransportCapability::Tls]),
        ];
        foreach ($changes as $changed) {
            self::assertNotSame($base, $builder->build($changed)->canonicalBytes());
        }
    }
}
