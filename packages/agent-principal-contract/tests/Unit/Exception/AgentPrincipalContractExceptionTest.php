<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Exception;

use Kongono\AgentPrincipalContract\Exception\ExceptionInterface;
use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentPrincipalContractExceptionTest extends TestCase
{
    public function testFactoriesMapExactlyRetainPreviousAndFixCodeToZero(): void
    {
        $previous = new RuntimeException('raw previous secret');
        $exceptions = [
            [InvalidAgentPrincipalContractException::invalidPrincipal('principal_kind', $previous), 'KGN-AGENT-CONTRACT-1301'],
            [InvalidAgentPrincipalContractException::invalidIdentity('intent_identity_grammar', $previous), 'KGN-AGENT-CONTRACT-1302'],
            [InvalidAgentPrincipalContractException::invalidPurpose('intent_purpose_grammar', $previous), 'KGN-AGENT-CONTRACT-1303'],
            [InvalidAgentPrincipalContractException::invalidEvidence('evidence_digest_grammar', $previous), 'KGN-AGENT-CONTRACT-1304'],
            [InvalidAgentPrincipalContractException::invalidProposal('constraints_not_list', 1, 0, $previous), 'KGN-AGENT-CONTRACT-1305'],
            [InvalidAgentPrincipalContractException::contractLimitExceeded('constraint_count', 10, $previous), 'KGN-AGENT-CONTRACT-1306'],
            [InvalidAgentPrincipalContractException::invalidReceipt('receipt_relation', $previous), 'KGN-AGENT-CONTRACT-1307'],
        ];

        foreach ($exceptions as [$exception, $diagnosticId]) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($diagnosticId, $exception->diagnosticId());
            self::assertSame(0, $exception->getCode());
            self::assertSame($previous, $exception->getPrevious());
            self::assertStringNotContainsString('raw previous secret', $exception->getMessage());
            self::assertStringNotContainsString('raw previous secret', (string) $exception);
            self::assertNotSame('', $exception->diagnosticTitle());
            self::assertNotSame('', $exception->diagnosticRemediation());
        }
    }

    public function testUnknownViolationFallsBackAndCountsAreNonNegative(): void
    {
        $raw = 'never-copy-this-unknown-violation';
        $exception = InvalidAgentPrincipalContractException::invalidProposal($raw, -7, -9);
        $fields = [];
        foreach ($exception->diagnosticContext()->fields() as $field) {
            $fields[$field->key()] = $field->value();
        }
        self::assertSame('unsupported_violation', $fields['violation']);
        self::assertSame(0, $fields['entry_count']);
        self::assertSame(0, $fields['entry_position']);
        self::assertStringNotContainsString($raw, $exception->getMessage());
        self::assertStringNotContainsString($raw, (string) $exception);
    }
}
