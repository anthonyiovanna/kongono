<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Exception\InvalidOshieValueException;
use Kongono\Oshie\Internal\CurriculumCatalog;

/** Represents one detached canonical response with a closed payload matrix. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieResponse
{
    /** @var list<OshieLessonSummary> */ private array $curriculum;
    /** @var list<string> */ private array $uncertainties;

    /**
     * @param array<array-key, mixed> $curriculum
     * @param array<array-key, mixed> $uncertainties
     */
    private function __construct(
        private OshieStatus $status,
        private OshieOperation $operation,
        private ?string $curriculumId,
        private ?string $frameworkArtifactSha256,
        private ?string $sourceSetSha256,
        array $curriculum,
        private ?OshieLesson $lesson,
        private ?OshieExercise $exercise,
        private ?OshieAttempt $attempt,
        array $uncertainties,
    ) {
        if (!array_is_list($curriculum) || !array_is_list($uncertainties)) {
            throw InvalidOshieValueException::response();
        }
        $validatedCurriculum = [];
        foreach ($curriculum as $summary) {
            if (!$summary instanceof OshieLessonSummary) {
                throw InvalidOshieValueException::response();
            }
            $validatedCurriculum[] = $summary;
        }
        $validatedUncertainties = [];
        foreach ($uncertainties as $uncertainty) {
            if (!is_string($uncertainty)) {
                throw InvalidOshieValueException::response();
            }
            $validatedUncertainties[] = $uncertainty;
        }
        $current = CurriculumCatalog::CURRICULUM_ID === $curriculumId
            && null !== $frameworkArtifactSha256 && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $frameworkArtifactSha256)
            && null !== $sourceSetSha256 && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $sourceSetSha256);
        $empty = [] === $validatedCurriculum && null === $lesson && null === $exercise && null === $attempt;
        $valid = match ($status) {
            OshieStatus::Available => OshieOperation::Catalog === $operation && $current && 6 === count($validatedCurriculum) && null === $lesson && null === $exercise && null === $attempt && [] === $validatedUncertainties,
            OshieStatus::Taught => OshieOperation::Lesson === $operation && $current && [] === $validatedCurriculum && null !== $lesson && null === $exercise && null === $attempt && [] === $validatedUncertainties,
            OshieStatus::Practice => OshieOperation::Exercise === $operation && $current && [] === $validatedCurriculum && null === $lesson && null !== $exercise && null === $attempt && [] === $validatedUncertainties,
            OshieStatus::Correct, OshieStatus::Incorrect => OshieOperation::Check === $operation && $current && [] === $validatedCurriculum && null === $lesson && null === $exercise && null !== $attempt && $status->value === $attempt->outcome()->value && [] === $validatedUncertainties,
            OshieStatus::Unsupported => $current && $empty && [OshieOperation::Lesson === $operation ? 'lesson-unsupported' : 'exercise-unsupported'] === $validatedUncertainties && OshieOperation::Catalog !== $operation,
            OshieStatus::Unavailable => null === $curriculumId && null === $frameworkArtifactSha256 && null === $sourceSetSha256 && $empty && ['artifact-unavailable'] === $validatedUncertainties,
            OshieStatus::Stale => null === $curriculumId && null === $frameworkArtifactSha256 && null === $sourceSetSha256 && $empty && ['artifact-stale'] === $validatedUncertainties,
            OshieStatus::Blocked => null === $curriculumId && null === $frameworkArtifactSha256 && null === $sourceSetSha256 && $empty && ['artifact-invalid'] === $validatedUncertainties,
        };
        if (!$valid) {
            throw InvalidOshieValueException::response();
        }
        if (null !== $sourceSetSha256) {
            foreach ($this->citations($validatedCurriculum, $lesson, $exercise, $attempt) as $citation) {
                if (1 !== preg_match('/\Akgn-source-v1:([a-f0-9]{64}):/', $citation->reference(), $match) || $match[1] !== $sourceSetSha256) {
                    throw InvalidOshieValueException::response();
                }
            }
        }
        $this->curriculum = [...$validatedCurriculum];
        $this->uncertainties = [...$validatedUncertainties];
    }

    /** @param list<OshieLessonSummary> $curriculum */
    public static function available(string $artifact, string $sourceSet, array $curriculum): self
    {
        return new self(OshieStatus::Available, OshieOperation::Catalog, CurriculumCatalog::CURRICULUM_ID, $artifact, $sourceSet, $curriculum, null, null, null, []);
    }
    public static function taught(string $artifact, string $sourceSet, OshieLesson $lesson): self
    {
        return new self(OshieStatus::Taught, OshieOperation::Lesson, CurriculumCatalog::CURRICULUM_ID, $artifact, $sourceSet, [], $lesson, null, null, []);
    }
    public static function practice(string $artifact, string $sourceSet, OshieExercise $exercise): self
    {
        return new self(OshieStatus::Practice, OshieOperation::Exercise, CurriculumCatalog::CURRICULUM_ID, $artifact, $sourceSet, [], null, $exercise, null, []);
    }
    public static function checked(string $artifact, string $sourceSet, OshieAttempt $attempt): self
    {
        $status = OshieAnswerOutcome::Correct === $attempt->outcome() ? OshieStatus::Correct : OshieStatus::Incorrect;
        return new self($status, OshieOperation::Check, CurriculumCatalog::CURRICULUM_ID, $artifact, $sourceSet, [], null, null, $attempt, []);
    }
    public static function unsupported(OshieOperation $operation, string $artifact, string $sourceSet): self
    {
        return new self(OshieStatus::Unsupported, $operation, CurriculumCatalog::CURRICULUM_ID, $artifact, $sourceSet, [], null, null, null, [OshieOperation::Lesson === $operation ? 'lesson-unsupported' : 'exercise-unsupported']);
    }
    public static function artifactFailure(OshieOperation $operation, OshieStatus $status, string $uncertainty): self
    {
        return new self($status, $operation, null, null, null, [], null, null, null, [$uncertainty]);
    }

    public function status(): OshieStatus
    {
        return $this->status;
    }
    public function operation(): OshieOperation
    {
        return $this->operation;
    }
    public function frameworkArtifactSha256(): ?string
    {
        return $this->frameworkArtifactSha256;
    }
    public function sourceSetSha256(): ?string
    {
        return $this->sourceSetSha256;
    }
    /** @return list<OshieLessonSummary> */ public function curriculum(): array
    {
        return [...$this->curriculum];
    }
    public function lesson(): ?OshieLesson
    {
        return $this->lesson;
    }
    public function exercise(): ?OshieExercise
    {
        return $this->exercise;
    }
    public function attempt(): ?OshieAttempt
    {
        return $this->attempt;
    }
    /** @return list<string> */ public function uncertainties(): array
    {
        return [...$this->uncertainties];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.oshie-response', 'version' => 1, 'role' => 'oshie', 'status' => $this->status->value, 'operation' => $this->operation->value,
            'curriculum_id' => $this->curriculumId, 'framework_artifact_sha256' => $this->frameworkArtifactSha256, 'source_set_sha256' => $this->sourceSetSha256,
            'curriculum' => array_map(static fn(OshieLessonSummary $summary): array => $summary->toArray(), $this->curriculum),
            'lesson' => $this->lesson?->toArray(), 'exercise' => $this->exercise?->toArray(), 'attempt' => $this->attempt?->toArray(), 'uncertainties' => [...$this->uncertainties],
        ];
    }

    /**
     * @param list<OshieLessonSummary> $curriculum
     * @return list<OshieCitation>
     */
    private function citations(array $curriculum, ?OshieLesson $lesson, ?OshieExercise $exercise, ?OshieAttempt $attempt): array
    {
        $citations = array_map(static fn(OshieLessonSummary $summary): OshieCitation => $summary->citation(), $curriculum);
        if (null !== $lesson) {
            $citations[] = $lesson->citation();
        }
        if (null !== $exercise) {
            $citations[] = $exercise->citation();
        }
        if (null !== $attempt) {
            $citations[] = $attempt->citation();
        }
        return $citations;
    }
}
