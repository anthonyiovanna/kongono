<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;

/** Carries caller-asserted Safe, detached, LF-only complete lines without sanitizing them. */
#[Api(ApiStatus::Experimental)]
final readonly class SafeCandidateText
{
    public function __construct(private string $text)
    {
        $bytes = strlen($text);
        $lines = substr_count($text, "\n");
        if (
            $bytes < 1
            || $bytes > 8192
            || $lines < 1
            || $lines > 128
            || !str_ends_with($text, "\n")
            || str_contains($text, "\r")
            || str_contains($text, "\0")
            || 1 !== preg_match('//u', $text)
        ) {
            throw InvalidChieValueException::candidateText();
        }
    }

    public function text(): string
    {
        return $this->text;
    }
}
