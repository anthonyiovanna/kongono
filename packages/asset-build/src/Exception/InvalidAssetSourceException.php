<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Diagnostic\AssetBuildDiagnostic;
use Throwable;

/** Reports one invalid source or complete source-set relation. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetSourceException extends AssetBuildException
{
    public static function invalid(
        string $violation,
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?int $limit = null,
        ?Throwable $previous = null,
    ): self {
        $context = ['violation' => self::safe($violation)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }

        return new self(AssetBuildDiagnostic::InvalidSource, $context, $previous);
    }

    private static function safe(string $value): string
    {
        return in_array($value, ['source_path_grammar', 'sources_not_list', 'invalid_source_entry', 'source_count', 'aggregate_bytes', 'duplicate_logical_identity', 'duplicate_source_path', 'missing_source', 'extra_source', 'identity_relation', 'content_relation'], true) ? $value : 'invalid_source';
    }
}
