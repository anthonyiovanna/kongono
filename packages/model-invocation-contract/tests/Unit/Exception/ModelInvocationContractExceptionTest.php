<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Exception;

use Kongono\ModelInvocationContract\Exception\ExceptionInterface;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelInvocationContractExceptionTest extends TestCase
{
    public function testFactoriesAreExactPreservePreviousIdentityAndNeverDiscloseIt(): void
    {
        $previous = new RuntimeException('previous raw secret');
        $exceptions = [
            [InvalidModelInvocationContractException::invalidIdentity('provider_identity_grammar', $previous), 'KGN-MODEL-CONTRACT-1401'],
            [InvalidModelInvocationContractException::invalidProfile('capability_count', 0, null, $previous), 'KGN-MODEL-CONTRACT-1402'],
            [InvalidModelInvocationContractException::invalidInput('input_content_grammar', $previous), 'KGN-MODEL-CONTRACT-1403'],
            [InvalidModelInvocationContractException::invalidRequest('message_count', 65, null, 'text', $previous), 'KGN-MODEL-CONTRACT-1404'],
            [InvalidModelInvocationContractException::invalidResult('result_shape', 'text', $previous), 'KGN-MODEL-CONTRACT-1405'],
            [InvalidModelInvocationContractException::invalidStreamEvent('stream_event_shape', 'started', $previous), 'KGN-MODEL-CONTRACT-1406'],
            [InvalidModelInvocationContractException::invalidUsageOrCostEvidence('usage_count', 17, null, $previous), 'KGN-MODEL-CONTRACT-1407'],
            [InvalidModelInvocationContractException::invalidReceipt('result_outcome_relation', $previous), 'KGN-MODEL-CONTRACT-1408'],
            [InvalidModelInvocationContractException::contractLimitExceeded('usage_count', 16, 17, $previous), 'KGN-MODEL-CONTRACT-1409'],
        ];
        foreach ($exceptions as [$exception, $id]) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($id, $exception->diagnosticId());
            self::assertSame($previous, $exception->getPrevious());
            self::assertSame(0, $exception->getCode());
            self::assertStringNotContainsString('previous raw secret', $exception->getMessage());
            self::assertStringNotContainsString('previous raw secret', (string) $exception);
        }
    }

    public function testUnknownLabelsAndRawValuesMapToClosedSafeTokens(): void
    {
        $raw = 'raw-secret-unknown-value';
        $exception = InvalidModelInvocationContractException::invalidRequest($raw, -1, -2, $raw);
        $values = [];
        foreach ($exception->diagnosticContext()->fields() as $field) {
            $values[$field->key()] = $field->value();
        }
        self::assertSame('unsupported_violation', $values['violation']);
        self::assertSame('unsupported_mode', $values['mode']);
        self::assertSame(0, $values['entry_count']);
        self::assertSame(0, $values['entry_position']);
        self::assertStringNotContainsString($raw, (string) $exception);
    }
}
