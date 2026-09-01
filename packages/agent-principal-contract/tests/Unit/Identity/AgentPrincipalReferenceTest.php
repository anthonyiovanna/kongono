<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Identity;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;

final class AgentPrincipalReferenceTest extends TestCase
{
    public function testOnlyAgentPrincipalIsAcceptedAndEqualityIsExact(): void
    {
        $principal = new PrincipalReference(PrincipalKind::Agent, new PrincipalId('agent-secret-id'));
        $reference = new AgentPrincipalReference($principal);
        self::assertSame($principal, $reference->reference());
        self::assertTrue($reference->equals(new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId('agent-secret-id')))));
        self::assertFalse($reference->equals(new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId('other-agent')))));

        foreach ([PrincipalKind::Human, PrincipalKind::Service, PrincipalKind::Organization, PrincipalKind::System] as $kind) {
            try {
                new AgentPrincipalReference(new PrincipalReference($kind, new PrincipalId('never-disclose-this-id')));
                self::fail('A non-Agent Principal kind was accepted.');
            } catch (InvalidAgentPrincipalContractException $exception) {
                self::assertSame('KGN-AGENT-CONTRACT-1301', $exception->diagnosticId());
                self::assertStringNotContainsString('never-disclose-this-id', $exception->getMessage());
                self::assertStringNotContainsString('never-disclose-this-id', (string) $exception);
                self::assertStringNotContainsString('never-disclose-this-id', self::contextText($exception));
            }
        }
    }

    public function testOperationGrammarBoundsAndEqualityAreExact(): void
    {
        foreach (['a', 'agent.operation-1', 'a_b.c-d', 'a' . str_repeat('1', 127)] as $valid) {
            self::assertSame($valid, (new OperationReference($valid))->value());
        }
        self::assertTrue((new OperationReference('operation.one'))->equals(new OperationReference('operation.one')));
        self::assertFalse((new OperationReference('operation.one'))->equals(new OperationReference('operation.two')));

        foreach (['', 'A', '1agent', 'agent::run', 'agent..run', 'agent-', 'a' . str_repeat('1', 128), "agent\nrun"] as $invalid) {
            try {
                new OperationReference($invalid);
                self::fail('An invalid operation reference was accepted.');
            } catch (InvalidAgentPrincipalContractException $exception) {
                self::assertSame('KGN-AGENT-CONTRACT-1302', $exception->diagnosticId());
                if (strlen($invalid) > 1) {
                    self::assertStringNotContainsString($invalid, $exception->getMessage());
                    self::assertStringNotContainsString($invalid, (string) $exception);
                    self::assertStringNotContainsString($invalid, self::contextText($exception));
                }
            }
        }
    }

    private static function contextText(InvalidAgentPrincipalContractException $exception): string
    {
        return implode('|', array_map(static fn($field): string => $field->key() . '=' . $field->value(), $exception->diagnosticContext()->fields()));
    }
}
