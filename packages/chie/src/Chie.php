<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\ArtifactStaleException;
use Kongono\Chie\Exception\ArtifactUnavailableException;
use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Chie\Internal\DiagnosticLocator;
use Kongono\Chie\Internal\ProposalBuilder;
use Kongono\Chie\Internal\SectionExtractor;
use Kongono\Chie\Internal\TopicCatalog;
use Kongono\Chie\Proposal\ChiePatchProposal;
use Kongono\Chie\Proposal\ChieProposalRequest;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;

/** Returns exact cited sections or bounded diagnostic occurrences from one current artifact. */
#[Api(ApiStatus::Experimental)]
final class Chie
{
    public function respondCurrent(string $frameworkRoot, string $artifactBytes, ChieRequest $request): ChieResponse
    {
        $artifact = $this->currentArtifact($frameworkRoot, $artifactBytes);

        return match ($request->operation()) {
            ChieOperation::Explain => $this->explain($artifact, $request),
            ChieOperation::Diagnose => $this->diagnose($artifact, $request),
        };
    }

    /** Constructs one unapplied review candidate from the exact current artifact. */
    public function proposeCurrent(string $frameworkRoot, string $artifactBytes, ChieProposalRequest $request): ChiePatchProposal
    {
        return (new ProposalBuilder())->build($this->currentArtifact($frameworkRoot, $artifactBytes), $request);
    }

    private function currentArtifact(string $frameworkRoot, string $artifactBytes): FrameworkKnowledgeArtifact
    {
        if (strlen($artifactBytes) > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
            throw new InvalidArtifactException(ChieDiagnostic::ResponseLimitExceeded, 'artifact-limit');
        }
        $validator = new FrameworkKnowledgeArtifactValidator();
        try {
            $validator->validateStructure($artifactBytes);
        } catch (InvalidKnowledgeArtifactException $exception) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-shape', $exception);
        }
        try {
            $artifact = $validator->validateCurrent($frameworkRoot, $artifactBytes);
        } catch (StaleKnowledgeArtifactException $exception) {
            throw new ArtifactStaleException(ChieDiagnostic::ArtifactStale, 'source-state-mismatch', $exception);
        } catch (InvalidKnowledgeSourceException $exception) {
            throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'source-unavailable', $exception);
        } catch (InvalidKnowledgeArtifactException $exception) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-shape', $exception);
        }

        return $artifact;
    }

    private function explain(FrameworkKnowledgeArtifact $artifact, ChieRequest $request): ChieResponse
    {
        $topic = $request->topic();
        if (null === $topic) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidRequest, 'operation');
        }
        $record = (new TopicCatalog())->record($topic);
        if (null === $record) {
            return ChieResponse::unsupported(ChieOperation::Explain, $artifact->artifactSha256(), $artifact->sourceSetSha256(), 'topic-unsupported');
        }
        $sources = array_values(array_filter($artifact->sources(), static fn(KnowledgeSource $source): bool => $source->path() === $record['path']));
        if (1 !== count($sources)) {
            throw new InvalidArtifactException(ChieDiagnostic::CitationResolutionFailed, 'source-missing');
        }
        $source = $sources[0];
        if (!in_array($source->authority(), [KnowledgeSourceAuthority::GoverningAuthority, KnowledgeSourceAuthority::CurrentExplanatoryDocumentation], true)) {
            throw new InvalidArtifactException(ChieDiagnostic::CitationResolutionFailed, 'authority');
        }
        $section = (new SectionExtractor())->extract($source, $record['heading']);
        $citation = new ChieCitation($source->reference(), $source->path(), $source->authority(), $source->sha256(), $section['line_start'], $section['line_end']);
        $claim = new ChieClaim(ChieClaimClass::AcceptedFrameworkFact, $section['text'], [$citation]);

        return ChieResponse::answered(ChieOperation::Explain, $artifact->artifactSha256(), $artifact->sourceSetSha256(), [$claim]);
    }

    private function diagnose(FrameworkKnowledgeArtifact $artifact, ChieRequest $request): ChieResponse
    {
        $observation = $request->observation();
        if (null === $observation) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidRequest, 'operation');
        }
        $occurrences = (new DiagnosticLocator())->locate($artifact, $observation->identifier());
        if ([] === $occurrences) {
            return ChieResponse::unsupported(ChieOperation::Diagnose, $artifact->artifactSha256(), $artifact->sourceSetSha256(), 'diagnostic-not-found');
        }
        $claims = [new ChieClaim(
            ChieClaimClass::CurrentWorkspaceObservation,
            'The caller supplied one Safe diagnostic identifier for read-only diagnosis.',
        )];
        foreach ($occurrences as $occurrence) {
            $source = $occurrence['source'];
            $citation = new ChieCitation($source->reference(), $source->path(), $source->authority(), $source->sha256(), $occurrence['line_number'], $occurrence['line_number']);
            $claims[] = new ChieClaim(ChieClaimClass::AcceptedFrameworkFact, $occurrence['line'], [$citation]);
        }

        return ChieResponse::answered(ChieOperation::Diagnose, $artifact->artifactSha256(), $artifact->sourceSetSha256(), $claims);
    }
}
