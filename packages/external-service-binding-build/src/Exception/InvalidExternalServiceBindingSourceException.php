<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;
use Throwable;

/** Reports a bounded source identity list ordering or uniqueness violation. */
#[Api(ApiStatus::Experimental)]
final class InvalidExternalServiceBindingSourceException extends ExternalServiceBindingBuildException
{
    public static function invalid(string $violation, ?string $field = null, ?int $count = null, ?int $position = null, ?int $limit = null, ?Throwable $previous = null): self
    {
        $violations = ['identity_grammar', 'bindings_not_list', 'binding_count', 'invalid_binding_entry', 'duplicate_binding_identity', 'duplicate_profile_identity', 'noncanonical_binding_order'];
        $fields = ['source_identity', 'bindings'];
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
            $context['limit'] = max(0, $limit);
        }

        return new self(ExternalServiceBindingBuildDiagnostic::InvalidSource, $context, $previous);
    }
}
