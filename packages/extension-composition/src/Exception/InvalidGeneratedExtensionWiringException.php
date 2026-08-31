<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports bounded non-executing generated-byte or exact-current validation failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedExtensionWiringException extends ExtensionCompositionException
{
    public static function invalid(string $kind, ?Throwable $previous = null): self
    {
        $allowed = ['generated_bytes', 'generated_header', 'metadata_json', 'metadata_shape', 'metadata_identity', 'metadata_count', 'generator_identity', 'php_syntax', 'generated_classes', 'forbidden_construct', 'canonical_whitespace', 'current_input_invalid', 'current_state_mismatch'];

        return new self(['violation' => in_array($kind, $allowed, true) ? $kind : 'unsupported_violation'], $previous);
    }
}
