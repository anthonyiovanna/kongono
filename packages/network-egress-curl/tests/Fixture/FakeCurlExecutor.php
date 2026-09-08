<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Fixture;

use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Http\Message\Message\Stream;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Policy\EgressPolicyReference;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressCurl\Internal\CurlExecutionSnapshot;
use Kongono\NetworkEgressCurl\Internal\CurlExecutor;
use Kongono\NetworkEgressCurl\Profile\CurlTlsTrust;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use LogicException;
use Psr\Http\Message\StreamInterface;
use Throwable;

final class FakeCurlExecutor implements CurlExecutor
{
    public int $executions = 0;
    public int $closes = 0;

    /** @param list<CurlExecutionSnapshot> $snapshots */
    public function __construct(
        private array $snapshots,
        private readonly ?Throwable $closeFailure = null,
    ) {}

    public function execute(): CurlExecutionSnapshot
    {
        ++$this->executions;
        $snapshot = array_shift($this->snapshots);
        if (!$snapshot instanceof CurlExecutionSnapshot) {
            throw new LogicException('No fake cURL snapshot remains.');
        }

        return $snapshot;
    }

    public function close(): void
    {
        ++$this->closes;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }

    public static function profile(bool $allowPlainHttp = false): CurlTransportProfile
    {
        return new CurlTransportProfile(new CurlTlsTrust(self::pem()), $allowPlainHttp);
    }

    public static function pem(): string
    {
        return "-----BEGIN CERTIFICATE-----\nQUJD\n-----END CERTIFICATE-----\n";
    }

    public static function budget(
        int $requestBytes = 4096,
        int $responseBytes = 4096,
        int $headerCount = 32,
        int $headerBytes = 4096,
        int $bufferedBytes = 4096,
    ): EgressBudget {
        return new EgressBudget(1, 0, 1, $requestBytes, $responseBytes, $headerCount, $headerBytes, $bufferedBytes, 4096, 1000, 1000, 5000);
    }

    /** @param array<string, string|list<string>> $headers */
    public static function request(
        string $uri = 'https://example.test/resource',
        ?StreamInterface $body = null,
        array $headers = [],
        ?EgressBudget $budget = null,
        string $address = '192.0.2.1',
    ): EgressTransportRequest {
        $scheme = str_starts_with($uri, 'http://') ? OriginScheme::Http : OriginScheme::Https;
        $port = OriginScheme::Http === $scheme ? 80 : 443;
        $origin = new CanonicalOrigin($scheme, 'example.test', $port);
        $message = new ServerRequest('GET', $uri, [], $headers, $body);
        $operation = new EgressOperation(
            new EgressOperationIdentity('curl-operation'),
            $message,
            $origin,
            new EgressAuthorityReference('curl-authority', 1),
            new EgressPolicyReference('curl-policy', str_repeat('A', 64)),
            $budget ?? self::budget(),
        );

        return new EgressTransportRequest($operation, new ResolvedAddress($address), new EgressAttemptIdentity('curl-attempt'));
    }

    public static function stream(string $contents, int $position = 0): StreamInterface
    {
        $resource = fopen('php://memory', 'w+b');
        if (false === $resource) {
            throw new LogicException('Unable to create in-memory test stream.');
        }
        fwrite($resource, $contents);
        fseek($resource, $position);

        return new Stream($resource);
    }

    public static function snapshot(): CurlExecutionSnapshot
    {
        return new CurlExecutionSnapshot(new EgressTransportEmission(
            new EgressTransferEvidence(EgressTransferState::ResponseReceived, 10, 20),
            NegotiatedProtocol::Http11,
            0,
            0,
            new Response(),
            null,
        ), 0);
    }
}
