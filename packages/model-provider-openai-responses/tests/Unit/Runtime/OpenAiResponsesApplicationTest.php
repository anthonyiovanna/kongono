<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Runtime;

use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesExternalServiceApplicationFactory;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesApplicationProfileException;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesApplicationLifecycleException;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesApplicationOperationException;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplicationState;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesApplicationFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class OpenAiResponsesApplicationTest extends TestCase
{
    #[DataProvider('supportedModes')]
    public function testAllFourSupportedModesComposeOneExactTerminalOperation(ResultMode $mode, bool $streaming): void
    {
        $fixture = new OpenAiResponsesApplicationFixture($mode, $streaming);
        try {
            $result = $fixture->application()->run($fixture->runInput());
            self::assertSame(InvocationState::Retired, $result->state());
            self::assertSame($mode, $result->result()?->mode());
            self::assertSame(1, $fixture->adapterFixture->createCalls);
            self::assertSame(1, $fixture->adapterFixture->transport?->openCalls);
            self::assertSame(OpenAiResponsesApplicationState::Retired, $fixture->application()->state());
            if ($streaming) {
                self::assertSame(
                    [StreamEventKind::Started, StreamEventKind::ContentDelta, StreamEventKind::ContentDelta, StreamEventKind::Usage, StreamEventKind::Completed],
                    array_map(static fn($event): StreamEventKind => $event->kind(), $result->events()),
                );
            }
            if (ResultMode::ActionSuggestion === $mode) {
                self::assertNotNull($result->result()->actionSuggestion());
                self::assertNull($result->result()->text());
            }
        } finally {
            $fixture->cleanup();
        }
    }

    public function testCancellationBeforeFirstPullCreatesNoExternalApplicationAndRetires(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture();
        try {
            $result = $fixture->application()->run($fixture->runInput([true]));
            self::assertSame('cancelled', $result->receipt()->outcome()->value);
            self::assertSame(0, $fixture->adapterFixture->createCalls);
            self::assertNull($fixture->adapterFixture->application);
            self::assertSame(OpenAiResponsesApplicationState::Retired, $fixture->application()->state());
        } finally {
            $fixture->cleanup();
        }
    }

    public function testFactoryProfileMismatchFailsBeforeCreationAndRetires(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture();
        $foreign = new ExternalServiceApplicationProfile(
            $fixture->adapterFixture->profile()->bindingSource(),
            $fixture->adapterFixture->profile()->bindingArtifact(),
            $fixture->adapterFixture->profile()->binding(),
            $fixture->adapterFixture->profile()->networkEgressProfile(),
            new \Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileIdentity(str_repeat('c', 64)),
        );
        $factory = new class ($foreign, $fixture->adapterFixture) implements OpenAiResponsesExternalServiceApplicationFactory {
            public int $createCalls = 0;
            public function __construct(private ExternalServiceApplicationProfile $profileValue, private OpenAiResponsesExternalServiceApplicationFactory $delegate) {}
            public function profile(): ExternalServiceApplicationProfile
            {
                return $this->profileValue;
            }
            public function create(): ExternalServiceApplication
            {
                ++$this->createCalls;
                return $this->delegate->create();
            }
        };
        try {
            try {
                $fixture->application()->run($fixture->runInput(factory: $factory));
                self::fail('A mismatched advertised factory profile must fail.');
            } catch (InvalidOpenAiResponsesApplicationProfileException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1720', $failure->diagnosticId());
            }
            self::assertSame(0, $factory->createCalls);
            self::assertSame(0, $fixture->adapterFixture->createCalls);
            self::assertSame(OpenAiResponsesApplicationState::Retired, $fixture->application()->state());
        } finally {
            $fixture->cleanup();
        }
    }

    public function testProviderAndUncertainFailuresRetireWithoutReplay(): void
    {
        foreach (['failure_before_effect', 'uncertain_effect'] as $mode) {
            $fixture = new OpenAiResponsesApplicationFixture(transportMode: $mode);
            try {
                try {
                    $fixture->application()->run($fixture->runInput());
                    self::fail('A terminal lower failure must remain visible.');
                } catch (OpenAiResponsesApplicationOperationException $failure) {
                    self::assertSame('KGN-OPENAI-RESPONSES-1723', $failure->diagnosticId());
                    self::assertNotNull($failure->getPrevious());
                }
                self::assertSame(1, $fixture->adapterFixture->createCalls);
                self::assertSame(1, $fixture->adapterFixture->transport?->openCalls);
                self::assertSame(OpenAiResponsesApplicationState::Retired, $fixture->application()->state());
                try {
                    $fixture->application()->run($fixture->runInput());
                    self::fail('A retired application must reject reuse.');
                } catch (OpenAiResponsesApplicationLifecycleException) {
                    self::assertSame(1, $fixture->adapterFixture->createCalls);
                }
            } finally {
                $fixture->cleanup();
            }
        }
    }

    public function testExactForeignFailureIdentityRemainsInTheProgrammaticChainAndPresentationIsSafe(): void
    {
        $sentinel = new RuntimeException('private-creation-failure-17f');
        $fixture = new OpenAiResponsesApplicationFixture(creationFailure: $sentinel);
        try {
            try {
                $fixture->application()->run($fixture->runInput());
                self::fail('Creation failure must remain terminal.');
            } catch (OpenAiResponsesApplicationOperationException $failure) {
                self::assertTrue(self::chainContains($failure, $sentinel));
                self::assertStringNotContainsString($sentinel->getMessage(), (string) $failure);
            }
            self::assertSame(OpenAiResponsesApplicationState::Retired, $fixture->application()->state());
        } finally {
            $fixture->cleanup();
        }
    }

    public function testReentryAndIndependentPersistentProcessInstancesAreIsolated(): void
    {
        $first = new OpenAiResponsesApplicationFixture();
        $second = new OpenAiResponsesApplicationFixture();
        $reentrantFactory = new class ($first->adapterFixture) implements OpenAiResponsesExternalServiceApplicationFactory {
            public ?\Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplication $application = null;
            public ?\Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplicationRunInput $input = null;
            public ?Throwable $reentryFailure = null;
            public function __construct(private OpenAiResponsesExternalServiceApplicationFactory $delegate) {}
            public function profile(): ExternalServiceApplicationProfile
            {
                try {
                    $this->application?->run($this->input ?? throw new RuntimeException('missing input'));
                } catch (Throwable $failure) {
                    $this->reentryFailure = $failure;
                }
                return $this->delegate->profile();
            }
            public function create(): ExternalServiceApplication
            {
                return $this->delegate->create();
            }
        };
        try {
            $reentrantFactory->application = $first->application();
            $input = $first->runInput(factory: $reentrantFactory);
            $reentrantFactory->input = $input;
            $first->application()->run($input);
            self::assertInstanceOf(OpenAiResponsesApplicationLifecycleException::class, $reentrantFactory->reentryFailure);
            self::assertSame(OpenAiResponsesApplicationState::Retired, $first->application()->state());
            self::assertSame(OpenAiResponsesApplicationState::Ready, $second->application()->state());
            $second->application()->run($second->runInput());
            self::assertSame(1, $first->adapterFixture->createCalls);
            self::assertSame(1, $second->adapterFixture->createCalls);
        } finally {
            $first->cleanup();
            $second->cleanup();
        }
    }

    /** @return iterable<string, array{ResultMode, bool}> */
    public static function supportedModes(): iterable
    {
        yield 'text' => [ResultMode::Text, false];
        yield 'streaming text' => [ResultMode::Text, true];
        yield 'structured' => [ResultMode::Structured, false];
        yield 'inert action suggestion' => [ResultMode::ActionSuggestion, false];
    }

    private static function chainContains(Throwable $failure, Throwable $sentinel): bool
    {
        do {
            if ($failure === $sentinel) {
                return true;
            }
            $failure = $failure->getPrevious();
        } while ($failure instanceof Throwable);

        return false;
    }
}
