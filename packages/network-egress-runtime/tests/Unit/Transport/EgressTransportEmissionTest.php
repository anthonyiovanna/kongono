<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Transport;

use Kongono\Http\Message\Message\Response;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use PHPUnit\Framework\TestCase;

final class EgressTransportEmissionTest extends TestCase
{
    public function testProgressAndTerminalShapesAreExplicit(): void
    {
        $transfer = new EgressTransferEvidence(EgressTransferState::RequestPartiallySent, 1, 0);
        $progress = new EgressTransportEmission($transfer, NegotiatedProtocol::Http11, 2, 3, null, null);
        self::assertFalse($progress->terminal());
        self::assertSame($transfer, $progress->transfer());
        $failure = new EgressFailure(EgressFailureKind::Connection, 'connection_failed');
        self::assertTrue((new EgressTransportEmission($transfer, NegotiatedProtocol::Http11, 0, 0, null, $failure))->terminal());
        $response = new Response();
        self::assertSame($response, (new EgressTransportEmission(new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1), NegotiatedProtocol::Http11, 0, 0, $response, null))->response());
    }

    public function testInvalidBoundsAndResponseShapesAreRejected(): void
    {
        $transfer = new EgressTransferEvidence(EgressTransferState::None, 0, 0);
        foreach ([
            [-1, 0, null, null],
            [0, -1, null, null],
            [0, 0, new Response(), null],
            [0, 0, new Response(), new EgressFailure(EgressFailureKind::Connection, 'failed')],
        ] as [$buffered, $retained, $response, $failure]) {
            try {
                new EgressTransportEmission($transfer, NegotiatedProtocol::Unknown, $buffered, $retained, $response, $failure);
                self::fail('Expected invalid transport emission.');
            } catch (InvalidEgressRuntimeException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
