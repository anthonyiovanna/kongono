<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use CurlHandle;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressCurl\Exception\CurlTransportOperationException;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use SensitiveParameter;
use Throwable;

/** Owns one fresh easy handle and performs at most one synchronous execution. */
#[Api(ApiStatus::Internal)]
final class NativeCurlExecutor implements CurlExecutor
{
    private ?CurlHandle $handle;
    private bool $executed = false;
    private readonly CurlResponseAccumulator $accumulator;

    public function __construct(
        #[SensitiveParameter]
        private readonly EgressTransportRequest $request,
        #[SensitiveParameter]
        private readonly CurlTransportProfile $profile,
        private readonly CurlRequestBodyReader $bodyReader,
    ) {
        $handle = curl_init();
        if (false === $handle) {
            throw InvalidCurlTransportProfileException::environment();
        }
        $this->handle = $handle;
        $this->accumulator = new CurlResponseAccumulator($request->operation()->budget());
    }

    public function execute(): CurlExecutionSnapshot
    {
        if ($this->executed || null === $this->handle) {
            throw InvalidCurlTransportProfileException::lifecycle('reentrancy');
        }
        $this->executed = true;
        $options = (new CurlOptionBuilder())->build($this->request, $this->profile, $this->bodyReader, $this->accumulator);
        try {
            if (!curl_setopt_array($this->handle, $options)) {
                throw InvalidCurlTransportProfileException::environment();
            }
            $result = curl_exec($this->handle);
            $curlCode = max(0, min(999, curl_errno($this->handle)));
            $requestHeadBytes = curl_getinfo($this->handle, CURLINFO_REQUEST_SIZE);
            $nativeProtocol = curl_getinfo($this->handle, CURLINFO_HTTP_VERSION);
        } catch (Throwable $throwable) {
            throw CurlTransportOperationException::transfer(EgressFailureKind::UncertainEffect, 0, $throwable);
        }

        $requestBytes = max(0, $requestHeadBytes) + $this->bodyReader->consumedBytes();
        $requestBytes = min(1_073_741_824, $requestBytes);
        $responseBytes = min(1_073_741_824, $this->accumulator->responseBytes());
        $protocol = (new CurlProtocolMapper())->map($nativeProtocol);
        $violation = $this->accumulator->violation();

        if (false === $result || 0 !== $curlCode || null !== $violation || NegotiatedProtocol::Http11 !== $protocol) {
            $failure = (new CurlFailureMapper())->map($curlCode, $violation, $requestBytes);
            $transfer = (new CurlTransferEvidenceBuilder())->build($requestBytes, $responseBytes, false);

            return new CurlExecutionSnapshot(new EgressTransportEmission(
                $transfer,
                $protocol,
                $this->accumulator->bufferedBytes(),
                $this->accumulator->retainedEvidenceBytes(),
                null,
                $failure,
            ), $curlCode);
        }

        try {
            $block = $this->accumulator->headerBlock();
            $response = (new CurlResponseBuilder())->build($block, $this->accumulator);
        } catch (InvalidCurlTransportProfileException) {
            $failure = (new CurlFailureMapper())->map(0, 'response_shape', $requestBytes);
            $transfer = (new CurlTransferEvidenceBuilder())->build($requestBytes, $responseBytes, false);

            return new CurlExecutionSnapshot(new EgressTransportEmission(
                $transfer,
                $protocol,
                $this->accumulator->bufferedBytes(),
                $this->accumulator->retainedEvidenceBytes(),
                null,
                $failure,
            ), 0);
        } catch (Throwable $throwable) {
            throw CurlTransportOperationException::response($throwable);
        }

        $transfer = (new CurlTransferEvidenceBuilder())->build($requestBytes, $responseBytes, true);

        return new CurlExecutionSnapshot(new EgressTransportEmission(
            $transfer,
            NegotiatedProtocol::Http11,
            $this->accumulator->bufferedBytes(),
            $this->accumulator->retainedEvidenceBytes(),
            $response,
            null,
        ), 0);
    }

    public function close(): void
    {
        $this->accumulator->close();
        $this->handle = null;
    }
}
