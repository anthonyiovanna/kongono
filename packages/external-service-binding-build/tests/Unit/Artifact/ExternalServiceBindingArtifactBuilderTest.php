<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Artifact;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingArtifactBuilderTest extends TestCase
{
    public function testCanonicalSchemaFramingDigestsAndDeterminismAreExact(): void
    {
        $builder = new ExternalServiceBindingArtifactBuilder();
        $first = $builder->build(ExternalServiceBindingSourceFactory::create());
        $second = $builder->build(ExternalServiceBindingSourceFactory::create());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
        $data = json_decode($first->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame(['schema', 'artifact_version', 'contract_version', 'builder', 'source', 'source_sha256'], array_keys($data));
        self::assertArrayHasKey('source', $data);
        self::assertIsArray($data['source']);
        self::assertSame(['source_identity', 'bindings'], array_keys($data['source']));
        self::assertArrayHasKey('bindings', $data['source']);
        self::assertIsArray($data['source']['bindings']);
        self::assertArrayHasKey(0, $data['source']['bindings']);
        self::assertIsArray($data['source']['bindings'][0]);
        self::assertSame(['binding_identity', 'service_profile_identity', 'endpoint', 'credential_reference', 'required_capabilities'], array_keys($data['source']['bindings'][0]));
        self::assertSame(hash('sha256', json_encode(['contract_version' => 1, 'source' => $data['source']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n"), $first->sourceSha256());
    }

    public function testEverySemanticBindingChangeChangesArtifactEvidence(): void
    {
        $builder = new ExternalServiceBindingArtifactBuilder();
        $base = $builder->build(ExternalServiceBindingSourceFactory::create());
        $changes = [
            ExternalServiceBindingSourceFactory::create('changed.bindings'),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(identity: 'beta.binding')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(profile: 'beta.profile')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(host: 'other.example.test')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(port: 444)]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(basePath: '/v2')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(credentialSource: 'other.credentials')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(credentialReference: 'fixtures/beta')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(capabilities: [TransportCapability::Http11, TransportCapability::Http2, TransportCapability::Tls])]),
        ];
        foreach ($changes as $change) {
            $changed = $builder->build($change);
            self::assertNotSame($base->canonicalBytes(), $changed->canonicalBytes());
            self::assertNotSame($base->artifactSha256(), $changed->artifactSha256());
            self::assertNotSame($base->sourceSha256(), $changed->sourceSha256());
        }
    }
}
