<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Invocation;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationLimits;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use PHPUnit\Framework\TestCase;

final class InvocationRequestTest extends TestCase
{
    public function testLimitsCapabilitiesStreamingAndFreshMessagesAreExact(): void
    {
        $limits = new InvocationLimits(1048576, 4096, 0);
        self::assertSame([1048576, 4096, 0], [$limits->maxOutputBytes(), $limits->maxStreamEvents(), $limits->maxUsageEntries()]);
        $request = new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invoke.one'), self::profile([ProviderCapability::TextResult, ProviderCapability::Streaming]), [new InputMessage(InputRole::User, ModelVisibility::Private, 'hello')], ResultMode::Text, true, $limits);
        $messages = $request->messages();
        array_pop($messages);
        self::assertCount(1, $request->messages());
        self::assertTrue($request->streaming());
        self::assertTrue($request->equals(new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invoke.one'), self::profile([ProviderCapability::TextResult, ProviderCapability::Streaming]), [new InputMessage(InputRole::User, ModelVisibility::Private, 'hello')], ResultMode::Text, true, $limits)));
        self::assertCount(64, (new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invoke.max'), $profile = self::profile([ProviderCapability::TextResult]), array_fill(0, 64, new InputMessage(InputRole::User, ModelVisibility::Private, str_repeat('x', 4096))), ResultMode::Text, false, new InvocationLimits(1, 1, 0)))->messages());
    }

    public function testCountsAggregateBytesCapabilityAndLimitRangesFailClosed(): void
    {
        $message = new InputMessage(InputRole::User, ModelVisibility::Private, 'x');
        $profile = self::profile([ProviderCapability::TextResult]);
        foreach ([[new InvocationLimits(1, 1, 16), array_fill(0, 65, $message), ResultMode::Text, false], [new InvocationLimits(1, 1, 16), array_fill(0, 9, new InputMessage(InputRole::User, ModelVisibility::Private, str_repeat('x', 32768))), ResultMode::Text, false], [new InvocationLimits(1, 1, 16), [$message], ResultMode::Structured, false], [new InvocationLimits(1, 1, 16), [$message], ResultMode::Text, true]] as [$limits, $messages, $mode, $streaming]) {
            try {
                new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invoke.one'), $profile, $messages, $mode, $streaming, $limits);
                self::fail('Expected invalid request.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-MODEL-CONTRACT-1404', 'KGN-MODEL-CONTRACT-1409']);
            }
        }
        foreach ([[0, 1, 0], [1048577, 1, 0], [1, 0, 0], [1, 4097, 0], [1, 1, -1], [1, 1, 17]] as [$output, $events, $usage]) {
            try {
                new InvocationLimits($output, $events, $usage);
                self::fail('Expected invalid invocation limits.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1404', $exception->diagnosticId());
            }
        }
    }

    /** @param list<ProviderCapability> $capabilities */
    private static function profile(array $capabilities): ModelProfileReference
    {
        return new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile.one'), new ProviderIdentity('provider.one'), new ModelIdentity('model.one'), new ProviderCapabilitySet($capabilities));
    }
}
