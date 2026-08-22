<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;

/** Derives the current-delivery permission from exact adapter-owned CGI evidence. */
#[Api(ApiStatus::Internal)]
final class CgiTrustedPresentationPermissionEvaluator
{
    private function __construct() {}

    /** @param array<array-key, mixed> $server */
    public static function evaluate(
        string $sapi,
        array $server,
    ): AdapterControlledDeliveryPermission {
        if ('cgi-fcgi' !== $sapi) {
            return AdapterControlledDeliveryPermission::Denied;
        }

        foreach ($server as $name => $_value) {
            if (!is_string($name)) {
                return AdapterControlledDeliveryPermission::Denied;
            }

            if (
                'HTTP_FORWARDED' === $name
                || 'HTTP_X_FORWARDED' === $name
                || str_starts_with($name, 'HTTP_X_FORWARDED_')
            ) {
                return AdapterControlledDeliveryPermission::Denied;
            }
        }

        if (
            'CGI/1.1' !== ($server['GATEWAY_INTERFACE'] ?? null)
            || 'RESPONDER' !== ($server['FCGI_ROLE'] ?? null)
            || !in_array($server['REMOTE_ADDR'] ?? null, ['127.0.0.1', '::1'], true)
        ) {
            return AdapterControlledDeliveryPermission::Denied;
        }

        return AdapterControlledDeliveryPermission::Allowed;
    }
}
