<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports structural, binding, reflection, request, or lifetime plan failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidExtensionGrantPlanException extends ExtensionCompositionException
{
    private const array KINDS = [
        'grant_plan_identity_grammar', 'canonical_json_failed', 'implementation_not_list', 'implementation_count',
        'invalid_implementation_entry', 'duplicate_implementation', 'noncanonical_implementation_order',
        'decision_not_list', 'decision_count', 'invalid_decision_entry', 'duplicate_decision',
        'noncanonical_decision_order', 'duplicate_constructor_parameter', 'constructor_parameter_grammar',
        'symbol_grammar', 'declaration_set_mismatch', 'admission_identity_mismatch', 'admission_order',
        'implementation_missing', 'implementation_extra', 'implementation_version_mismatch', 'declaration_missing',
        'unsupported_extension_category', 'decision_missing', 'decision_extra', 'decision_extension_version',
        'decision_purpose', 'decision_requirement', 'required_capability_denied', 'application_reaches_operation_provider',
        'implementation_not_found', 'implementation_symbol_case', 'implementation_not_instantiable',
        'implementation_constructor', 'constructor_parameter_missing', 'constructor_parameter_extra',
        'constructor_parameter_shape', 'provider_not_found', 'provider_symbol_case', 'provider_contract_shape',
        'provider_contract_mismatch', 'denied_parameter_required', 'invalid_provider_lifetime', 'generated_bytes_limit',
    ];

    public static function invalid(string $kind, ?int $count = null, ?int $position = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => in_array($kind, self::KINDS, true) ? $kind : 'unsupported_violation'];
        if (null !== $count) {
            $context['entry_count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['entry_position'] = max(0, $position);
        }

        return new self($context, $previous);
    }
}
