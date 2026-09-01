<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Provenance;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use PHPUnit\Framework\TestCase;

final class AgentProvenanceTest extends TestCase
{
    public function testVocabularyAndAccessorsAreExact(): void
    {
        self::assertSame(['caller', 'application', 'model', 'retrieval', 'capability', 'deterministic'], array_map(static fn(ProvenanceSource $case): string => $case->value, ProvenanceSource::cases()));
        $provenance = new AgentProvenance(ProvenanceSource::Model, 'model.output-1', str_repeat('b', 64));
        self::assertSame(ProvenanceSource::Model, $provenance->source());
        self::assertSame('model.output-1', $provenance->sourceIdentity());
        self::assertSame(str_repeat('b', 64), $provenance->sha256());
    }

    public function testInvalidSourceIdentityAndDigestAreNondisclosing(): void
    {
        foreach ([['Secret Identity', str_repeat('a', 64)], ['valid.identity', str_repeat('B', 64)], ['valid.identity', str_repeat('a', 63)]] as [$identity, $digest]) {
            try {
                new AgentProvenance(ProvenanceSource::Caller, $identity, $digest);
                self::fail('Invalid provenance was accepted.');
            } catch (InvalidAgentPrincipalContractException $exception) {
                self::assertSame('KGN-AGENT-CONTRACT-1304', $exception->diagnosticId());
                self::assertStringNotContainsString($identity, $exception->getMessage());
                self::assertStringNotContainsString($digest, (string) $exception);
            }
        }
    }
}
