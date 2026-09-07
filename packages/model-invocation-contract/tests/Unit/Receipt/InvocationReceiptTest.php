<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Receipt;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Cost\CostEvidenceKind;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\TestCase;

final class InvocationReceiptTest extends TestCase
{
    public function testOutcomesAndValidRelationsRemainBoundedClaims(): void
    {
        self::assertSame(['succeeded', 'failed', 'cancelled', 'rejected', 'uncertain'], array_column(InvocationOutcome::cases(), 'value'));
        $profile = self::profile(ProviderCapability::cases());
        $receipt = new InvocationReceipt(new ReceiptIdentity('receipt'), new InvocationIdentity('invoke'), $profile, InvocationOutcome::Succeeded, 16, new ResultIdentity('result'), self::usage(), self::cost(), true);
        self::assertSame(16, $receipt->attemptCount());
        self::assertTrue($receipt->cleanupComplete());
        self::assertTrue($receipt->equals(new InvocationReceipt(new ReceiptIdentity('receipt'), new InvocationIdentity('invoke'), $profile, InvocationOutcome::Succeeded, 16, new ResultIdentity('result'), self::usage(), self::cost(), true)));
        self::assertSame(0, (new InvocationReceipt(new ReceiptIdentity('rejected'), new InvocationIdentity('invoke'), $profile, InvocationOutcome::Rejected, 0, null, null, null, false))->attemptCount());
    }

    public function testAttemptOutcomeResultAndCapabilitiesFailClosed(): void
    {
        $profile = self::profile([ProviderCapability::TextResult]);
        $cases = [[InvocationOutcome::Rejected, 1, null, null, null], [InvocationOutcome::Failed, 0, null, null, null], [InvocationOutcome::Succeeded, 1, null, null, null], [InvocationOutcome::Failed, 1, new ResultIdentity('result'), null, null], [InvocationOutcome::Failed, 17, null, null, null], [InvocationOutcome::Failed, 1, null, self::usage(), null], [InvocationOutcome::Failed, 1, null, null, self::cost()]];
        foreach ($cases as [$outcome, $attempts, $result, $usage, $cost]) {
            try {
                new InvocationReceipt(new ReceiptIdentity('receipt'), new InvocationIdentity('invoke'), $profile, $outcome, $attempts, $result, $usage, $cost, false);
                self::fail('Expected invalid receipt.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1408', $exception->diagnosticId());
            }
        }
    }

    /** @param list<ProviderCapability> $capabilities */
    private static function profile(array $capabilities): ModelProfileReference
    {
        return new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile'), new ProviderIdentity('provider'), new ModelIdentity('model'), new ProviderCapabilitySet($capabilities));
    }

    private static function usage(): UsageEvidence
    {
        return new UsageEvidence([new UsageQuantity(new UsageMetricName('input'), new UsageUnitName('token'), 1)]);
    }

    private static function cost(): CostEvidence
    {
        return new CostEvidence(CostEvidenceKind::Estimated, new CurrencyCode('USD'), new DecimalAmount('1.0'));
    }
}
