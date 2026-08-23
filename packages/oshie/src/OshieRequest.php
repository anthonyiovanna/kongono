<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\InvalidOshieRequestException;
use Kongono\Oshie\Internal\CurriculumCatalog;

/** Carries only one operation from the closed package-owned Oshie curriculum. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieRequest
{
    private function __construct(private OshieOperation $operation, private ?string $lessonId, private ?string $exerciseId, private ?string $answerToken) {}

    public static function catalog(): self
    {
        return new self(OshieOperation::Catalog, null, null, null);
    }
    public static function lesson(string $lessonId): self
    {
        self::assertToken($lessonId, 'identifier-grammar');
        return new self(OshieOperation::Lesson, $lessonId, null, null);
    }
    public static function exercise(string $exerciseId): self
    {
        self::assertToken($exerciseId, 'identifier-grammar');
        return new self(OshieOperation::Exercise, null, $exerciseId, null);
    }
    public static function check(string $exerciseId, string $answerToken): self
    {
        self::assertToken($exerciseId, 'identifier-grammar');
        self::assertToken($answerToken, 'answer-grammar');
        $record = CurriculumCatalog::load()->exercise($exerciseId);
        if (null !== $record && !in_array($answerToken, $record['exercise']['options'], true)) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'answer-option');
        }
        return new self(OshieOperation::Check, null, $exerciseId, $answerToken);
    }

    public function operation(): OshieOperation
    {
        return $this->operation;
    }
    public function lessonId(): ?string
    {
        return $this->lessonId;
    }
    public function exerciseId(): ?string
    {
        return $this->exerciseId;
    }
    public function answerToken(): ?string
    {
        return $this->answerToken;
    }

    private static function assertToken(string $value, string $violation): void
    {
        if (strlen($value) > 64 || 1 !== preg_match('/\A[a-z0-9-]+\z/D', $value)) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, $violation);
        }
    }
}
