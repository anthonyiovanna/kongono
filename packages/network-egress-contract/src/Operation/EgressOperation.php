<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Policy\EgressPolicyReference;
use Psr\Http\Message\RequestInterface;

/** Retains one exact request and inert control-plane evidence without performing I/O. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressOperation
{
    public function __construct(
        private EgressOperationIdentity $identity,
        private RequestInterface $request,
        private CanonicalOrigin $origin,
        private EgressAuthorityReference $authorityReference,
        private EgressPolicyReference $policyReference,
        private EgressBudget $budget,
    ) {
        $requestOrigin = CanonicalOrigin::fromUri($request->getUri());
        if (!$origin->equals($requestOrigin)) {
            throw InvalidNetworkEgressContractException::invalidOperation('request_origin_mismatch');
        }
    }

    public function identity(): EgressOperationIdentity
    {
        return $this->identity;
    }

    public function request(): RequestInterface
    {
        return $this->request;
    }

    public function origin(): CanonicalOrigin
    {
        return $this->origin;
    }

    public function authorityReference(): EgressAuthorityReference
    {
        return $this->authorityReference;
    }

    public function policyReference(): EgressPolicyReference
    {
        return $this->policyReference;
    }

    public function budget(): EgressBudget
    {
        return $this->budget;
    }
}
