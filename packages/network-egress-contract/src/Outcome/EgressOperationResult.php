<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Psr\Http\Message\ResponseInterface;

/** Carries one terminal receipt and only the response or failure shape it permits. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressOperationResult
{
    public function __construct(
        private EgressReceipt $receipt,
        private ?ResponseInterface $response,
        private ?EgressFailure $failure,
    ) {
        $success = EgressOutcome::Succeeded === $receipt->outcome();
        if (
            ($success && (null === $response || null !== $failure))
            || (!$success && (null !== $response || null === $failure))
        ) {
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt('result_shape', $receipt->outcome()->value);
        }
    }

    public function receipt(): EgressReceipt
    {
        return $this->receipt;
    }

    public function response(): ?ResponseInterface
    {
        return $this->response;
    }

    public function failure(): ?EgressFailure
    {
        return $this->failure;
    }
}
