<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Generated;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsGenerator;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use PHPUnit\Framework\TestCase;

final class GeneratedExternalServiceBindingsGeneratorTest extends TestCase
{
    public function testGenerationIsDeterministicFixedSecretFreeAndInert(): void
    {
        $builder = new ExternalServiceBindingArtifactBuilder();
        $generator = new GeneratedExternalServiceBindingsGenerator();
        $first = $generator->generate($builder->build(ExternalServiceBindingSourceFactory::create()));
        $second = $generator->generate($builder->build(ExternalServiceBindingSourceFactory::create()));
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertStringStartsWith("<?php\n\ndeclare(strict_types=1);\n\n// {\"schema\":\"kongono.external-service-bindings.generated-php\"", $first->canonicalBytes());
        self::assertStringContainsString('new \\Kongono\\ExternalServiceContract\\Binding\\ServiceBinding(', $first->canonicalBytes());
        self::assertStringContainsString('TransportCapability::Http11', $first->canonicalBytes());
        self::assertStringNotContainsString('include ', $first->canonicalBytes());
        self::assertStringNotContainsString('require ', $first->canonicalBytes());
        self::assertStringNotContainsString('eval(', $first->canonicalBytes());
    }

    public function testEverySecretFreeSemanticChangeChangesGeneratedEvidenceAndEscapingIsFixed(): void
    {
        $builder = new ExternalServiceBindingArtifactBuilder();
        $generator = new GeneratedExternalServiceBindingsGenerator();
        $base = $generator->generate($builder->build(ExternalServiceBindingSourceFactory::create()))->canonicalBytes();
        $changes = [
            ExternalServiceBindingSourceFactory::create('other.bindings'),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(identity: 'beta.binding')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(profile: 'beta.profile')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(host: 'other.example.test')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(port: 8443)]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(basePath: "/v1/it's")]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(credentialReference: 'fixtures/beta')]),
            ExternalServiceBindingSourceFactory::create(bindings: [ExternalServiceBindingSourceFactory::binding(capabilities: [TransportCapability::Http11, TransportCapability::Http2, TransportCapability::Tls])]),
        ];
        foreach ($changes as $source) {
            self::assertNotSame($base, $generator->generate($builder->build($source))->canonicalBytes());
        }
        $escaped = $generator->generate($builder->build($changes[5]))->canonicalBytes();
        self::assertStringContainsString("it\\'s", $escaped);
    }
}
