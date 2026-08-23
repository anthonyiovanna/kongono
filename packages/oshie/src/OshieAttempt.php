<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Exception\InvalidOshieValueException;

/** Reports one exact-answer outcome without retaining or returning submitted input. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieAttempt
{
    public function __construct(private OshieAnswerOutcome $outcome, private string $acceptedAnswer, private string $feedback, private OshieCitation $citation)
    {
        $expected = OshieAnswerOutcome::Correct === $outcome
            ? 'The selected answer matches the cited accepted source.'
            : 'The selected answer does not match the cited accepted source.';
        if (strlen($acceptedAnswer) > 64 || 1 !== preg_match('/\A[a-z0-9-]+\z/D', $acceptedAnswer) || $expected !== $feedback) {
            throw InvalidOshieValueException::attempt();
        }
    }

    public function outcome(): OshieAnswerOutcome
    {
        return $this->outcome;
    }
    public function citation(): OshieCitation
    {
        return $this->citation;
    }

    /** @return array{outcome: string, accepted_answer: string, feedback: string, citations: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return ['outcome' => $this->outcome->value, 'accepted_answer' => $this->acceptedAnswer, 'feedback' => $this->feedback, 'citations' => [$this->citation->toArray()]];
    }
}
