<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Exception\SystemResolverEnvironmentException;

/** Validates the fixed sockets capability profile without changing configuration. */
#[Api(ApiStatus::Internal)]
final class SystemResolverEnvironment
{
    private const array REQUIRED_CONSTANTS = [
        'AF_INET', 'AF_INET6', 'SOCK_STREAM', 'SOL_TCP', 'AI_NUMERICHOST', 'AI_NUMERICSERV',
    ];

    public static function assertSupported(): void
    {
        if (!extension_loaded('sockets') || !function_exists('socket_addrinfo_lookup') || !function_exists('socket_addrinfo_explain')) {
            throw SystemResolverEnvironmentException::unavailable();
        }
        foreach (self::REQUIRED_CONSTANTS as $constant) {
            if (!defined($constant)) {
                throw SystemResolverEnvironmentException::unavailable();
            }
        }
        if (!self::isPositiveInteger(constant('SOL_TCP'))) {
            throw SystemResolverEnvironmentException::unavailable();
        }
    }

    private static function isPositiveInteger(mixed $value): bool
    {
        return is_int($value) && $value > 0;
    }

    private function __construct() {}
}
