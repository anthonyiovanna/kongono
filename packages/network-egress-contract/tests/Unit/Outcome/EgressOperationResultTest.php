<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Outcome;

use Kongono\Http\Message\Message\Response;
use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOperationResult;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class EgressOperationResultTest extends TestCase
{
    public function testSuccessRetainsResponseWithoutReadingItsBody(): void
    {
        $stream = new ResultBodyProbe();
        $response = new Response(body: $stream);
        $result = new EgressOperationResult(self::successReceipt(), $response, null);
        self::assertSame($response, $result->response());
        self::assertNull($result->failure());
        self::assertSame(0, $stream->calls);
    }

    public function testNonSuccessRequiresExactlyOneFailureAndNoResponse(): void
    {
        $failure = new EgressFailure(EgressFailureKind::PolicyDenied, 'policy_denied');
        $result = new EgressOperationResult(self::failedReceipt(), null, $failure);
        self::assertSame($failure, $result->failure());
        self::assertNull($result->response());

        foreach ([[self::successReceipt(), null, null], [self::successReceipt(), new Response(), $failure], [self::failedReceipt(), new Response(), $failure], [self::failedReceipt(), null, null]] as [$receipt, $response, $invalidFailure]) {
            try {
                new EgressOperationResult($receipt, $response, $invalidFailure);
                self::fail('Expected result rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1507', $exception->diagnosticId());
            }
        }
    }

    private static function successReceipt(): EgressReceipt
    {
        return new EgressReceipt(new EgressReceiptIdentity('receipt'), new EgressOperationIdentity('operation'), new EgressAttemptIdentity('attempt'), EgressLifecycleState::Succeeded, EgressOutcome::Succeeded, 1, new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1), new CancellationEvidence(CancellationState::NotRequested, 0), NegotiatedProtocol::Http11, new TransportCapabilitySet([TransportCapability::Http11]));
    }

    private static function failedReceipt(): EgressReceipt
    {
        return new EgressReceipt(new EgressReceiptIdentity('receipt'), new EgressOperationIdentity('operation'), null, EgressLifecycleState::Failed, EgressOutcome::FailedBeforeEffect, 0, new EgressTransferEvidence(EgressTransferState::None, 0, 0), new CancellationEvidence(CancellationState::NotRequested, 0), NegotiatedProtocol::Unknown, new TransportCapabilitySet([TransportCapability::Http11]));
    }
}

final class ResultBodyProbe implements StreamInterface
{
    public int $calls = 0;
    public function __toString(): string
    {
        return $this->fail();
    }
    public function close(): void
    {
        $this->fail();
    }
    public function detach()
    {
        return $this->fail();
    }
    public function getSize(): ?int
    {
        return $this->fail();
    }
    public function tell(): int
    {
        return $this->fail();
    }
    public function eof(): bool
    {
        return $this->fail();
    }
    public function isSeekable(): bool
    {
        return $this->fail();
    }
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->fail();
    }
    public function rewind(): void
    {
        $this->fail();
    }
    public function isWritable(): bool
    {
        return $this->fail();
    }
    public function write(string $string): int
    {
        return $this->fail();
    }
    public function isReadable(): bool
    {
        return $this->fail();
    }
    public function read(int $length): string
    {
        return $this->fail();
    }
    public function getContents(): string
    {
        return $this->fail();
    }
    public function getMetadata(?string $key = null)
    {
        return $this->fail();
    }

    private function fail(): never
    {
        ++$this->calls;
        throw new RuntimeException('Body stream access is forbidden.');
    }
}
