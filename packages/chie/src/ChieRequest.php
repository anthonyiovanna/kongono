<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieRequestException;

/** Carries only one closed explanation topic or one Safe diagnostic observation. */
#[Api(ApiStatus::Experimental)]
final readonly class ChieRequest
{
    private function __construct(
        private ChieOperation $operation,
        private ?string $topic,
        private ?DiagnosticObservation $observation,
    ) {}

    public static function explain(string $topic): self
    {
        if (strlen($topic) > 64 || 1 !== preg_match('/^[a-z][a-z0-9-]{0,63}$/D', $topic)) {
            throw new InvalidChieRequestException(ChieDiagnostic::InvalidRequest, 'topic-grammar');
        }

        return new self(ChieOperation::Explain, $topic, null);
    }

    public static function diagnose(DiagnosticObservation $observation): self
    {
        return new self(ChieOperation::Diagnose, null, $observation);
    }

    public function operation(): ChieOperation
    {
        return $this->operation;
    }

    public function topic(): ?string
    {
        return $this->topic;
    }

    public function observation(): ?DiagnosticObservation
    {
        return $this->observation;
    }
}
