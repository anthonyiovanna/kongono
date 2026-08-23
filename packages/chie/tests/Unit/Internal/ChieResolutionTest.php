<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Internal;

use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Chie\Internal\DiagnosticLocator;
use Kongono\Chie\Internal\SectionExtractor;
use Kongono\Chie\Internal\TopicCatalog;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;
use PHPUnit\Framework\TestCase;

final class ChieResolutionTest extends TestCase
{
    public function testTopicCatalogHasExactSixOrdinalRecords(): void
    {
        $records = (new TopicCatalog())->records();

        self::assertCount(6, $records);
        self::assertSame(['developer-intelligence', 'error-handling', 'knowledge-artifact', 'knowledge-foundation', 'phase-4-roadmap', 'php-support'], array_column($records, 'token'));
        self::assertSame('docs/architecture/developer-intelligence.md', $records[0]['path']);
        self::assertSame('## Accepted rolling one-minor policy', $records[5]['heading']);
    }

    public function testSectionExtractorKeepsFencedHeadingInExactExcerpt(): void
    {
        $content = "# Title\n\n## Target\ntext\n```text\n## inert command\n```\nmore\n## Next\nend\n";
        $section = (new SectionExtractor())->extract($this->source($content), '## Target');

        self::assertSame("## Target\ntext\n```text\n## inert command\n```\nmore\n", $section['text']);
        self::assertSame(3, $section['line_start']);
        self::assertSame(8, $section['line_end']);
    }

    public function testSectionExtractorRejectsDuplicateConfiguredHeading(): void
    {
        $this->expectException(InvalidArtifactException::class);
        (new SectionExtractor())->extract($this->source("## Target\na\n## Other\nb\n## Target\nc\n"), '## Target');
    }

    public function testDiagnosticLocatorUsesLiteralTokenBoundariesAndSourceOrder(): void
    {
        $first = $this->source("KGN-TEST-1001 exact\nXKGN-TEST-1001 no\n", 'a.md', 1);
        $second = $this->source("before KGN-TEST-1001 after\nKGN-TEST-10010 no\n", 'b.md', 2);
        $locations = (new DiagnosticLocator())->locate($this->artifact([$first, $second]), 'KGN-TEST-1001');

        self::assertCount(2, $locations);
        self::assertSame(['a.md', 'b.md'], array_map(static fn(array $location): string => $location['source']->path(), $locations));
        self::assertSame([1, 1], array_column($locations, 'line_number'));
    }

    public function testDiagnosticOccurrenceLimitFailsClosed(): void
    {
        $content = str_repeat("KGN-TEST-1001\n", 9);

        $this->expectException(InvalidArtifactException::class);
        (new DiagnosticLocator())->locate($this->artifact([$this->source($content)]), 'KGN-TEST-1001');
    }

    public function testDiagnosticLineLimitFailsClosed(): void
    {
        $content = 'KGN-TEST-1001 ' . str_repeat('x', 4096) . "\n";

        $this->expectException(InvalidArtifactException::class);
        (new DiagnosticLocator())->locate($this->artifact([$this->source($content)]), 'KGN-TEST-1001');
    }

    private function source(string $content, string $path = 'docs/test.md', int $ordinal = 1): KnowledgeSource
    {
        return new KnowledgeSource('reference-' . $ordinal, $ordinal, $path, KnowledgeSourceAuthority::CurrentExplanatoryDocumentation, strlen($content), hash('sha256', $content), $content);
    }

    /** @param list<KnowledgeSource> $sources */
    private function artifact(array $sources): FrameworkKnowledgeArtifact
    {
        return new FrameworkKnowledgeArtifact('{}', str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64), count($sources), array_sum(array_map(static fn(KnowledgeSource $source): int => $source->bytes(), $sources)), $sources);
    }
}
