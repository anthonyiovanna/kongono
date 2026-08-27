<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\AuthorizationDecision;

/** Couples one authorization decision inseparably to its bounded receipt. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthorizationEvaluation
{
    public function __construct(private AuthorizationDecisionReceipt $receipt) {}

    public function decision(): AuthorizationDecision
    {
        return $this->receipt->decision();
    }

    public function receipt(): AuthorizationDecisionReceipt
    {
        return $this->receipt;
    }
}
