<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Stream;

use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationContract\Stream\StreamSequence;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\TestCase;

final class ModelStreamEventTest extends TestCase
{
    public function testVocabularySequenceAndEveryEventShapeAreExact(): void
    {
        self::assertSame(['started', 'content_delta', 'action_name', 'action_arguments_delta', 'usage', 'completed', 'failed'], array_column(StreamEventKind::cases(), 'value'));
        $identity = new InvocationIdentity('invoke');
        $usage = new UsageEvidence([new UsageQuantity(new UsageMetricName('input'), new UsageUnitName('token'), 0)]);
        $events = [new ModelStreamEvent($identity, new StreamSequence(1), StreamEventKind::Started), new ModelStreamEvent($identity, new StreamSequence(2), StreamEventKind::ContentDelta, 'x'), new ModelStreamEvent($identity, new StreamSequence(3), StreamEventKind::ActionName, 'lookup'), new ModelStreamEvent($identity, new StreamSequence(4), StreamEventKind::ActionArgumentsDelta, '{}'), new ModelStreamEvent($identity, new StreamSequence(5), StreamEventKind::Usage, usage: $usage), new ModelStreamEvent($identity, new StreamSequence(6), StreamEventKind::Completed), new ModelStreamEvent($identity, new StreamSequence(4096), StreamEventKind::Failed)];
        self::assertCount(7, $events);
        self::assertSame('x', $events[1]->payload());
        self::assertSame($usage, $events[4]->usage());
        self::assertSame(8192, strlen((new ModelStreamEvent($identity, new StreamSequence(7), StreamEventKind::ContentDelta, str_repeat('x', 8192)))->payload() ?? ''));
    }

    public function testInvalidSequencesShapesAndPayloadBoundsFailClosed(): void
    {
        $identity = new InvocationIdentity('invoke');
        foreach ([0, 4097] as $sequence) {
            try {
                new StreamSequence($sequence);
                self::fail('Expected invalid sequence.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1406', $exception->diagnosticId());
            }
        }
        foreach ([[StreamEventKind::Started, 'x'], [StreamEventKind::ContentDelta, null], [StreamEventKind::ContentDelta, str_repeat('x', 8193)]] as [$kind, $payload]) {
            try {
                new ModelStreamEvent($identity, new StreamSequence(1), $kind, $payload);
                self::fail('Expected invalid stream event.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-MODEL-CONTRACT-1406', 'KGN-MODEL-CONTRACT-1409']);
            }
        }
    }
}
