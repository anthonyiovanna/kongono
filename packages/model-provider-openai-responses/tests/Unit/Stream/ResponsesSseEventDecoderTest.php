<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Stream;

use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventDecoder;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventKind;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrame;
use PHPUnit\Framework\TestCase;

final class ResponsesSseEventDecoderTest extends TestCase
{
    public function testExactTypeAndSequenceDecode(): void
    {
        $event = (new ResponsesSseEventDecoder())->decode(new ResponsesSseFrame('response.created', '{"sequence_number":0,"type":"response.created"}'));
        self::assertSame(ResponsesSseEventKind::ResponseCreated, $event->kind());
        self::assertSame(0, $event->sequenceNumber());
    }

    public function testUnknownMismatchDuplicateAndRangeFailClosed(): void
    {
        foreach ([
            new ResponsesSseFrame(null, '{"sequence_number":0,"type":"unknown"}'),
            new ResponsesSseFrame('response.completed', '{"sequence_number":0,"type":"response.created"}'),
            new ResponsesSseFrame(null, '{"sequence_number":0,"sequence_number":0,"type":"response.created"}'),
            new ResponsesSseFrame(null, '{"nested":{"private":"a","private":"b"},"sequence_number":0,"type":"response.created"}'),
            new ResponsesSseFrame(null, '{"0":"numeric","sequence_number":0,"type":"response.created"}'),
            new ResponsesSseFrame(null, '{"sequence_number":-1,"type":"response.created"}'),
            new ResponsesSseFrame(null, '{"sequence_number":4096,"type":"response.created"}'),
            new ResponsesSseFrame(null, "{\"sequence_number\":0,\"type\":\"response.created\",\"value\":\"\xFF\"}"),
        ] as $frame) {
            try {
                (new ResponsesSseEventDecoder())->decode($frame);
                self::fail('Invalid event must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1716', $failure->diagnosticId());
            }
        }
    }

    public function testSequenceBoundaryValuesZeroAnd4095AreAccepted(): void
    {
        $decoder = new ResponsesSseEventDecoder();
        self::assertSame(0, $decoder->decode(new ResponsesSseFrame(null, '{"sequence_number":0,"type":"response.created"}'))->sequenceNumber());
        self::assertSame(4095, $decoder->decode(new ResponsesSseFrame(null, '{"sequence_number":4095,"type":"response.failed"}'))->sequenceNumber());
    }
}
