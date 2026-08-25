<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic;

/** Reports a generated assembly outside the exact data-operation root profile. */
#[Api(ApiStatus::Public)]
final class InvalidGeneratedDataProfileException extends DatabaseApplicationException
{
    public function __construct(string $failureCategory = 'invalid_profile')
    {
        parent::__construct(DatabaseApplicationDiagnostic::InvalidGeneratedProfile, $failureCategory);
    }
}
