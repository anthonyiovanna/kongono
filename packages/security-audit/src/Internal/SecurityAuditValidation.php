<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;

/** Centralizes the closed security-audit field grammar. */
#[Api(ApiStatus::Internal)]
final class SecurityAuditValidation
{
    /** @var list<string> */
    private const array DENIED_TOKENS = [
        'credential', 'credentials', 'cookie', 'cookies', 'csrf', 'password',
        'passwords', 'secret', 'secrets', 'session', 'sessions', 'stack',
        'token', 'tokens', 'url', 'urls',
    ];

    private function __construct() {}

    public static function isFieldKeyAllowed(string $key): bool
    {
        if (str_starts_with($key, 'event.') || str_starts_with($key, 'audit.')) {
            return false;
        }

        $tokens = preg_split('/[._-]/', $key);
        if (false === $tokens || [] !== array_intersect($tokens, self::DENIED_TOKENS)) {
            return false;
        }

        foreach (['database.body', 'request.body', 'raw.header', 'policy.body', 'query.string'] as $denied) {
            if ($key === $denied || str_contains($key, '.' . $denied . '.') || str_starts_with($key, $denied . '.') || str_ends_with($key, '.' . $denied)) {
                return false;
            }
        }

        return true;
    }

    public static function isDomainValueAllowed(AttributeValue $value, AttributeValueKind $kind): bool
    {
        return $value->kind() === $kind
            && ($kind !== AttributeValueKind::Text || strlen((string) $value->value()) <= 128);
    }
}
