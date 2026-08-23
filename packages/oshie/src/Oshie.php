<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\ArtifactStaleException;
use Kongono\Oshie\Exception\ArtifactUnavailableException;
use Kongono\Oshie\Exception\InvalidArtifactException;
use Kongono\Oshie\Exception\InvalidOshieRequestException;
use Kongono\Oshie\Internal\CurriculumCatalog;
use Kongono\Oshie\Internal\SectionExtractor;

/** Teaches exact cited sections and checks fixed answers from one current artifact. */
#[Api(ApiStatus::Experimental)]
final class Oshie
{
    public function respondCurrent(string $frameworkRoot, string $artifactBytes, OshieRequest $request): OshieResponse
    {
        if (strlen($artifactBytes) > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
            throw new InvalidArtifactException(OshieDiagnostic::ResponseLimitExceeded, 'artifact-limit');
        }
        $validator = new FrameworkKnowledgeArtifactValidator();
        try {
            $validator->validateStructure($artifactBytes);
        } catch (InvalidKnowledgeArtifactException $exception) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-shape', $exception);
        }
        try {
            $artifact = $validator->validateCurrent($frameworkRoot, $artifactBytes);
        } catch (StaleKnowledgeArtifactException $exception) {
            throw new ArtifactStaleException(OshieDiagnostic::ArtifactStale, 'source-state-mismatch', $exception);
        } catch (InvalidKnowledgeSourceException $exception) {
            throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'source-unavailable', $exception);
        } catch (InvalidKnowledgeArtifactException $exception) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-shape', $exception);
        }

        $catalog = CurriculumCatalog::load();
        return match ($request->operation()) {
            OshieOperation::Catalog => $this->catalog($artifact, $catalog),
            OshieOperation::Lesson => $this->lesson($artifact, $catalog, $request),
            OshieOperation::Exercise => $this->exercise($artifact, $catalog, $request),
            OshieOperation::Check => $this->check($artifact, $catalog, $request),
        };
    }

    private function catalog(FrameworkKnowledgeArtifact $artifact, CurriculumCatalog $catalog): OshieResponse
    {
        $summaries = [];
        foreach ($catalog->lessons() as $record) {
            [, $citation] = $this->resolve($artifact, $record);
            $summaries[] = new OshieLessonSummary($record['lesson_id'], $record['title'], $record['objective'], $citation);
        }
        return OshieResponse::available($artifact->artifactSha256(), $artifact->sourceSetSha256(), $summaries);
    }

    private function lesson(FrameworkKnowledgeArtifact $artifact, CurriculumCatalog $catalog, OshieRequest $request): OshieResponse
    {
        $lessonId = $request->lessonId();
        if (null === $lessonId) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'operation');
        }
        $record = $catalog->lesson($lessonId);
        if (null === $record) {
            return OshieResponse::unsupported(OshieOperation::Lesson, $artifact->artifactSha256(), $artifact->sourceSetSha256());
        }
        [$section, $citation] = $this->resolve($artifact, $record);
        return OshieResponse::taught($artifact->artifactSha256(), $artifact->sourceSetSha256(), new OshieLesson($record['lesson_id'], $record['title'], $record['objective'], $section, $citation));
    }

    private function exercise(FrameworkKnowledgeArtifact $artifact, CurriculumCatalog $catalog, OshieRequest $request): OshieResponse
    {
        $exerciseId = $request->exerciseId();
        if (null === $exerciseId) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'operation');
        }
        $record = $catalog->exercise($exerciseId);
        if (null === $record) {
            return OshieResponse::unsupported(OshieOperation::Exercise, $artifact->artifactSha256(), $artifact->sourceSetSha256());
        }
        [, $citation] = $this->resolve($artifact, $record);
        $item = $record['exercise'];
        return OshieResponse::practice($artifact->artifactSha256(), $artifact->sourceSetSha256(), new OshieExercise($item['exercise_id'], $record['lesson_id'], $item['prompt'], $item['options'], $citation));
    }

    private function check(FrameworkKnowledgeArtifact $artifact, CurriculumCatalog $catalog, OshieRequest $request): OshieResponse
    {
        $exerciseId = $request->exerciseId();
        $answer = $request->answerToken();
        if (null === $exerciseId || null === $answer) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'operation');
        }
        $record = $catalog->exercise($exerciseId);
        if (null === $record) {
            return OshieResponse::unsupported(OshieOperation::Check, $artifact->artifactSha256(), $artifact->sourceSetSha256());
        }
        if (!in_array($answer, $record['exercise']['options'], true)) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'answer-option');
        }
        [, $citation] = $this->resolve($artifact, $record);
        $accepted = $record['exercise']['accepted_answer'];
        $outcome = $answer === $accepted ? OshieAnswerOutcome::Correct : OshieAnswerOutcome::Incorrect;
        $feedback = OshieAnswerOutcome::Correct === $outcome ? 'The selected answer matches the cited accepted source.' : 'The selected answer does not match the cited accepted source.';
        return OshieResponse::checked($artifact->artifactSha256(), $artifact->sourceSetSha256(), new OshieAttempt($outcome, $accepted, $feedback, $citation));
    }

    /**
     * @param array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}} $record
     * @return array{string, OshieCitation}
     */
    private function resolve(FrameworkKnowledgeArtifact $artifact, array $record): array
    {
        $sources = array_values(array_filter($artifact->sources(), static fn(KnowledgeSource $source): bool => $source->path() === $record['source_path']));
        if (1 !== count($sources)) {
            throw new InvalidArtifactException(OshieDiagnostic::CitationResolutionFailed, 'source-missing');
        }
        $source = $sources[0];
        if (!in_array($source->authority(), [KnowledgeSourceAuthority::GoverningAuthority, KnowledgeSourceAuthority::CurrentExplanatoryDocumentation], true)) {
            throw new InvalidArtifactException(OshieDiagnostic::CitationResolutionFailed, 'authority');
        }
        $section = (new SectionExtractor())->extract($source, $record['heading']);
        $citation = new OshieCitation($source->reference(), $source->path(), $source->authority(), $source->sha256(), $section['line_start'], $section['line_end']);
        return [$section['text'], $citation];
    }
}
