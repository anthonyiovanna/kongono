<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Execution;

use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOperationResult;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\Http\Message\Message\Response;
use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
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
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use PHPUnit\Framework\TestCase;

final class ExternalServiceApplicationResultTest extends TestCase
{
    public function testExactSuccessfulNestedResultsAreRetained(): void
    {
        [$operationResult, $credentialResult, $egressResult] = $this->results();
        $result = new ExternalServiceApplicationResult($operationResult, $credentialResult, $egressResult);

        self::assertSame($operationResult, $result->operationResult());
        self::assertSame($credentialResult, $result->credentialResult());
        self::assertSame($egressResult, $result->egressResult());
    }

    public function testMismatchedOperationIdentityFailsClosed(): void
    {
        [, $credentialResult, $egressResult] = $this->results();
        $mismatch = new ExternalServiceOperationResult(
            new ExternalServiceOperationIdentity('operation.other'),
            new ServiceBindingIdentity('binding.primary'),
            ExternalServiceLifecycleState::Retired,
            ExternalServiceOutcome::Succeeded,
            null,
        );

        $this->expectException(ExternalServiceApplicationOperationException::class);
        new ExternalServiceApplicationResult($mismatch, $credentialResult, $egressResult);
    }

    /** @return array{ExternalServiceOperationResult, CredentialLeaseRuntimeResult, EgressRuntimeResult} */
    private function results(): array
    {
        $receipt = new EgressReceipt(
            new EgressReceiptIdentity('receipt.external-service'),
            new EgressOperationIdentity('operation.external-service'),
            new EgressAttemptIdentity('attempt.external-service'),
            EgressLifecycleState::Succeeded,
            EgressOutcome::Succeeded,
            1,
            new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
            new CancellationEvidence(CancellationState::NotRequested, 1),
            NegotiatedProtocol::Http11,
            new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]),
        );
        $egressResult = new EgressRuntimeResult(new EgressOperationResult($receipt, new Response(), null), 1, 1, 1, 0, 0);
        $credentialResult = new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::Succeeded,
            CredentialLeaseRuntimeState::Retired,
            null,
            1,
            1,
            1,
            1,
            true,
        );
        $operationResult = new ExternalServiceOperationResult(
            new ExternalServiceOperationIdentity('operation.external-service'),
            new ServiceBindingIdentity('binding.primary'),
            ExternalServiceLifecycleState::Retired,
            ExternalServiceOutcome::Succeeded,
            null,
        );

        return [$operationResult, $credentialResult, $egressResult];
    }
}
