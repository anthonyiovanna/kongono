<?php

declare(strict_types=1);

namespace Kongono\Cli\Knowledge;

use Kongono\Cli\Skills\SkillsInstaller;

/**
 * Deterministic, zero-dependency, version-matched framework knowledge retriever for Kongono.
 *
 * Indexes the installed framework's canonical `site/llms-full.txt` sections and bundled
 * `skills/<name>/SKILL.md` workflow recipes so Oshie, Chie, Hantei, MCP clients, and local
 * Ollama models retrieve exact Kongono v1.20+ APIs without hallucinating Laravel/Symfony code.
 */
final readonly class KongonoKnowledgeIndex
{
    /** @var list<string> */
    private const array STOPWORDS = [
        'the', 'and', 'for', 'with', 'that', 'this', 'from', 'into', 'how', 'does', 'what',
        'use', 'using', 'can', 'are', 'you', 'your', 'our', 'all', 'any', 'not', 'but',
        'have', 'has', 'will', 'when', 'where', 'why', 'who', 'make', 'add', 'create', 'build',
        'write', 'new', 'one', 'two', 'more', 'only', 'also', 'via', 'per', 'about',
    ];

    private string $frameworkRoot;

    public function __construct(?string $frameworkRoot = null)
    {
        $this->frameworkRoot = rtrim($frameworkRoot ?? dirname(__DIR__, 4), '\\/');
    }

    /**
     * Load all indexed sections from the bundled framework handbook and workflow skills.
     *
     * @return list<array{id: string, title: string, source: string, content: string}>
     */
    public function documents(): array
    {
        $docs = [];

        $llmsFullPath = $this->frameworkRoot . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'llms-full.txt';
        if (is_file($llmsFullPath)) {
            $raw = file_get_contents($llmsFullPath);
            if (is_string($raw) && '' !== trim($raw)) {
                foreach ($this->splitMarkdownSections($raw, 'site/llms-full.txt') as $sec) {
                    $docs[] = $sec;
                }
            }
        }

        $skillsDir = $this->frameworkRoot . DIRECTORY_SEPARATOR . 'skills';
        $installer = new SkillsInstaller($skillsDir);
        foreach ($installer->available() as $skillName) {
            $path = $skillsDir . DIRECTORY_SEPARATOR . $skillName . DIRECTORY_SEPARATOR . 'SKILL.md';
            $raw = file_get_contents($path);
            if (!is_string($raw) || '' === trim($raw)) {
                continue;
            }
            $body = preg_replace('/\A---\R.*?\R---\R+/s', '', $raw);
            $clean = is_string($body) ? trim($body) : trim($raw);
            $title = $skillName;
            if (1 === preg_match('/^#\s+(.+)$/m', $clean, $m)) {
                $title = trim($m[1]);
            }
            $docs[] = [
                'id' => 'skill:' . $skillName,
                'title' => $title,
                'source' => 'skills/' . $skillName . '/SKILL.md',
                'content' => $clean,
            ];
        }

        return $docs;
    }

    /**
     * Retrieve top matching verified Kongono reference chunks for a developer or agent query.
     *
     * @return list<array{id: string, title: string, source: string, score: float, content: string}>
     */
    public function search(string $query, int $limit = 3): array
    {
        $trimmed = trim($query);
        if ('' === $trimmed) {
            return [];
        }

        $docs = $this->documents();
        if ([] === $docs) {
            return [];
        }

        $queryTokens = $this->tokenize($trimmed);
        $querySymbols = $this->extractSymbols($trimmed);
        if ([] === $queryTokens && [] === $querySymbols) {
            return [];
        }

        $docCount = count($docs);
        /** @var array<string, int> $df */
        $df = [];
        /** @var list<array{tokens: array<string, int>, titleTokens: array<string, int>, lower: string, length: int}> $stats */
        $stats = [];

        foreach ($docs as $doc) {
            $bodyTokens = $this->termFrequencies($this->tokenize($doc['content']));
            $titleTokens = $this->termFrequencies($this->tokenize($doc['title'] . ' ' . $doc['id']));
            $combinedKeys = array_unique([...array_keys($bodyTokens), ...array_keys($titleTokens)]);
            foreach ($combinedKeys as $tok) {
                $df[$tok] = ($df[$tok] ?? 0) + 1;
            }
            $stats[] = [
                'tokens' => $bodyTokens,
                'titleTokens' => $titleTokens,
                'lower' => strtolower($doc['title'] . "\n" . $doc['content']),
                'length' => max(1, array_sum($bodyTokens)),
            ];
        }

        $avgLen = max(1.0, array_sum(array_column($stats, 'length')) / (float) $docCount);
        $scored = [];

        foreach ($docs as $idx => $doc) {
            $st = $stats[$idx];
            $score = 0.0;

            foreach ($queryTokens as $tok) {
                $docFreq = $df[$tok] ?? 0;
                if (0 === $docFreq) {
                    continue;
                }
                $idf = log(1.0 + (($docCount - $docFreq + 0.5) / ($docFreq + 0.5)));
                $tf = (float) ($st['tokens'][$tok] ?? 0);
                if ($tf > 0.0) {
                    $k1 = 1.5;
                    $b = 0.75;
                    $denom = $tf + $k1 * (1.0 - $b + $b * ((float) $st['length'] / $avgLen));
                    $score += $idf * (($tf * ($k1 + 1.0)) / $denom);
                }
                if (isset($st['titleTokens'][$tok])) {
                    $score += $idf * 2.8;
                }
            }

            foreach ($querySymbols as $sym) {
                if (str_contains($st['lower'], $sym)) {
                    $score += 4.5;
                }
            }

            if ($score > 0.0) {
                $scored[] = [
                    'id' => $doc['id'],
                    'title' => $doc['title'],
                    'source' => $doc['source'],
                    'score' => round($score, 3),
                    'content' => $doc['content'],
                ];
            }
        }

        usort($scored, static function (array $a, array $b): int {
            $cmp = $b['score'] <=> $a['score'];

            return 0 !== $cmp ? $cmp : strcmp($a['id'], $b['id']);
        });

        return array_slice($scored, 0, max(1, min(10, $limit)));
    }

    /**
     * Format top retrieved reference sections into a bounded prompt grounding block for LLM companions.
     */
    public function formatContextForPrompt(string $query, int $limit = 3, int $maxChars = 3600): string
    {
        $hits = $this->search($query, $limit);
        if ([] === $hits) {
            return '';
        }

        $parts = [];
        $used = 0;
        foreach ($hits as $hit) {
            $header = sprintf("### %s (%s)\n", $hit['title'], $hit['source']);
            $remaining = $maxChars - $used - strlen($header) - 4;
            if ($remaining < 160) {
                break;
            }
            $snippet = strlen($hit['content']) > $remaining
                ? substr($hit['content'], 0, $remaining - 16) . "\n... [truncated]"
                : $hit['content'];
            $block = $header . $snippet;
            $parts[] = $block;
            $used += strlen($block) + 2;
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return list<array{id: string, title: string, source: string, content: string}>
     */
    private function splitMarkdownSections(string $markdown, string $source): array
    {
        $lines = preg_split('/\R/', $markdown);
        if (false === $lines) {
            return [];
        }

        $sections = [];
        $currentTitle = null;
        /** @var list<string> $buffer */
        $buffer = [];
        $index = 0;

        foreach ($lines as $line) {
            if (1 === preg_match('/\A##\s+(.+)\z/', $line, $m)) {
                if (null !== $currentTitle) {
                    $section = $this->buildMarkdownSection($currentTitle, $buffer, $index + 1, $source);
                    if (null !== $section) {
                        ++$index;
                        $sections[] = $section;
                    }
                    $buffer = [];
                }
                $currentTitle = trim($m[1]);
                continue;
            }
            if (null !== $currentTitle) {
                $buffer[] = $line;
            }
        }
        if (null !== $currentTitle) {
            $section = $this->buildMarkdownSection($currentTitle, $buffer, $index + 1, $source);
            if (null !== $section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * @param list<string> $buffer
     * @return array{id: string, title: string, source: string, content: string}|null
     */
    private function buildMarkdownSection(string $title, array $buffer, int $index, string $source): ?array
    {
        $body = trim(implode("\n", $buffer));
        if ('' === $body) {
            return null;
        }
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));

        return [
            'id' => sprintf('handbook:%02d-%s', $index, '' !== $slug ? $slug : 'section'),
            'title' => $title,
            'source' => $source,
            'content' => "## {$title}\n\n{$body}",
        ];
    }

    /** @return list<string> */
    private function tokenize(string $text): array
    {
        // Split CamelCase identifiers into searchable words too (e.g. ReadOnlyDb -> read only db)
        $expanded = (string) preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        preg_match_all('/[a-z0-9_]{2,}/', strtolower($expanded), $matches);
        $raw = $matches[0];
        $stop = array_flip(self::STOPWORDS);

        $out = [];
        foreach ($raw as $tok) {
            if (!isset($stop[$tok])) {
                $out[] = $tok;
            }
        }

        return $out;
    }

    /**
     * Extract high-signal Kongono symbols (`#[ReadOnlyDb]`, `kongo prove`, `Response::adaptive`, `@safeHtml`, `kgn-post`).
     *
     * @return list<string>
     */
    private function extractSymbols(string $text): array
    {
        $symbols = [];
        if (preg_match_all('/#\[[A-Za-z0-9_]+|@[a-zA-Z][a-zA-Z0-9]+|kongo\s+[a-z0-9:_-]+|[A-Za-z0-9_]+::[A-Za-z0-9_]+|kgn-[a-z0-9-]+|\/_[a-z0-9-]+/i', $text, $m) > 0) {
            foreach ($m[0] as $hit) {
                $symbols[] = strtolower(trim($hit));
            }
        }

        return array_values(array_unique($symbols));
    }

    /**
     * @param list<string> $tokens
     * @return array<string, int>
     */
    private function termFrequencies(array $tokens): array
    {
        $freq = [];
        foreach ($tokens as $tok) {
            $freq[$tok] = ($freq[$tok] ?? 0) + 1;
        }

        return $freq;
    }
}
