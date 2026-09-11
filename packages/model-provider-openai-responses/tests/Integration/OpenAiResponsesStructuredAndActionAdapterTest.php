<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Integration;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Runtime\ModelInvocationRuntime;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesStructuredAndActionAdapterTest extends TestCase
{
    #[DataProvider('modes')]
    public function testOneOperationProducesOnlyNormalizedInertEvidence(ResultMode $mode, string $fixtureName): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], (new StreamFactory())->createStream((string) file_get_contents(dirname(__DIR__) . '/Fixture/Response/' . $fixtureName)));
        $fixture = new OpenAiResponsesAdapterFixture(response: $response, resultMode: $mode);
        $effectCalls = 0;
        $fakeCallable = static function () use (&$effectCalls): void {
            ++$effectCalls;
        };
        $runtimeRequest = new InvocationRuntimeRequest(
            $fixture->source,
            $fixture->artifactBytes,
            new ReceiptIdentity('receipt.model.openai.responses.v2'),
            new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context),
            new class implements InvocationCancellation {
                public function isCancellationRequested(): bool
                {
                    return false;
                }
            },
            new class implements InvocationMonotonicClock {
                private int $value = 0;
                public function milliseconds(): int
                {
                    return $this->value++;
                }
            },
        );
        $result = (new ModelInvocationRuntime())->run($runtimeRequest)->result();
        self::assertNotNull($result);
        self::assertSame($mode, $result->mode());
        self::assertSame(ResultMode::ActionSuggestion === $mode ? 'action_suggested' : 'completed', $result->finishReason()->value);
        self::assertSame(1, $fixture->createCalls);
        self::assertSame(1, $fixture->transport?->openCalls);
        self::assertSame(0, $effectCalls);
        unset($fakeCallable);
        self::assertNotNull($fixture->application);
        self::assertFalse($fixture->application->hasActiveOperation());
    }

    public function testVersionTwoStreamingFailsBeforeExternalServiceCreation(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(resultMode: ResultMode::Structured);
        $baseRequest = $fixture->source->request();
        $baseProfile = $baseRequest->profile();
        $streamingProfile = new ModelProfileReference(
            $baseProfile->version(),
            $baseProfile->identity(),
            $baseProfile->providerIdentity(),
            $baseProfile->modelIdentity(),
            new ProviderCapabilitySet([ProviderCapability::StructuredResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence]),
        );
        $streamingRequest = new InvocationRequest(
            $baseRequest->version(),
            $baseRequest->identity(),
            $streamingProfile,
            $baseRequest->messages(),
            ResultMode::Structured,
            true,
            $baseRequest->limits(),
        );
        $streamingSource = new InvocationPlanSource(
            $fixture->source->identity(),
            $fixture->source->adapterIdentity(),
            $streamingRequest,
            $fixture->source->budget(),
        );

        try {
            (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($streamingSource);
            self::fail('Version 2 streaming must fail before creating an external-service application.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1709', $failure->diagnosticId());
        }

        self::assertSame(0, $fixture->createCalls);
        self::assertNull($fixture->application);
    }

    /** @return iterable<string, array{ResultMode, string}> */
    public static function modes(): iterable
    {
        yield 'structured' => [ResultMode::Structured, 'completed-structured.json'];
        yield 'action suggestion' => [ResultMode::ActionSuggestion, 'completed-function-call.json'];
    }
}
