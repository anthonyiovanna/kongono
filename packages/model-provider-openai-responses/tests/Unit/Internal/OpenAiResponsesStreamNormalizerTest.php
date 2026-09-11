<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Internal;

use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesStreamNormalizer;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesStreamNormalizerTest extends TestCase
{
    public function testNeutralSequenceAndUsageAreExact(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $normalizer = new OpenAiResponsesStreamNormalizer();
        $request = $fixture->source->request();
        $decoded = (new ResponsesResponseDecoder())->decode($request, $fixture->openAiProfile, OpenAiResponsesAdapterFixture::completedBody());
        $events = [$normalizer->started($request), $normalizer->delta($request, 'x'), $normalizer->usage($request, $decoded), $normalizer->completed($request)];
        self::assertSame([1, 2, 3, 4], array_map(static fn($event): int => $event?->sequence()->value() ?? 0, $events));
        self::assertSame([StreamEventKind::Started, StreamEventKind::ContentDelta, StreamEventKind::Usage, StreamEventKind::Completed], array_map(static fn($event): ?StreamEventKind => $event?->kind(), $events));
    }

    public function testMissingUsageAndInvalidDeltaRemainClosed(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $decoded = (new ResponsesResponseDecoder())->decode($fixture->source->request(), $fixture->openAiProfile, OpenAiResponsesAdapterFixture::completedBody(false));
        self::assertNull((new OpenAiResponsesStreamNormalizer())->usage($fixture->source->request(), $decoded));

        foreach ([' ', str_repeat('x', 8193)] as $delta) {
            try {
                (new OpenAiResponsesStreamNormalizer())->delta($fixture->source->request(), $delta);
                self::fail('Invalid neutral delta must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1716', $failure->diagnosticId());
            }
        }
    }
}
