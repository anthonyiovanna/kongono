<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Cost;

use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Cost\CostEvidenceKind;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use PHPUnit\Framework\TestCase;

final class CostEvidenceTest extends TestCase
{
    public function testVocabularyCurrencyAndDecimalRepresentationsAreExact(): void
    {
        self::assertSame(['provider_reported', 'estimated'], array_column(CostEvidenceKind::cases(), 'value'));
        foreach (['0', '1', '999999999999999999', '0.000000001', '999999999999999999.999999999'] as $amount) {
            $evidence = new CostEvidence(CostEvidenceKind::ProviderReported, new CurrencyCode('USD'), new DecimalAmount($amount));
            self::assertSame($amount, $evidence->amount()->value());
            self::assertTrue($evidence->equals(new CostEvidence(CostEvidenceKind::ProviderReported, new CurrencyCode('USD'), new DecimalAmount($amount))));
        }
    }

    public function testCurrencyAndDecimalRejectNormalizationFloatAndLocaleForms(): void
    {
        foreach (['usd', 'US', 'USDD'] as $currency) {
            try {
                new CurrencyCode($currency);
                self::fail('Expected invalid currency.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1407', $exception->diagnosticId());
            }
        }
        foreach (['-1', '+1', '01', '1.', '.1', '1e2', '1,000', ' 1', '1.0000000000', '1000000000000000000'] as $amount) {
            try {
                new DecimalAmount($amount);
                self::fail('Expected invalid decimal.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertStringNotContainsString($amount, $exception->getMessage());
            }
        }
    }
}
