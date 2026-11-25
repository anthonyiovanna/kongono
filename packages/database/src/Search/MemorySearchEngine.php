<?php

declare(strict_types=1);

namespace Kongono\Database\Search;

/**
 * Deterministic zero-dependency in-memory full-text search engine for unit tests and small apps.
 * Weights title matches higher than body matches and supports exact metadata filtering.
 */
final class MemorySearchEngine implements SearchEngineInterface
{
    /** @var array<string, array{title: string, body: string, metadata: array<string, mixed>}> */
    private array $documents = [];

    public function index(string $id, string $title, string $body, array $metadata = []): void
    {
        $this->documents[$id] = [
            'title' => $title,
            'body' => $body,
            'metadata' => $metadata,
        ];
    }

    public function remove(string $id): void
    {
        unset($this->documents[$id]);
    }

    public function search(string $query, int $limit = 10, array $metadataFilter = []): array
    {
        $terms = self::tokenize($query);
        if ([] === $terms) {
            return [];
        }

        $phrase = mb_strtolower(trim($query), 'UTF-8');
        $hits = [];

        foreach ($this->documents as $id => $doc) {
            if (!self::matchesFilter($doc['metadata'], $metadataFilter)) {
                continue;
            }

            $titleLower = mb_strtolower($doc['title'], 'UTF-8');
            $bodyLower = mb_strtolower($doc['body'], 'UTF-8');
            $score = 0.0;

            foreach ($terms as $term) {
                $titleCount = substr_count($titleLower, $term);
                $bodyCount = substr_count($bodyLower, $term);
                $score += ($titleCount * 2.5) + ($bodyCount * 1.0);
            }

            if ($score <= 0.0) {
                continue;
            }

            if ('' !== $phrase && (str_contains($titleLower, $phrase) || str_contains($bodyLower, $phrase))) {
                $score += 1.5;
            }

            $snippet = self::excerpt($doc['body'], $terms[0]);
            $hits[] = new TextSearchHit(
                id: $id,
                title: $doc['title'],
                score: round($score, 4),
                snippet: $snippet,
                metadata: $doc['metadata'],
            );
        }

        usort($hits, static fn(TextSearchHit $a, TextSearchHit $b): int => $b->score <=> $a->score);

        return array_slice($hits, 0, min(1000, max(1, $limit)));
    }

    /** @return list<string> */
    private static function tokenize(string $text): array
    {
        $clean = mb_strtolower(trim($text), 'UTF-8');
        if ('' === $clean) {
            return [];
        }
        $raw = preg_split('/[^\p{L}\p{N}_-]+/u', $clean);
        if (!is_array($raw)) {
            return [];
        }

        $tokens = [];
        foreach ($raw as $part) {
            if ('' !== $part && !in_array($part, $tokens, true)) {
                $tokens[] = $part;
            }
        }

        return $tokens;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $filter
     */
    private static function matchesFilter(array $metadata, array $filter): bool
    {
        foreach ($filter as $key => $expected) {
            if (!array_key_exists($key, $metadata) || $metadata[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }

    private static function excerpt(string $body, string $firstTerm, int $maxLength = 160): string
    {
        $trimmed = trim((string) preg_replace('/\s+/u', ' ', $body));
        $totalLen = mb_strlen($trimmed, 'UTF-8');
        if ($totalLen <= $maxLength) {
            return $trimmed;
        }
        $pos = mb_stripos($trimmed, $firstTerm, 0, 'UTF-8');
        $start = false !== $pos ? max(0, $pos - 40) : 0;
        $slice = mb_substr($trimmed, $start, $maxLength, 'UTF-8');

        return ($start > 0 ? '...' : '') . trim($slice) . ($start + $maxLength < $totalLen ? '...' : '');
    }
}
