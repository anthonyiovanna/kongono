<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Provider;

use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use PHPUnit\Framework\TestCase;

final class ProviderCapabilitySetTest extends TestCase
{
    public function testCasesOrderSupportEqualityAndFreshArraysAreExact(): void
    {
        self::assertSame(['text_result', 'structured_result', 'action_suggestion', 'streaming', 'usage_evidence', 'cost_evidence'], array_column(ProviderCapability::cases(), 'value'));
        $input = ProviderCapability::cases();
        $set = new ProviderCapabilitySet($input);
        $returned = $set->capabilities();
        array_pop($returned);
        self::assertCount(6, $set->capabilities());
        self::assertTrue($set->supports(ProviderCapability::Streaming));
        self::assertTrue($set->equals(new ProviderCapabilitySet(ProviderCapability::cases())));
    }

    public function testListUniquenessAndCanonicalOrderFailClosed(): void
    {
        foreach ([[1 => ProviderCapability::TextResult], [], [ProviderCapability::TextResult, ProviderCapability::TextResult], [ProviderCapability::Streaming, ProviderCapability::TextResult]] as $capabilities) {
            try {
                (new \ReflectionClass(ProviderCapabilitySet::class))->newInstanceArgs([$capabilities]);
                self::fail('Expected invalid capabilities.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1402', $exception->diagnosticId());
            }
        }
    }
}
