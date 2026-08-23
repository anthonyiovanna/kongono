<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit;

use Kongono\Chie\Chie;
use Kongono\Chie\ChieClaimClass;
use Kongono\Chie\ChieRequest;
use Kongono\Chie\ChieStatus;
use Kongono\Chie\DiagnosticObservation;
use Kongono\Chie\Exception\ArtifactStaleException;
use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use PHPUnit\Framework\TestCase;

final class ChieTest extends TestCase
{
    public function testAllSixTopicsReturnExactConfiguredSectionsAndCitations(): void
    {
        $artifact = $this->artifact();
        $topics = [
            'developer-intelligence' => ['docs/architecture/developer-intelligence.md', '## Roles and promises'],
            'error-handling' => ['docs/engineering/error-handling.md', '## Purpose and current status'],
            'knowledge-artifact' => ['docs/architecture/framework-knowledge-artifact.md', '## Purpose and status'],
            'knowledge-foundation' => ['docs/architecture/knowledge-foundation.md', '## Purpose and status'],
            'phase-4-roadmap' => ['docs/architecture/phase-4-roadmap.md', '## Fixed A-H roadmap'],
            'php-support' => ['docs/engineering/php-support-policy.md', '## Accepted rolling one-minor policy'],
        ];
        foreach ($topics as $token => [$path, $heading]) {
            $response = (new Chie())->respondCurrent($this->root(), $artifact->canonicalBytes(), ChieRequest::explain($token));
            self::assertSame(ChieStatus::Answered, $response->status());
            self::assertCount(1, $response->claims());
            $claim = $response->claims()[0];
            self::assertSame(ChieClaimClass::AcceptedFrameworkFact, $claim->class());
            self::assertStringStartsWith($heading . "\n", $claim->text());
            self::assertCount(1, $claim->citations());
            self::assertSame($path, $claim->citations()[0]->path());
            self::assertSame(hash('sha256', $this->source($artifact, $path)->content()), $claim->citations()[0]->sha256());
            self::assertSame($claim->text(), $this->citedText($artifact, $claim->citations()[0]->path(), $claim->citations()[0]->lineStart(), $claim->citations()[0]->lineEnd()));
        }
    }

    public function testUnknownAdmittedTopicIsUnsupportedAfterCurrentValidation(): void
    {
        $artifact = $this->artifact();
        $response = (new Chie())->respondCurrent($this->root(), $artifact->canonicalBytes(), ChieRequest::explain('unknown-topic'));

        self::assertSame(ChieStatus::Unsupported, $response->status());
        self::assertSame([], $response->claims());
        self::assertSame(['topic-unsupported'], $response->uncertainties());
        self::assertSame($artifact->artifactSha256(), $response->frameworkArtifactSha256());
        self::assertSame($artifact->sourceSetSha256(), $response->sourceSetSha256());
    }

    public function testStructurallyValidOldArtifactMapsToStale(): void
    {
        $temporaryRoot = $this->copyArtifactSources($this->artifact());
        try {
            $readme = $temporaryRoot . '/README.md';
            $bytes = (string) file_get_contents($readme);
            file_put_contents($readme, str_replace('Kongono', 'KongonO', $bytes, $count));
            self::assertGreaterThan(0, $count);
            $oldArtifact = (new FrameworkKnowledgeArtifactGenerator())->generate($temporaryRoot);

            $this->expectException(ArtifactStaleException::class);
            (new Chie())->respondCurrent($this->root(), $oldArtifact->canonicalBytes(), ChieRequest::explain('php-support'));
        } finally {
            $this->removeTree($temporaryRoot);
        }
    }

    public function testMalformedArtifactIsBlockedWithoutFallback(): void
    {
        $this->expectException(InvalidArtifactException::class);
        (new Chie())->respondCurrent($this->root(), "{}\n", ChieRequest::explain('php-support'));
    }

    public function testKnownDiagnosticReturnsBoundedExactOccurrences(): void
    {
        $artifact = $this->artifact();
        $response = (new Chie())->respondCurrent(
            $this->root(),
            $artifact->canonicalBytes(),
            ChieRequest::diagnose(new DiagnosticObservation('KGN-KNOWLEDGE-1001')),
        );

        self::assertSame(ChieStatus::Answered, $response->status());
        self::assertGreaterThanOrEqual(2, count($response->claims()));
        self::assertLessThanOrEqual(9, count($response->claims()));
        self::assertSame(ChieClaimClass::CurrentWorkspaceObservation, $response->claims()[0]->class());
        self::assertSame([], $response->claims()[0]->citations());
        foreach (array_slice($response->claims(), 1) as $claim) {
            self::assertSame(ChieClaimClass::AcceptedFrameworkFact, $claim->class());
            self::assertStringContainsString('KGN-KNOWLEDGE-1001', $claim->text());
            self::assertCount(1, $claim->citations());
            self::assertSame($claim->citations()[0]->lineStart(), $claim->citations()[0]->lineEnd());
        }
    }

    public function testUnknownDiagnosticIsUnsupportedWithoutInventedClaims(): void
    {
        $artifact = $this->artifact();
        $response = (new Chie())->respondCurrent(
            $this->root(),
            $artifact->canonicalBytes(),
            ChieRequest::diagnose(new DiagnosticObservation('KGN-NOT-PRESENT-9999')),
        );

        self::assertSame(ChieStatus::Unsupported, $response->status());
        self::assertSame([], $response->claims());
        self::assertSame(['diagnostic-not-found'], $response->uncertainties());
    }

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    private function artifact(): FrameworkKnowledgeArtifact
    {
        return (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
    }

    private function source(FrameworkKnowledgeArtifact $artifact, string $path): \Kongono\Knowledge\Source\KnowledgeSource
    {
        foreach ($artifact->sources() as $source) {
            if ($source->path() === $path) {
                return $source;
            }
        }

        self::fail('Expected artifact source was absent.');
    }

    private function citedText(FrameworkKnowledgeArtifact $artifact, string $path, int $start, int $end): string
    {
        $lines = explode("\n", $this->source($artifact, $path)->content());

        return implode("\n", array_slice($lines, $start - 1, $end - $start + 1)) . "\n";
    }

    private function copyArtifactSources(FrameworkKnowledgeArtifact $artifact): string
    {
        $root = sys_get_temp_dir() . '/kongono-chie-' . bin2hex(random_bytes(8));
        mkdir($root, 0777, true);
        foreach ($artifact->sources() as $source) {
            $path = $root . '/' . $source->path();
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $source->content());
        }

        return str_replace('\\', '/', (string) realpath($root));
    }

    private function removeTree(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                self::fail('Temporary cleanup yielded an unexpected iterator value.');
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }
}
