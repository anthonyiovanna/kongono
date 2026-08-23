<?php

declare(strict_types=1);

namespace Kongono\Oshie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\InvalidArtifactException;

/** Extracts one exact bounded level-two section with fenced content kept inert. */
#[Api(ApiStatus::Internal)]
final class SectionExtractor
{
    public const MAX_SECTION_BYTES = 32768;

    /** @return array{text: string, line_start: int, line_end: int} */
    public function extract(KnowledgeSource $source, string $heading): array
    {
        $lines = explode("\n", $source->content());
        if ('' === $lines[count($lines) - 1]) {
            array_pop($lines);
        }
        $outsideFence = [];
        $inFence = false;
        $fenceByte = null;
        foreach ($lines as $index => $line) {
            $outsideFence[$index] = !$inFence;
            if (1 === preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $match)) {
                $marker = $match[1][0];
                if (!$inFence) {
                    $inFence = true;
                    $fenceByte = $marker;
                } elseif ($marker === $fenceByte) {
                    $inFence = false;
                    $fenceByte = null;
                }
            }
        }
        $matches = [];
        foreach ($lines as $index => $line) {
            if ($outsideFence[$index] && $line === $heading) {
                $matches[] = $index;
            }
        }
        if (1 !== count($matches)) {
            throw new InvalidArtifactException(OshieDiagnostic::SourceSectionInvalid, [] === $matches ? 'heading-missing' : 'heading-duplicate');
        }
        $startLine = $matches[0];
        $startOffset = 0;
        for ($index = 0; $index < $startLine; ++$index) {
            $startOffset += strlen($lines[$index]) + 1;
        }
        $endLineExclusive = count($lines);
        for ($index = $startLine + 1; $index < count($lines); ++$index) {
            if ($outsideFence[$index] && str_starts_with($lines[$index], '## ')) {
                $endLineExclusive = $index;
                break;
            }
        }
        $endOffset = $startOffset;
        for ($index = $startLine; $index < $endLineExclusive; ++$index) {
            $endOffset += strlen($lines[$index]) + 1;
        }
        $text = substr($source->content(), $startOffset, $endOffset - $startOffset);
        if (strlen($text) > self::MAX_SECTION_BYTES) {
            throw new InvalidArtifactException(OshieDiagnostic::SourceSectionInvalid, 'section-limit');
        }
        return ['text' => $text, 'line_start' => $startLine + 1, 'line_end' => $endLineExclusive];
    }
}
