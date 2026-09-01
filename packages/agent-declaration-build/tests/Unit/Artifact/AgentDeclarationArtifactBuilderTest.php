<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Unit\Artifact;

use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Tests\Support\AgentDeclarationFactory;
use PHPUnit\Framework\TestCase;

final class AgentDeclarationArtifactBuilderTest extends TestCase
{
    public function testBuildIsByteIdenticalAndUsesExactRootOrder(): void
    {
        $first = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        $second = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertFalse(str_ends_with($first->canonicalBytes(), "\n\n"));

        $decoded = json_decode($first->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $declaration = $decoded['declaration'];
        self::assertIsArray($declaration);
        $principal = $declaration['principal'];
        $capabilityRequests = $declaration['capability_requests'];
        $constraints = $declaration['constraints'];
        $evidenceReferences = $declaration['evidence_references'];
        $provenance = $declaration['provenance'];
        self::assertIsArray($principal);
        self::assertIsArray($capabilityRequests);
        self::assertIsArray($capabilityRequests[0]);
        self::assertIsArray($constraints);
        self::assertIsArray($constraints[0]);
        self::assertIsArray($evidenceReferences);
        self::assertIsArray($evidenceReferences[0]);
        self::assertIsArray($provenance);
        self::assertSame(['schema', 'artifact_version', 'contract_version', 'builder', 'declaration', 'declaration_sha256'], array_keys($decoded));
        self::assertSame(['declaration_id', 'principal', 'capability_requests', 'constraints', 'evidence_references', 'provenance', 'visibility'], array_keys($declaration));
        self::assertSame(['kind', 'id'], array_keys($principal));
        self::assertSame(['identity', 'purpose', 'requirement'], array_keys($capabilityRequests[0]));
        self::assertSame(['kind', 'value'], array_keys($constraints[0]));
        self::assertSame(['kind', 'identity', 'sha256'], array_keys($evidenceReferences[0]));
        self::assertSame(['source', 'source_identity', 'sha256'], array_keys($provenance));
    }

    public function testBothDigestsAreIndependentlyReproduced(): void
    {
        $artifact = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        $decoded = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $declaration = $decoded['declaration'];
        self::assertIsArray($declaration);
        $declarationBytes = json_encode($declaration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        self::assertSame(hash('sha256', $declarationBytes), $artifact->declarationSha256());
        self::assertSame(hash('sha256', $artifact->canonicalBytes()), $artifact->artifactSha256());
        self::assertArrayNotHasKey('artifact_sha256', $decoded);
        self::assertSame('assistant.primary', $artifact->declarationIdentity()->value());
    }

    public function testBuildIgnoresAmbientFilesystemAndEnvironment(): void
    {
        $before = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create())->canonicalBytes();
        $old = getenv('KONGONO_AGENT_DECLARATION_TEST');
        putenv('KONGONO_AGENT_DECLARATION_TEST=changed');
        try {
            self::assertSame($before, (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create())->canonicalBytes());
        } finally {
            false === $old ? putenv('KONGONO_AGENT_DECLARATION_TEST') : putenv('KONGONO_AGENT_DECLARATION_TEST=' . $old);
        }
    }
}
