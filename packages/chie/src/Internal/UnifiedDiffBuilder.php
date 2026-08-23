<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieProposalException;

/** Builds one deterministic Git-style unified hunk with exactly three available context lines. */
#[Api(ApiStatus::Internal)]
final class UnifiedDiffBuilder
{
    /**
     * @param list<string> $sourceLines
     * @param list<string> $beforeLines
     * @param list<string> $afterLines
     */
    public function build(string $path, array $sourceLines, int $matchIndex, array $beforeLines, array $afterLines): string
    {
        $leadingCount = min(3, $matchIndex);
        $followingStart = $matchIndex + count($beforeLines);
        $followingCount = min(3, count($sourceLines) - $followingStart);
        $leading = array_slice($sourceLines, $matchIndex - $leadingCount, $leadingCount);
        $following = array_slice($sourceLines, $followingStart, $followingCount);
        $oldStart = $matchIndex - $leadingCount + 1;
        $newStart = $oldStart;
        $oldCount = $leadingCount + count($beforeLines) + $followingCount;
        $newCount = $leadingCount + count($afterLines) + $followingCount;

        $diff = "--- a/{$path}\n+++ b/{$path}\n@@ -{$oldStart},{$oldCount} +{$newStart},{$newCount} @@\n";
        foreach ($leading as $line) {
            $diff .= ' ' . $line . "\n";
        }
        foreach ($beforeLines as $line) {
            $diff .= '-' . $line . "\n";
        }
        foreach ($afterLines as $line) {
            $diff .= '+' . $line . "\n";
        }
        foreach ($following as $line) {
            $diff .= ' ' . $line . "\n";
        }
        if (strlen($diff) > 65536) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalDiffLimitExceeded, 'diff-limit');
        }

        return $diff;
    }
}
