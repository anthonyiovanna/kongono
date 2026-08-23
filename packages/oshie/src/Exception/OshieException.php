<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use RuntimeException;
use Throwable;

/** Provides fixed package diagnostics while retaining native causes internally. */
#[Api(ApiStatus::Internal)]
abstract class OshieException extends RuntimeException implements ExceptionInterface
{
    private readonly OshieDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    final public function __construct(OshieDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
    {
        $validPair = match (true) {
            $this instanceof ArtifactUnavailableException => OshieDiagnostic::ArtifactUnavailable === $diagnostic,
            $this instanceof ArtifactStaleException => OshieDiagnostic::ArtifactStale === $diagnostic,
            $this instanceof InvalidOshieRequestException => in_array($diagnostic, [OshieDiagnostic::InvalidRequest, OshieDiagnostic::ResponseLimitExceeded], true),
            $this instanceof InvalidArtifactException => in_array($diagnostic, [OshieDiagnostic::InvalidArtifact, OshieDiagnostic::CurriculumInvalid, OshieDiagnostic::SourceSectionInvalid, OshieDiagnostic::CitationResolutionFailed, OshieDiagnostic::ResponseLimitExceeded], true),
            default => false,
        };
        if (!$validPair) {
            throw InvalidOshieValueException::exceptionDiagnostic();
        }
        $this->diagnostic = $diagnostic;
        $this->diagnosticContext = $diagnostic->context($violation);
        parent::__construct($diagnostic->title(), 0, $previous);
    }

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    final public function diagnosticId(): string
    {
        return $this->diagnostic->value;
    }
    final public function diagnosticTitle(): string
    {
        return $this->diagnostic->title();
    }
    final public function diagnosticRemediation(): string
    {
        return $this->diagnostic->remediation();
    }
    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
