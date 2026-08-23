<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;

/** Stores one detached Phase 4D claim and at most one exact citation. */
#[Api(ApiStatus::Experimental)]
final readonly class ChieClaim
{
    /** @var list<ChieCitation> */
    private array $citations;

    /** @param array<array-key, mixed> $citations */
    public function __construct(
        private ChieClaimClass $class,
        private string $text,
        array $citations = [],
    ) {
        $validText = strlen($text) >= 1
            && strlen($text) <= 32768
            && 1 === preg_match('//u', $text)
            && !str_contains($text, "\xEF\xBB\xBF")
            && !str_contains($text, "\r")
            && !str_contains($text, "\0");
        if (!array_is_list($citations)) {
            throw InvalidChieValueException::claim();
        }
        $validatedCitations = [];
        foreach ($citations as $citation) {
            if (!$citation instanceof ChieCitation) {
                throw InvalidChieValueException::claim();
            }
            $validatedCitations[] = $citation;
        }
        $validMatrix = match ($class) {
            ChieClaimClass::AcceptedFrameworkFact => 1 === count($validatedCitations),
            ChieClaimClass::CurrentWorkspaceObservation => 'The caller supplied one Safe diagnostic identifier for read-only diagnosis.' === $text && [] === $validatedCitations,
            ChieClaimClass::UnresolvedUncertainty => 'The requested information is unresolved in the exact validated framework state.' === $text && [] === $validatedCitations,
            default => false,
        };
        if (!$validText || !$validMatrix) {
            throw InvalidChieValueException::claim();
        }
        $this->citations = [...$validatedCitations];
    }

    public function class(): ChieClaimClass
    {
        return $this->class;
    }

    public function text(): string
    {
        return $this->text;
    }

    /** @return list<ChieCitation> */
    public function citations(): array
    {
        return [...$this->citations];
    }

    /** @return array{class: string, text: string, citations: list<array{reference: string, path: string, authority: string, sha256: string, line_start: int, line_end: int}>} */
    public function toArray(): array
    {
        return [
            'class' => $this->class->value,
            'text' => $this->text,
            'citations' => array_map(static fn(ChieCitation $citation): array => $citation->toArray(), $this->citations),
        ];
    }
}
