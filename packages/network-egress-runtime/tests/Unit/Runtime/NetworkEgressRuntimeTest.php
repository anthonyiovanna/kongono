<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Runtime;

use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Runtime\NetworkEgressRuntime;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeCancellation;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeClock;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeResolver;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeTransport;
use Kongono\NetworkEgressRuntime\Tests\Fixture\FakeTransportSession;
use Kongono\NetworkEgressRuntime\Tests\Fixture\RuntimeFixture;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class NetworkEgressRuntimeTest extends TestCase
{
    public function testSuccessUsesOneResolutionOneAttemptExactAddressAndClosesOnce(): void
    {
        $resolver = new FakeResolver(RuntimeFixture::resolution('8.8.8.8'));
        $session = RuntimeFixture::session();
        $transport = new FakeTransport([RuntimeFixture::capabilities()], $session);
        $result = (new NetworkEgressRuntime())->run(RuntimeFixture::request(resolver: $resolver, transport: $transport));
        self::assertSame(EgressOutcome::Succeeded, $result->operationResult()->receipt()->outcome());
        self::assertSame(1, $resolver->calls);
        self::assertSame(1, $transport->openCalls);
        self::assertSame('8.8.8.8', $transport->request?->selectedAddress()->value());
        self::assertSame(2, $transport->capabilityCalls);
        self::assertSame(1, $session->closeCalls);
        self::assertSame(1, $result->resolutionCandidateCount());
    }

    public function testAuthorityDenialAndRevocationPreventRequiredEffects(): void
    {
        $resolver = new FakeResolver(RuntimeFixture::resolution());
        $transport = new FakeTransport([RuntimeFixture::capabilities()], RuntimeFixture::session());
        $denied = (new NetworkEgressRuntime())->run(RuntimeFixture::request(new FakeAuthorityCurrentness([false]), $resolver, $transport));
        self::assertSame(EgressOutcome::RejectedBeforeAttempt, $denied->operationResult()->receipt()->outcome());
        self::assertSame(0, $resolver->calls);
        self::assertSame(0, $transport->openCalls);

        $resolver = new FakeResolver(RuntimeFixture::resolution());
        $transport = new FakeTransport([RuntimeFixture::capabilities()], RuntimeFixture::session());
        $revoked = (new NetworkEgressRuntime())->run(RuntimeFixture::request(new FakeAuthorityCurrentness([true, false]), $resolver, $transport));
        self::assertSame(EgressOutcome::RejectedBeforeAttempt, $revoked->operationResult()->receipt()->outcome());
        self::assertSame(1, $resolver->calls);
        self::assertSame(0, $transport->openCalls);
    }

    public function testCancellationBeforeResolutionReturnsTypedResult(): void
    {
        $resolver = new FakeResolver(RuntimeFixture::resolution());
        $transport = new FakeTransport([RuntimeFixture::capabilities()], RuntimeFixture::session());
        $result = (new NetworkEgressRuntime())->run(RuntimeFixture::request(resolver: $resolver, transport: $transport, cancellation: new FakeCancellation([true])));
        self::assertSame(EgressOutcome::CancelledBeforeEffect, $result->operationResult()->receipt()->outcome());
        self::assertSame(0, $resolver->calls);
        self::assertSame(0, $transport->openCalls);
    }

    public function testMixedResolutionAndTrailingEmissionFailClosedAndCloseOnce(): void
    {
        $selected = new \Kongono\NetworkEgressRuntime\Address\ResolvedAddress('8.8.8.8');
        $mixed = new \Kongono\NetworkEgressRuntime\Address\ResolutionEvidence([$selected, new \Kongono\NetworkEgressRuntime\Address\ResolvedAddress('127.0.0.1')], $selected);
        $resolver = new FakeResolver(new \Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult($mixed, null));
        $transport = new FakeTransport([RuntimeFixture::capabilities()], RuntimeFixture::session());
        $result = (new NetworkEgressRuntime())->run(RuntimeFixture::request(resolver: $resolver, transport: $transport));
        self::assertSame(EgressOutcome::FailedBeforeEffect, $result->operationResult()->receipt()->outcome());
        self::assertSame(0, $transport->openCalls);

        $terminal = new EgressTransportEmission(new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1), NegotiatedProtocol::Http11, 0, 0, new \Kongono\Http\Message\Message\Response(), null);
        $session = new FakeTransportSession([$terminal, $terminal]);
        $transport = new FakeTransport([RuntimeFixture::capabilities()], $session);
        $result = (new NetworkEgressRuntime())->run(RuntimeFixture::request(transport: $transport));
        self::assertSame(EgressOutcome::UncertainEffect, $result->operationResult()->receipt()->outcome());
        self::assertSame(1, $session->closeCalls);
    }

    public function testSequentialReuseClearsCompletedOperationState(): void
    {
        $runtime = new NetworkEgressRuntime();
        self::assertSame(EgressOutcome::Succeeded, $runtime->run(RuntimeFixture::request())->operationResult()->receipt()->outcome());
        self::assertSame(EgressOutcome::Succeeded, $runtime->run(RuntimeFixture::request())->operationResult()->receipt()->outcome());
    }

    public function testTotalTimeoutIsAnExpectedTypedFailure(): void
    {
        $resolver = new FakeResolver(RuntimeFixture::resolution());
        $transport = new FakeTransport([RuntimeFixture::capabilities()], RuntimeFixture::session());
        $result = (new NetworkEgressRuntime())->run(RuntimeFixture::request(
            resolver: $resolver,
            transport: $transport,
            clock: new FakeClock([0, 5_001]),
        ));
        self::assertSame(EgressOutcome::FailedBeforeEffect, $result->operationResult()->receipt()->outcome());
        self::assertSame(0, $resolver->calls);
        self::assertSame(0, $transport->openCalls);
        self::assertSame('total_timeout', $result->operationResult()->failure()?->reason());
    }

    public function testRequestAndResponseBodyStreamsAreNeverAccessed(): void
    {
        $requestBody = new RuntimeBodyProbe();
        $responseBody = new RuntimeBodyProbe();
        $message = new \Kongono\Http\Message\Message\ServerRequest('POST', 'https://example.com/resource', body: $requestBody);
        $response = new \Kongono\Http\Message\Message\Response(body: $responseBody);
        $session = new FakeTransportSession([
            new EgressTransportEmission(
                new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
                NegotiatedProtocol::Http11,
                0,
                0,
                $response,
                null,
            ),
            null,
        ]);
        $result = (new NetworkEgressRuntime())->run(RuntimeFixture::request(
            transport: new FakeTransport([RuntimeFixture::capabilities()], $session),
            message: $message,
        ));
        self::assertSame(EgressOutcome::Succeeded, $result->operationResult()->receipt()->outcome());
        self::assertSame(0, $requestBody->calls);
        self::assertSame(0, $responseBody->calls);
    }
}

final class RuntimeBodyProbe implements StreamInterface
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
