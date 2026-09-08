<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Transfer;

use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use PHPUnit\Framework\TestCase;

final class EgressTransferEvidenceTest extends TestCase
{
    public function testEveryCoherentStateAndByteBoundaryIsAccepted(): void
    {
        $cases = [
            [EgressTransferState::None, 0, 0],
            [EgressTransferState::RequestPartiallySent, 1, 0],
            [EgressTransferState::RequestSent, 1073741824, 0],
            [EgressTransferState::ResponsePartiallyReceived, 1, 1],
            [EgressTransferState::ResponseReceived, 1073741824, 1073741824],
        ];
        foreach ($cases as [$state, $request, $response]) {
            $evidence = new EgressTransferEvidence($state, $request, $response);
            self::assertSame($state, $evidence->state());
            self::assertSame($request, $evidence->requestBytes());
            self::assertSame($response, $evidence->responseBytes());
            self::assertTrue($evidence->equals(new EgressTransferEvidence($state, $request, $response)));
        }
    }

    public function testIncoherentAndOutOfRangeEvidenceFailsClosed(): void
    {
        foreach ([[EgressTransferState::None, 1, 0], [EgressTransferState::RequestSent, 0, 0], [EgressTransferState::ResponseReceived, 1, 0], [EgressTransferState::None, -1, 0], [EgressTransferState::None, 0, 1073741825]] as [$state, $request, $response]) {
            try {
                new EgressTransferEvidence($state, $request, $response);
                self::fail('Expected transfer rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EGRESS-CONTRACT-1507', 'KGN-EGRESS-CONTRACT-1508']);
            }
        }
    }
}
