<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Receives borrowed material explicitly; implementations may still copy it. */
#[Api(ApiStatus::Experimental)]
interface CredentialMaterialConsumer
{
    public function consume(#[\SensitiveParameter] string $material): void;
}
