<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Diagnostic\AssetBuildDiagnostic;
use Throwable;

/** Reports one invalid complete dependency graph. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetGraphException extends AssetBuildException
{
    public static function invalid(
        string $violation,
        ?int $entryCount = null,
        ?int $depth = null,
        ?int $limit = null,
        ?string $dependencyKind = null,
        ?Throwable $previous = null,
    ): self {
        $safe = in_array($violation, ['missing_entry_point', 'unreachable_declaration', 'dependency_cycle', 'dependency_depth', 'graph_relation'], true) ? $violation : 'invalid_graph';
        $context = ['violation' => $safe];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $depth) {
            $context['depth'] = max(0, $depth);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $dependencyKind) {
            $context['dependency_kind'] = in_array($dependencyKind, ['static', 'dynamic', 'resource'], true) ? $dependencyKind : 'unsupported';
        }

        return new self(AssetBuildDiagnostic::InvalidGraph, $context, $previous);
    }
}
