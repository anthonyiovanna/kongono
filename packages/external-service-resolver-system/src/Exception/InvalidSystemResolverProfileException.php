<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Diagnostic\SystemResolverDiagnostic;

/** Reports one closed resolver-profile defect without retaining caller content. */
#[Api(ApiStatus::Experimental)]
final class InvalidSystemResolverProfileException extends ExternalServiceResolverSystemException
{
    public static function candidateLimit(int $count): self
    {
        return new self(SystemResolverDiagnostic::InvalidResolverProfile, [
            'stage' => 'profile',
            'violation' => 'candidate_limit',
            'count' => $count,
            'limit' => 64,
        ]);
    }
}
