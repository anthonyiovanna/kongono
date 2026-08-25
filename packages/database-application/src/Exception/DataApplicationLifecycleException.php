<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic;

/** Reports wrapper use after its generated lifecycle boundary retired. */
#[Api(ApiStatus::Public)]
final class DataApplicationLifecycleException extends DatabaseApplicationException
{
    public function __construct(string $failureCategory = 'boundary_retired')
    {
        parent::__construct(DatabaseApplicationDiagnostic::LifecycleFailed, $failureCategory);
    }
}
