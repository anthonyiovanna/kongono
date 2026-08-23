<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Exception\InvalidOshieValueException;

/** Carries one cited deterministic prompt without exposing its accepted answer. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieExercise
{
    /** @var list<string> */
    private array $options;

    /** @param array<array-key, mixed> $options */
    public function __construct(private string $exerciseId, private string $lessonId, private string $prompt, array $options, private OshieCitation $citation)
    {
        if (!array_is_list($options) || strlen($exerciseId) > 64 || 1 !== preg_match('/\A[a-z0-9-]+\z/D', $exerciseId) || strlen($lessonId) > 64 || 1 !== preg_match('/\A[a-z0-9-]+\z/D', $lessonId) || '' === $prompt || strlen($prompt) > 256) {
            throw InvalidOshieValueException::exercise();
        }
        $validated = [];
        foreach ($options as $option) {
            if (!is_string($option) || strlen($option) > 64 || 1 !== preg_match('/\A[a-z0-9-]+\z/D', $option) || in_array($option, $validated, true)) {
                throw InvalidOshieValueException::exercise();
            }
            $validated[] = $option;
        }
        if (count($validated) < 2) {
            throw InvalidOshieValueException::exercise();
        }
        $this->options = [...$validated];
    }

    public function exerciseId(): string
    {
        return $this->exerciseId;
    }
    /** @return list<string> */ public function options(): array
    {
        return [...$this->options];
    }
    public function citation(): OshieCitation
    {
        return $this->citation;
    }

    /** @return array{exercise_id: string, lesson_id: string, prompt: string, options: list<string>, citations: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return ['exercise_id' => $this->exerciseId, 'lesson_id' => $this->lessonId, 'prompt' => $this->prompt, 'options' => [...$this->options], 'citations' => [$this->citation->toArray()]];
    }
}
