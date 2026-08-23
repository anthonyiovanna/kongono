<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Proposal;

use Kongono\Chie\Chie;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\ProposalTargetStaleException;
use Kongono\Chie\Proposal\ChieProposalAuthority;
use Kongono\Chie\Proposal\ChieProposalRequest;
use Kongono\Chie\Proposal\SafeCandidateText;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Source\KnowledgeSource;
use PHPUnit\Framework\TestCase;

final class ChieProposalServiceTest extends TestCase
{
    private const PATH = 'docs/engineering/php-support-policy.md';
    private const BEFORE = "The actively developed line supports exactly one stable PHP minor at a time:\n";
    private const AFTER = "The actively developed line supports one certified stable PHP minor at a time:\n";

    public function testBuildsOneDeterministicReversibleUnappliedCandidate(): void
    {
        $artifact = $this->artifact();
        $source = $this->source($artifact, self::PATH);
        $proposal = (new Chie())->proposeCurrent($this->root(), $artifact->canonicalBytes(), $this->request($source));
        $proposed = str_replace(self::BEFORE, self::AFTER, $source->content(), $count);

        self::assertSame(1, $count);
        self::assertSame(strtoupper(hash('sha256', $proposed)), $proposal->proposedSha256());
        self::assertStringContainsString("-" . rtrim(self::BEFORE, "\n") . "\n", $proposal->forwardDiff());
        self::assertStringContainsString("+" . rtrim(self::AFTER, "\n") . "\n", $proposal->forwardDiff());
        self::assertStringContainsString("-" . rtrim(self::AFTER, "\n") . "\n", $proposal->reverseDiff());
        self::assertSame($source->content(), str_replace(self::AFTER, self::BEFORE, $proposed, $reverseCount));
        self::assertSame(1, $reverseCount);
        self::assertSame($proposal->toArray(), (new Chie())->proposeCurrent($this->root(), $artifact->canonicalBytes(), $this->request($source))->toArray());
        self::assertSame(hash('sha256', $source->content()), $source->sha256());
    }

    public function testStaleBaseFailsClosed(): void
    {
        $artifact = $this->artifact();
        $this->expectException(ProposalTargetStaleException::class);
        (new Chie())->proposeCurrent($this->root(), $artifact->canonicalBytes(), new ChieProposalRequest(
            new ChieProposalAuthority(),
            self::PATH,
            str_repeat('A', 64),
            new SafeCandidateText(self::BEFORE),
            new SafeCandidateText(self::AFTER),
        ));
    }

    public function testMissingAndNonUniqueTargetsFailClosed(): void
    {
        $artifact = $this->artifact();
        $source = $this->source($artifact, self::PATH);
        try {
            (new Chie())->proposeCurrent($this->root(), $artifact->canonicalBytes(), new ChieProposalRequest(
                new ChieProposalAuthority(),
                self::PATH,
                strtoupper($source->sha256()),
                new SafeCandidateText("the\n"),
                new SafeCandidateText("replacement\n"),
            ));
            self::fail('Expected non-unique replacement rejection.');
        } catch (InvalidChieProposalException $exception) {
            self::assertSame('KGN-CHIE-1016', $exception->diagnosticId());
        }

        $this->expectException(InvalidChieProposalException::class);
        (new Chie())->proposeCurrent($this->root(), $artifact->canonicalBytes(), new ChieProposalRequest(
            new ChieProposalAuthority(),
            'docs/not-present.md',
            str_repeat('A', 64),
            new SafeCandidateText("missing\n"),
            new SafeCandidateText("replacement\n"),
        ));
    }

    private function root(): string
    {
        return dirname(__DIR__, 5);
    }

    private function artifact(): FrameworkKnowledgeArtifact
    {
        return (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
    }

    private function source(FrameworkKnowledgeArtifact $artifact, string $path): KnowledgeSource
    {
        foreach ($artifact->sources() as $source) {
            if ($path === $source->path()) {
                return $source;
            }
        }
        self::fail('Expected artifact source was absent.');
    }

    private function request(KnowledgeSource $source): ChieProposalRequest
    {
        return new ChieProposalRequest(
            new ChieProposalAuthority(),
            self::PATH,
            strtoupper($source->sha256()),
            new SafeCandidateText(self::BEFORE),
            new SafeCandidateText(self::AFTER),
        );
    }
}
