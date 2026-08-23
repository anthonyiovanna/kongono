<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\ProposalTargetStaleException;
use Kongono\Chie\Proposal\ChiePatchHandoff;
use Kongono\Chie\Proposal\ChiePatchProposal;
use Kongono\Chie\Proposal\ChiePatchProvenance;
use Kongono\Chie\Proposal\ChieProposalRequest;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;

/** Constructs one exact reversible candidate from one validated artifact source. */
#[Api(ApiStatus::Internal)]
final class ProposalBuilder
{
    public function build(FrameworkKnowledgeArtifact $artifact, ChieProposalRequest $request): ChiePatchProposal
    {
        $sources = array_values(array_filter(
            $artifact->sources(),
            static fn(KnowledgeSource $source): bool => $source->path() === $request->targetPath(),
        ));
        if (1 !== count($sources)) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalTargetUnsupported, 'target-missing');
        }
        $source = $sources[0];
        if (KnowledgeSourceAuthority::CurrentExplanatoryDocumentation !== $source->authority()) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalTargetUnsupported, 'target-authority');
        }
        $sourceSha256 = strtoupper($source->sha256());
        if ($request->baseSha256() !== $sourceSha256) {
            throw new ProposalTargetStaleException(ChieDiagnostic::ProposalTargetStale, 'target-sha');
        }
        $sourceText = $source->content();
        if (
            strlen($sourceText) > 262144
            || !str_ends_with($sourceText, "\n")
            || str_contains($sourceText, "\r")
            || str_contains($sourceText, "\0")
            || 1 !== preg_match('//u', $sourceText)
        ) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalReplacementInvalid, 'source-limit');
        }
        $sourceLines = $this->completeLines($sourceText);
        $beforeLines = $this->completeLines($request->before()->text());
        $afterLines = $this->completeLines($request->after()->text());
        $matches = $this->matches($sourceLines, $beforeLines);
        if (1 !== count($matches)) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalReplacementInvalid, 'match-count');
        }
        $match = $matches[0];
        $proposedLines = $sourceLines;
        array_splice($proposedLines, $match, count($beforeLines), $afterLines);
        $proposedText = implode("\n", $proposedLines) . "\n";
        $proposedSha256 = strtoupper(hash('sha256', $proposedText));
        $diffBuilder = new UnifiedDiffBuilder();
        $forward = $diffBuilder->build($source->path(), $sourceLines, $match, $beforeLines, $afterLines);
        $reverse = $diffBuilder->build($source->path(), $proposedLines, $match, $afterLines, $beforeLines);
        $provenance = new ChiePatchProvenance(
            $source->reference(),
            $source->path(),
            $source->authority()->value,
            $sourceSha256,
            $match + 1,
            $match + count($beforeLines),
        );

        return new ChiePatchProposal(
            strtoupper($artifact->artifactSha256()),
            strtoupper($artifact->sourceSetSha256()),
            $source->path(),
            $sourceSha256,
            $proposedSha256,
            $forward,
            $reverse,
            $provenance,
            new ChiePatchHandoff($proposedSha256),
            ['generated-candidate-not-authority', 'candidate-not-applied', 'candidate-not-verified'],
        );
    }

    /** @return list<string> */
    private function completeLines(string $text): array
    {
        $lines = explode("\n", $text);
        array_pop($lines);

        return $lines;
    }

    /**
     * @param list<string> $source
     * @param list<string> $needle
     *
     * @return list<int>
     */
    private function matches(array $source, array $needle): array
    {
        $matches = [];
        $limit = count($source) - count($needle);
        for ($index = 0; $index <= $limit; ++$index) {
            if (array_slice($source, $index, count($needle)) === $needle) {
                $matches[] = $index;
            }
        }

        return $matches;
    }
}
