<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Adapter;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationSession;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationSessionState;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenAiResponsesInvocationSessionTest extends TestCase
{
    public function testSuccessPerformsExactlyOneOperationAndOneResultEmission(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture();
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        self::assertInstanceOf(OpenAiResponsesInvocationSession::class, $session);
        $emission = $session->next();
        self::assertNotNull($emission);
        self::assertNull($emission->eventValue());
        self::assertSame('synthetic output', $emission->resultValue()?->text()?->value());
        self::assertSame(1, $fixture->createCalls);
        self::assertNotNull($fixture->credentialSource);
        self::assertNotNull($fixture->lease);
        self::assertNotNull($fixture->resolver);
        self::assertNotNull($fixture->transport);
        self::assertNotNull($fixture->transportSession);
        self::assertSame(1, $fixture->credentialSource->calls);
        self::assertSame(1, $fixture->lease->materialCalls);
        self::assertSame(1, $fixture->lease->retireCalls);
        self::assertSame(1, $fixture->resolver->calls);
        self::assertSame(1, $fixture->transport->openCalls);
        self::assertSame(1, $fixture->transportSession->closeCalls);
        self::assertNotNull($fixture->application);
        self::assertSame(\Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplicationState::Retired, $fixture->application->state());
        self::assertFalse($fixture->application->hasActiveOperation());
        $request = $fixture->transport->request?->operation()->request();
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.openai.invalid/v1/responses', (string) $request->getUri());
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('Bearer fixture-credential-17c', $request->getHeaderLine('Authorization'));
        self::assertStringContainsString('"stream":false', $request->getBody()->getContents());
        self::assertSame(OpenAiResponsesInvocationSessionState::Emitted, $session->state());
        self::assertNull($session->next());
        self::assertNull($session->next());
        self::assertSame(OpenAiResponsesInvocationSessionState::Exhausted, $session->state());
        $session->close();
        $session->close();
        self::assertSame(OpenAiResponsesInvocationSessionState::Closed, $session->state());
        $fixture->transport->request = null;
    }

    public function testCancellationFailureDoesNotReplay(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(cancellationAnswers: [true]);
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        self::assertInstanceOf(OpenAiResponsesInvocationSession::class, $session);
        try {
            $session->next();
            self::fail('Cancelled external operation must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1711', $failure->diagnosticId());
            self::assertSame(1, $fixture->createCalls);
            self::assertSame(0, $fixture->transport?->openCalls);
            self::assertSame(OpenAiResponsesInvocationSessionState::Failed, $session->state());
            self::assertNotNull($fixture->application);
            self::assertFalse($fixture->application->hasActiveOperation());
        }
        try {
            $session->next();
            self::fail('Failed session must not replay.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1710', $failure->diagnosticId());
        }
        $session->close();
    }

    public function testApplicationCreationFailureIsOneAttemptAndLeavesNoOperation(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(creationFailure: new RuntimeException('private-create-failure'));
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        try {
            $session->next();
            self::fail('Application creation failure must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1711', $failure->diagnosticId());
            self::assertStringContainsString('violation=application_creation, stage=external_service', (string) $failure);
            self::assertStringNotContainsString('private-create-failure', (string) $failure);
            self::assertSame(1, $fixture->createCalls);
            self::assertNull($fixture->credentialSource);
            self::assertNull($fixture->transport);
        }
        $session->close();
    }

    public function testTerminalExternalFailuresAndTrailingEmissionNeverReplay(): void
    {
        foreach (['failure_before_effect', 'uncertain_effect', 'trailing_emission'] as $mode) {
            $fixture = new OpenAiResponsesAdapterFixture(transportMode: $mode);
            $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
            self::assertInstanceOf(OpenAiResponsesInvocationSession::class, $session);
            $this->assertExternalFailureDoesNotReplay($fixture, $session);
            self::assertNotNull($fixture->lease);
            self::assertNotNull($fixture->transport);
            self::assertNotNull($fixture->transportSession);
            self::assertSame(1, $fixture->lease->retireCalls);
            self::assertSame(1, $fixture->transport->openCalls);
            self::assertSame(1, $fixture->transportSession->closeCalls);
            self::assertNotNull($fixture->application);
            self::assertFalse($fixture->application->hasActiveOperation());
            self::assertSame(OpenAiResponsesInvocationSessionState::Failed, $session->state());
            $session->close();
        }
    }

    public function testCancellationAfterPossibleEffectNeverReplays(): void
    {
        $answers = [...array_fill(0, 9, false), true];
        $fixture = new OpenAiResponsesAdapterFixture(cancellationAnswers: $answers, transportMode: 'request_progress');
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        $this->assertExternalFailureDoesNotReplay($fixture, $session);
        self::assertNotNull($fixture->egressCancellation);
        self::assertNotNull($fixture->transport);
        self::assertNotNull($fixture->transportSession);
        self::assertSame(10, $fixture->egressCancellation->calls);
        self::assertSame(1, $fixture->transport->openCalls);
        self::assertSame(1, $fixture->transportSession->nextCalls);
        self::assertSame(1, $fixture->transportSession->closeCalls);
        self::assertNotNull($fixture->application);
        self::assertFalse($fixture->application->hasActiveOperation());
        $session->close();
    }

    public function testHttpAndDecodeFailuresCloseAndRetireWithoutTrailingState(): void
    {
        foreach ([
            new Response(503, ['Content-Type' => 'application/json'], (new StreamFactory())->createStream('private-error-body')),
            new Response(200, ['Content-Type' => 'application/json'], (new StreamFactory())->createStream('{malformed')),
        ] as $response) {
            $fixture = new OpenAiResponsesAdapterFixture($response);
            $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
            self::assertInstanceOf(OpenAiResponsesInvocationSession::class, $session);
            try {
                $session->next();
                self::fail('Rejected response must fail.');
            } catch (\Throwable $failure) {
                self::assertStringNotContainsString('private-error-body', (string) $failure);
                self::assertSame(1, $fixture->createCalls);
                self::assertSame(1, $fixture->transportSession?->closeCalls);
                self::assertFalse($response->getBody()->isReadable());
                self::assertNotNull($fixture->application);
                self::assertFalse($fixture->application->hasActiveOperation());
            }
            $session->close();
            self::assertSame(OpenAiResponsesInvocationSessionState::Closed, $session->state());
        }
    }

    public function testSequentialIndependentInvocationsDoNotShareState(): void
    {
        $first = new OpenAiResponsesAdapterFixture();
        $second = new OpenAiResponsesAdapterFixture(new Response(200, ['Content-Type' => 'application/json'], (new StreamFactory())->createStream(OpenAiResponsesAdapterFixture::incompleteBody('content_filter'))), credential: 'second-credential-17c');
        $firstResult = (new OpenAiResponsesInvocationAdapter($first->openAiProfile, $first->context))->open($first->source)->next()?->resultValue();
        $secondResult = (new OpenAiResponsesInvocationAdapter($second->openAiProfile, $second->context))->open($second->source)->next()?->resultValue();
        self::assertNotNull($firstResult);
        self::assertNotNull($secondResult);
        self::assertNotSame($firstResult->identity()->value(), $secondResult->identity()->value());
        self::assertNotSame($firstResult->finishReason(), $secondResult->finishReason());
        self::assertSame(1, $first->lease?->materialCalls);
        self::assertSame(1, $second->lease?->materialCalls);
        self::assertNotNull($first->application);
        self::assertNotNull($second->application);
        self::assertFalse($first->application->hasActiveOperation());
        self::assertFalse($second->application->hasActiveOperation());
        self::assertStringNotContainsString('second-credential-17c', $first->transport?->request?->operation()->request()->getHeaderLine('Authorization') ?? '');
    }

    private function assertExternalFailureDoesNotReplay(OpenAiResponsesAdapterFixture $fixture, \Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession $session): void
    {
        try {
            $session->next();
            self::fail('Terminal external-service failure must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1711', $failure->diagnosticId());
            self::assertSame(1, $fixture->createCalls);
        }
        try {
            $session->next();
            self::fail('A failed session must not replay.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1710', $failure->diagnosticId());
            self::assertSame(1, $fixture->createCalls);
        }
    }
}
