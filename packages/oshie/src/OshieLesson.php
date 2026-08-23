<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Exception\InvalidOshieValueException;

/** Carries one complete exact cited level-two lesson section. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieLesson
{
    public function __construct(private string $lessonId, private string $title, private string $objective, private string $text, private OshieCitation $citation)
    {
        if (strlen($lessonId) > 64 || 1 !== preg_match('/\A[a-z0-9-]+\z/D', $lessonId) || '' === $title || '' === $objective || '' === $text || strlen($text) > 32768 || str_contains($text, "\r") || str_contains($text, "\0")) {
            throw InvalidOshieValueException::lesson();
        }
    }

    public function lessonId(): string
    {
        return $this->lessonId;
    }
    public function text(): string
    {
        return $this->text;
    }
    public function citation(): OshieCitation
    {
        return $this->citation;
    }

    /** @return array{lesson_id: string, title: string, objective: string, text: string, citations: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return ['lesson_id' => $this->lessonId, 'title' => $this->title, 'objective' => $this->objective, 'text' => $this->text, 'citations' => [$this->citation->toArray()]];
    }
}
