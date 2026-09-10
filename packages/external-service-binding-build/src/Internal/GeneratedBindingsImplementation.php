<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the fixed reviewed Phase 16C secret-free PHP emitter contract. */
#[Api(ApiStatus::Internal)]
final class GeneratedBindingsImplementation
{
    private const string CONTRACT = 'kongono.external-service-bindings.generated-php/v1;strict-php;secret-free;fixed-value-constructors;inert-bytes;no-load';

    public static function sha256(): string
    {
        return hash('sha256', self::CONTRACT);
    }

    private function __construct() {}
}
