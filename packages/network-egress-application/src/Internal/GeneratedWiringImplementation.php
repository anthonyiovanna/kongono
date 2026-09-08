<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the exact fixed Phase 15F generated boundary implementation. */
#[Api(ApiStatus::Internal)]
final class GeneratedWiringImplementation
{
    private const string CONTRACT = 'kongono.network-egress-application.generated-wiring/v1;strict-php;single-profile;direct-resolver;direct-transport;single-terminal-operation;no-effects-on-load';

    public static function sha256(): string
    {
        return hash('sha256', self::CONTRACT);
    }

    private function __construct() {}
}
