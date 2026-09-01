<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Security;

use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactPublisher;
use Kongono\AgentDeclarationBuild\Exception\AgentDeclarationBuildException;
use Kongono\AgentDeclarationBuild\Tests\Support\AgentDeclarationFactory;
use PHPUnit\Framework\TestCase;

final class AgentDeclarationDisclosureTest extends TestCase
{
    public function testFailuresAndBasenameDoNotDiscloseDistinctSentinels(): void
    {
        $principal = 'PrincipalSecretCaseSensitive';
        $artifact = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create('declaration.safe', $principal));
        self::assertStringContainsString($principal, $artifact->canonicalBytes());
        self::assertStringNotContainsString($principal, $artifact->artifactSha256());

        try {
            (new AgentDeclarationArtifactPublisher())->publish($artifact, 'PathSecretCaseSensitive');
            self::fail('Expected path rejection.');
        } catch (AgentDeclarationBuildException $failure) {
            $context = array_map(
                static fn($field): array => [$field->key(), $field->value()],
                $failure->diagnosticContext()->fields(),
            );
            foreach ([$failure->getMessage(), (string) $failure, json_encode($context, JSON_THROW_ON_ERROR)] as $visible) {
                self::assertStringNotContainsString($principal, $visible);
                self::assertStringNotContainsString('PathSecretCaseSensitive', $visible);
                self::assertStringNotContainsString(str_repeat('3', 64), $visible);
            }
        }

        $basename = 'kongono-agent-declaration-v1-' . $artifact->artifactSha256() . '.json';
        self::assertStringNotContainsString($principal, $basename);
        self::assertStringNotContainsString('application.primary', $basename);
    }
}
