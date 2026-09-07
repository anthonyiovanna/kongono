<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Result;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Cost\CostEvidenceKind;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Result\ActionArguments;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use PHPUnit\Framework\TestCase;

final class ModelResultTest extends TestCase
{
    public function testFinishReasonsAndEveryValidShapeRemainEvidenceOnly(): void
    {
        self::assertSame(['completed', 'length', 'content_filtered', 'action_suggested', 'cancelled', 'unknown'], array_column(FinishReason::cases(), 'value'));
        $profile = self::profile(ProviderCapability::cases());
        $text = self::modelResult($profile, ResultMode::Text, FinishReason::Completed, new ResultText('answer'), null, self::usage(), self::cost());
        self::assertSame('answer', $text->text()?->value());
        $action = self::modelResult($profile, ResultMode::ActionSuggestion, FinishReason::ActionSuggested, null, new ActionSuggestion(new ActionName('lookup'), new ActionArguments('{}')), null, null);
        self::assertSame('lookup', $action->actionSuggestion()?->name()->value());
        self::assertSame(262144, strlen((new ResultText(str_repeat('x', 262144)))->value()));
        $this->expectException(InvalidModelInvocationContractException::class);
        new ResultText(str_repeat('x', 262145));
    }

    public function testShapeFinishReasonAndEvidenceCapabilitiesFailClosed(): void
    {
        $profile = self::profile([ProviderCapability::TextResult]);
        foreach ([[ResultMode::Text, FinishReason::Completed, null, null, null, null], [ResultMode::ActionSuggestion, FinishReason::Completed, null, new ActionSuggestion(new ActionName('lookup'), new ActionArguments('{}')), null, null], [ResultMode::Text, FinishReason::ActionSuggested, new ResultText('answer'), null, null, null], [ResultMode::Text, FinishReason::Completed, new ResultText('answer'), null, self::usage(), null], [ResultMode::Text, FinishReason::Completed, new ResultText('answer'), null, null, self::cost()]] as [$mode, $finish, $text, $action, $usage, $cost]) {
            try {
                self::modelResult($profile, $mode, $finish, $text, $action, $usage, $cost);
                self::fail('Expected invalid result.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1405', $exception->diagnosticId());
            }
        }
    }

    /** @param list<ProviderCapability> $capabilities */
    private static function profile(array $capabilities): ModelProfileReference
    {
        return new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile'), new ProviderIdentity('provider'), new ModelIdentity('model'), new ProviderCapabilitySet($capabilities));
    }

    private static function modelResult(ModelProfileReference $profile, ResultMode $mode, FinishReason $finish, ?ResultText $text, ?ActionSuggestion $action, ?UsageEvidence $usage, ?CostEvidence $cost): ModelResult
    {
        return new ModelResult(ContractVersion::Version1, new ResultIdentity('result.one'), new InvocationIdentity('invoke.one'), $profile, $mode, $finish, $text, $action, $usage, $cost, ModelVisibility::Private);
    }

    private static function usage(): UsageEvidence
    {
        return new UsageEvidence([new UsageQuantity(new UsageMetricName('input'), new UsageUnitName('token'), 1)]);
    }

    private static function cost(): CostEvidence
    {
        return new CostEvidence(CostEvidenceKind::Estimated, new CurrencyCode('USD'), new DecimalAmount('0.1'));
    }
}
