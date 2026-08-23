<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Exception\InvalidOshieValueException;

/** Summarizes one closed lesson with its exact source citation. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieLessonSummary
{
    public function __construct(private string $lessonId, private string $title, private string $objective, private OshieCitation $citation)
    {
        if (!self::token($lessonId) || !self::text($title, 128) || !self::text($objective, 256)) {
            throw InvalidOshieValueException::lesson();
        }
    }

    public function lessonId(): string
    {
        return $this->lessonId;
    }
    public function title(): string
    {
        return $this->title;
    }
    public function objective(): string
    {
        return $this->objective;
    }
    public function citation(): OshieCitation
    {
        return $this->citation;
    }

    /** @return array{lesson_id: string, title: string, objective: string, citations: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return ['lesson_id' => $this->lessonId, 'title' => $this->title, 'objective' => $this->objective, 'citations' => [$this->citation->toArray()]];
    }

    private static function token(string $value): bool
    {
        return strlen($value) <= 64 && 1 === preg_match('/\A[a-z0-9-]+\z/D', $value);
    }
    private static function text(string $value, int $max): bool
    {
        return '' !== $value && strlen($value) <= $max && 1 === preg_match('//u', $value) && !str_contains($value, "\r") && !str_contains($value, "\0");
    }
}
