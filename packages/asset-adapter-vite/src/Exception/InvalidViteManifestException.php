<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Diagnostic\ViteAdapterDiagnostic;
use Throwable;

/** Reports closed manifest or translation failures without producer disclosure. */
#[Api(ApiStatus::Experimental)]final class InvalidViteManifestException extends ViteAdapterException
{
    private const array ALLOWED = ['framing','json','root_shape','entry_count','entry_shape','unknown_member','string_grammar','boolean_type','list_shape','duplicate_list','unsafe_path','unresolved_reference','entry_relation','source_relation','output_type','output_graph'];
    public static function invalid(string $violation, ?int $entryCount = null, ?int $limit = null, ?Throwable $previous = null): self
    {
        $values = ['violation' => in_array($violation, self::ALLOWED, true) ? $violation : 'unsupported_violation'];
        if (null !== $entryCount) {
            $values['entry_count'] = max(0, $entryCount);
        }if (null !== $limit) {
            $values['limit'] = max(0, $limit);
        }return new self(ViteAdapterDiagnostic::InvalidManifest, $values, $previous);
    }
}
