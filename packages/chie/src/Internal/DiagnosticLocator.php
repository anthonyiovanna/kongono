<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Source\KnowledgeSource;

/** Resolves literal diagnostic occurrences in artifact and line order only. */
#[Api(ApiStatus::Internal)]
final class DiagnosticLocator
{
    public const MAX_LINE_BYTES = 4096;
    public const MAX_OCCURRENCES = 8;

    /** @return list<array{source: KnowledgeSource, line: string, line_number: int}> */
    public function locate(FrameworkKnowledgeArtifact $artifact, string $identifier): array
    {
        $occurrences = [];
        foreach ($artifact->sources() as $source) {
            $lines = explode("\n", $source->content());
            if ('' === $lines[count($lines) - 1]) {
                array_pop($lines);
            }
            foreach ($lines as $index => $line) {
                if (!$this->containsExactIdentifier($line, $identifier)) {
                    continue;
                }
                if (strlen($line) > self::MAX_LINE_BYTES) {
                    throw new InvalidArtifactException(ChieDiagnostic::ResponseLimitExceeded, 'line-limit');
                }
                $occurrences[] = ['source' => $source, 'line' => $line, 'line_number' => $index + 1];
                if (count($occurrences) > self::MAX_OCCURRENCES) {
                    throw new InvalidArtifactException(ChieDiagnostic::ResponseLimitExceeded, 'occurrence-limit');
                }
            }
        }

        return $occurrences;
    }

    private function containsExactIdentifier(string $line, string $identifier): bool
    {
        $offset = 0;
        while (false !== $position = strpos($line, $identifier, $offset)) {
            $before = 0 === $position ? null : $line[$position - 1];
            $afterPosition = $position + strlen($identifier);
            $after = $afterPosition >= strlen($line) ? null : $line[$afterPosition];
            if ((null === $before || !$this->isIdentifierByte($before)) && (null === $after || !$this->isIdentifierByte($after))) {
                return true;
            }
            $offset = $position + 1;
        }

        return false;
    }

    private function isIdentifierByte(string $byte): bool
    {
        $value = ord($byte);

        return 45 === $value || ($value >= 48 && $value <= 57) || ($value >= 65 && $value <= 90);
    }
}
