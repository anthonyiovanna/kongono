<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Diagnostic;

use Kongono\AgentPrincipalContract\Diagnostic\AgentPrincipalContractDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class AgentPrincipalContractDiagnosticTest extends TestCase
{
    public function testIdsTitlesRemediationsOperationsInvariantsAndSchemasAreClosed(): void
    {
        self::assertSame([
            'KGN-AGENT-CONTRACT-1301',
            'KGN-AGENT-CONTRACT-1302',
            'KGN-AGENT-CONTRACT-1303',
            'KGN-AGENT-CONTRACT-1304',
            'KGN-AGENT-CONTRACT-1305',
            'KGN-AGENT-CONTRACT-1306',
            'KGN-AGENT-CONTRACT-1307',
        ], array_map(static fn(AgentPrincipalContractDiagnostic $case): string => $case->value, AgentPrincipalContractDiagnostic::cases()));

        foreach (AgentPrincipalContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            self::assertSame('violation', $schema->fields()[0]->key());
            foreach ($schema->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }

        self::assertCount(3, AgentPrincipalContractDiagnostic::InvalidProposal->contextSchema()->fields());
        self::assertCount(3, AgentPrincipalContractDiagnostic::ContractLimitExceeded->contextSchema()->fields());
        self::assertCount(1, AgentPrincipalContractDiagnostic::InvalidReceipt->contextSchema()->fields());
    }
}
