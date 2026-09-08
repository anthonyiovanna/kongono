<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Diagnostic\NetworkEgressPolicyBuildDiagnostic;

/** Reports one bounded typed policy identity source or compatibility failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidEgressPolicySourceException extends NetworkEgressPolicyBuildException
{
    public static function invalidIdentity(): self
    {
        return new self(NetworkEgressPolicyBuildDiagnostic::InvalidPolicyIdentity, ['violation' => 'identity_grammar']);
    }

    public static function invalidSource(
        string $violation,
        ?string $field = null,
        ?int $count = null,
        ?int $position = null,
        ?int $limit = null,
    ): self {
        return new self(NetworkEgressPolicyBuildDiagnostic::InvalidPolicySource, self::context($violation, $field, $count, $position, $limit));
    }

    public static function incompatible(string $violation, string $field): self
    {
        return new self(NetworkEgressPolicyBuildDiagnostic::PolicyCompatibilityFailure, self::context($violation, $field));
    }

    /** @return array<string, string|int|bool> */
    private static function context(string $violation, ?string $field, ?int $count = null, ?int $position = null, ?int $limit = null): array
    {
        $violations = [
            'origins_not_list', 'origin_count', 'invalid_origin_entry', 'duplicate_origin', 'noncanonical_origin_order',
            'http_not_allowed', 'tls_capability_required', 'redirect_budget_mismatch', 'proxy_capability_required',
        ];
        $fields = ['allowed_origins', 'transport_security', 'redirect_mode', 'required_capabilities'];
        $context = ['violation' => in_array($violation, $violations, true) ? $violation : 'unsupported_violation'];
        if (null !== $field) {
            $context['field'] = in_array($field, $fields, true) ? $field : 'unsupported_field';
        }
        if (null !== $count) {
            $context['entry_count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['entry_position'] = max(0, $position);
        }
        if (null !== $limit) {
            $context['numeric_limit'] = max(0, $limit);
        }

        return $context;
    }
}
