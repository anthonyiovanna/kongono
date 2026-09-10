<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;

/** Retains one explicit exact-current profile and optional detached completed result. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceInspectionRequest
{
    public function __construct(
        private ExternalServiceApplicationProfile $profile,
        private ?ExternalServiceApplicationResult $result = null,
    ) {}

    public function profile(): ExternalServiceApplicationProfile
    {
        return $this->profile;
    }

    public function result(): ?ExternalServiceApplicationResult
    {
        return $this->result;
    }
}
