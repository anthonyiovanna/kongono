<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Adapter;

use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesStreamingInvocationSession;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesInvocationAdapterTest extends TestCase
{
    public function testIdentityOpenAndOneOpenLifecycleAreExactAndLazy(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $adapter = new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context);
        self::assertSame('openai.responses.v1', $adapter->identity()->value());
        $session = $adapter->open($fixture->source);
        self::assertSame(0, $fixture->createCalls);
        $session->close();
        self::assertSame(0, $fixture->createCalls);
        self::assertNull($fixture->application);

        $this->expectException(OpenAiResponsesInvocationException::class);
        $adapter->open($fixture->source);
    }

    public function testPlanAdapterLifetimeAndToolMismatchesRejectBeforeApplicationCreation(): void
    {
        foreach (['plan', 'adapter', 'lifetime', 'tool'] as $case) {
            $messages = 'tool' === $case
                ? [new InputMessage(InputRole::Tool, ModelVisibility::Private, 'private-tool-input')]
                : null;
            $fixture = new OpenAiResponsesAdapterFixture(messages: $messages);
            $source = new InvocationPlanSource(
                new InvocationPlanIdentity('plan' === $case ? 'plan.other' : $fixture->source->identity()->value()),
                new AdapterIdentity('adapter' === $case ? 'adapter.other' : OpenAiResponsesInvocationAdapter::IDENTITY),
                $fixture->source->request(),
                'lifetime' === $case
                    ? new InvocationExecutionBudget(4999, 1, 65536, $fixture->source->budget()->usageLimits(), null)
                    : $fixture->source->budget(),
            );
            try {
                (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($source);
                self::fail('Mismatched source must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1709', $failure->diagnosticId());
                self::assertStringNotContainsString('private-tool-input', (string) $failure);
                self::assertSame(0, $fixture->createCalls);
            }
        }
    }

    public function testNeutralProfileMismatchRejectsBeforeApplicationCreation(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $neutral = $fixture->openAiProfile->neutralProfile();
        $different = new ModelProfileReference(
            $neutral->version(),
            $neutral->identity(),
            $neutral->providerIdentity(),
            new ModelIdentity('different-openai-model'),
            $neutral->capabilities(),
        );
        $profile = new OpenAiResponsesProfile(
            $different,
            $fixture->openAiProfile->mappingVersion(),
            $fixture->openAiProfile->storagePreference(),
        );

        try {
            (new OpenAiResponsesInvocationAdapter($profile, $fixture->context))->open($fixture->source);
            self::fail('A different neutral model profile must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1709', $failure->diagnosticId());
            self::assertStringContainsString('violation=profile, stage=open', (string) $failure);
            self::assertSame(0, $fixture->createCalls);
        }
    }

    public function testStreamingSourceSelectsOnlyTheStreamingSession(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        self::assertInstanceOf(OpenAiResponsesStreamingInvocationSession::class, $session);
        self::assertSame(0, $fixture->createCalls);
        $session->close();
    }
}
