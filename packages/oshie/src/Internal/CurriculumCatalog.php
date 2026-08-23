<?php

declare(strict_types=1);

namespace Kongono\Oshie\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\InvalidArtifactException;

/** Loads and proves the one canonical package-owned six-record curriculum. */
#[Api(ApiStatus::Internal)]
final readonly class CurriculumCatalog
{
    public const CURRICULUM_ID = 'phase4-foundation-v1';
    public const RESOURCE_SHA256 = '2da2f66224debeedd7760ce9907474345ea80acfd23502c2919ea9c8e04e4935';

    /** @var list<array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}}> */
    private array $lessons;

    /** @param list<array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}}> $lessons */
    private function __construct(array $lessons)
    {
        $this->lessons = $lessons;
    }

    public static function load(): self
    {
        $path = dirname(__DIR__, 2) . '/resources/phase4-foundation-v1.json';
        $bytes = @file_get_contents($path);
        if (false === $bytes) {
            throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-read');
        }

        return self::fromBytes($bytes);
    }

    public static function fromBytes(string $bytes): self
    {
        if (hash('sha256', $bytes) !== self::RESOURCE_SHA256) {
            throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-digest');
        }
        try {
            $decoded = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-shape', $exception);
        }
        if (!is_array($decoded) || array_keys($decoded) !== ['schema', 'version', 'curriculum', 'lessons']
            || 'kongono.oshie-curriculum' !== $decoded['schema'] || 1 !== $decoded['version']
            || self::CURRICULUM_ID !== $decoded['curriculum'] || !is_array($decoded['lessons']) || !array_is_list($decoded['lessons'])) {
            throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-shape');
        }
        $canonical = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 32) . "\n";
        if ($canonical !== $bytes) {
            throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-canonical');
        }

        $lessons = [];
        foreach ($decoded['lessons'] as $record) {
            if (!is_array($record) || array_keys($record) !== ['lesson_id', 'title', 'objective', 'source_path', 'heading', 'exercise'] || !is_array($record['exercise'])
                || array_keys($record['exercise']) !== ['exercise_id', 'prompt', 'options', 'accepted_answer']) {
                throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-shape');
            }
            $values = [$record['lesson_id'], $record['title'], $record['objective'], $record['source_path'], $record['heading'], $record['exercise']['exercise_id'], $record['exercise']['prompt'], $record['exercise']['accepted_answer']];
            if (count(array_filter($values, is_string(...))) !== count($values) || !is_array($record['exercise']['options']) || !array_is_list($record['exercise']['options'])) {
                throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-value');
            }
            $options = [];
            foreach ($record['exercise']['options'] as $option) {
                if (!is_string($option)) {
                    throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-value');
                }
                $options[] = $option;
            }
            /** @var array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}} $record */
            $record['exercise']['options'] = $options;
            $lessons[] = $record;
        }
        if ($lessons !== self::expected()) {
            throw new InvalidArtifactException(OshieDiagnostic::CurriculumInvalid, 'resource-order');
        }

        return new self($lessons);
    }

    /** @return list<array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}}> */
    public function lessons(): array
    {
        return $this->lessons;
    }

    /** @return array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}}|null */
    public function lesson(string $lessonId): ?array
    {
        foreach ($this->lessons as $lesson) {
            if ($lesson['lesson_id'] === $lessonId) {
                return $lesson;
            }
        }
        return null;
    }

    /** @return array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}}|null */
    public function exercise(string $exerciseId): ?array
    {
        foreach ($this->lessons as $lesson) {
            if ($lesson['exercise']['exercise_id'] === $exerciseId) {
                return $lesson;
            }
        }
        return null;
    }

    /** @return list<array{lesson_id: string, title: string, objective: string, source_path: string, heading: string, exercise: array{exercise_id: string, prompt: string, options: list<string>, accepted_answer: string}}> */
    private static function expected(): array
    {
        return [
            ['lesson_id' => 'role-separation', 'title' => 'Roles and separation of duties', 'objective' => 'Identify the distinct Chie, Hantei, and Oshie responsibilities.', 'source_path' => 'docs/architecture/developer-intelligence.md', 'heading' => '## Roles and promises', 'exercise' => ['exercise_id' => 'role-verifier', 'prompt' => 'Which Kongono role independently verifies deterministic evidence?', 'options' => ['chie', 'hantei', 'oshie'], 'accepted_answer' => 'hantei']],
            ['lesson_id' => 'knowledge-authority', 'title' => 'Framework knowledge authority', 'objective' => 'Distinguish governing authority from evidence and explanatory documentation.', 'source_path' => 'docs/architecture/knowledge-foundation.md', 'heading' => '## Source authority matrix', 'exercise' => ['exercise_id' => 'doctrine-authority', 'prompt' => 'Which source class may establish Kongono doctrine?', 'options' => ['governing-authority', 'implementation-evidence', 'current-explanatory-documentation'], 'accepted_answer' => 'governing-authority']],
            ['lesson_id' => 'php-support', 'title' => 'Rolling PHP support', 'objective' => "Identify Kongono's one-minor compatibility window.", 'source_path' => 'docs/engineering/php-support-policy.md', 'heading' => '## Accepted rolling one-minor policy', 'exercise' => ['exercise_id' => 'supported-minor-window', 'prompt' => 'How many PHP minor lines does one Kongono release certify at a time?', 'options' => ['one', 'two', 'all'], 'accepted_answer' => 'one']],
            ['lesson_id' => 'diagnostic-safety', 'title' => 'Diagnostic disclosure safety', 'objective' => 'Apply the default classification for unstructured diagnostic detail.', 'source_path' => 'docs/engineering/error-handling.md', 'heading' => '## Current safety rule for unstructured detail', 'exercise' => ['exercise_id' => 'unstructured-detail', 'prompt' => 'How is unstructured diagnostic detail classified by default?', 'options' => ['safe', 'protected', 'public'], 'accepted_answer' => 'protected']],
            ['lesson_id' => 'quality-gates', 'title' => 'Canonical quality gates', 'objective' => "Recognize the fixed validation order and each gate's purpose.", 'source_path' => 'docs/engineering/toolchain.md', 'heading' => '## Gate order and meaning', 'exercise' => ['exercise_id' => 'quality-first-gate', 'prompt' => 'Which quality concern is checked before package schemas?', 'options' => ['composer-schema', 'static-analysis', 'tests'], 'accepted_answer' => 'composer-schema']],
            ['lesson_id' => 'phase-sequence', 'title' => 'Phase 4 execution order', 'objective' => 'Identify the next accepted subphase in the locked Phase 4 roadmap.', 'source_path' => 'docs/architecture/phase-4-roadmap.md', 'heading' => '## Fixed A-H roadmap', 'exercise' => ['exercise_id' => 'phase-after-4f', 'prompt' => 'Which accepted Phase 4 subphase follows 4F?', 'options' => ['4e', '4g', '4h'], 'accepted_answer' => '4g']],
        ];
    }
}
