<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Diagnostic\ViewBuildDiagnostic;
use Throwable;

/** Reports invalid, unsupported, or over-bound explicit source evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidViewSourceException extends ViewBuildException
{
    private const array INVALID = ['source_framing', 'source_json', 'source_shape', 'source_identity_grammar', 'source_pointer_grammar', 'duplicate_view', 'schema_shape', 'field_shape', 'duplicate_field', 'node_shape', 'attribute_shape', 'duplicate_attribute', 'reference_shape'];
    private const array UNSUPPORTED = ['schema_kind', 'context_root', 'node_kind', 'element_name', 'attribute_name', 'attribute_value_kind', 'reference_target', 'reference_required', 'reference_value_kind', 'loop_target', 'loop_alias', 'loop_shadowing'];
    private const array BOUNDS = ['source_bytes', 'view_count', 'schema_depth', 'schema_nodes', 'field_count', 'string_bytes', 'list_items', 'body_count', 'node_count', 'node_depth', 'loop_depth', 'attribute_count', 'static_bytes', 'reference_bytes', 'reference_segments', 'model_bytes', 'generated_bytes', 'artifact_bytes'];

    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(ViewBuildDiagnostic::InvalidSource, ['violation' => self::closed($violation, self::INVALID)], $previous);
    }
    public static function unsupported(string $violation, ?string $nodeKind = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::closed($violation, self::UNSUPPORTED)];
        if (null !== $nodeKind) {
            $context['node_kind'] = in_array($nodeKind, ['text', 'value', 'element', 'each'], true) ? $nodeKind : 'unsupported';
        }

        return new self(ViewBuildDiagnostic::UnsupportedGrammar, $context, $previous);
    }
    public static function bound(string $violation, int $limit, ?int $entryCount = null, ?int $depth = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::closed($violation, self::BOUNDS), 'limit' => max(0, $limit)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $depth) {
            $context['depth'] = max(0, $depth);
        }

        return new self(ViewBuildDiagnostic::BoundExceeded, $context, $previous);
    }

    /** @param list<string> $allowed */
    private static function closed(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : 'unsupported_violation';
    }
}
