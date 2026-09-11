<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Adapter;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesStreamingInvocationSession;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesStreamingInvocationSessionTest extends TestCase
{
    public function testApplicationIsLazyAndPullsOneNeutralEmissionAtATime(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        self::assertInstanceOf(OpenAiResponsesStreamingInvocationSession::class, $session);
        self::assertSame(0, $fixture->createCalls);
        $kinds = [];
        $result = null;
        while (null !== ($emission = $session->next())) {
            if (null !== $emission->eventValue()) {
                $kinds[] = $emission->eventValue()->kind();
            }
            $result = $emission->resultValue() ?? $result;
        }
        self::assertSame([StreamEventKind::Started, StreamEventKind::ContentDelta, StreamEventKind::ContentDelta, StreamEventKind::Usage, StreamEventKind::Completed], $kinds);
        self::assertSame('synthetic-stream-output', $result?->text()?->value());
        self::assertSame(1, $fixture->createCalls);
        self::assertNull($session->next());
        $session->close();
        $session->close();
    }

    public function testEarlyCloseCreatesNoApplication(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        $session->close();
        self::assertSame(0, $fixture->createCalls);
        self::assertNull($session->next());
    }

    public function testFailedProviderEmitsFailedThenExhausts(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(
            new Response(200, ['Content-Type' => 'text/event-stream'], (new StreamFactory())->createStream(OpenAiResponsesAdapterFixture::streamBody('failed-text-stream.sse'))),
            streaming: true,
        );
        $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
        $started = $session->next();
        self::assertNotNull($started);
        self::assertNotNull($started->eventValue());
        self::assertSame(StreamEventKind::Started, $started->eventValue()->kind());
        $failed = $session->next();
        self::assertNotNull($failed);
        self::assertNotNull($failed->eventValue());
        self::assertSame(StreamEventKind::Failed, $failed->eventValue()->kind());
        self::assertNull($session->next());
        self::assertNull($session->next());
        $session->close();
        self::assertFalse($fixture->responseBodyReadable());
    }

    public function testMissingTerminalAndTrailingEventFailAndClose(): void
    {
        $complete = OpenAiResponsesAdapterFixture::streamBody('completed-text-stream.sse');
        $lines = preg_split('/\n\n/', trim($complete));
        self::assertIsArray($lines);
        array_pop($lines);
        $missingTerminal = implode("\n\n", $lines) . "\n\n";
        $trailing = $complete . "data: {\"response\":{},\"sequence_number\":10,\"type\":\"response.completed\"}\n\n";

        foreach ([$missingTerminal, $trailing] as $body) {
            $fixture = new OpenAiResponsesAdapterFixture(
                new Response(200, ['Content-Type' => 'text/event-stream'], (new StreamFactory())->createStream($body)),
                streaming: true,
            );
            $session = (new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context))->open($fixture->source);
            try {
                while (null !== $session->next()) {
                }
                self::fail('Missing or trailing terminal evidence must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-OPENAI-RESPONSES-1715', 'KGN-OPENAI-RESPONSES-1717']);
                self::assertFalse($fixture->responseBodyReadable());
            } finally {
                $session->close();
            }
        }
    }
}
