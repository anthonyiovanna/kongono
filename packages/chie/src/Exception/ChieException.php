<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Provides fixed package diagnostics while retaining native causes internally. */
#[Api(ApiStatus::Internal)]
abstract class ChieException extends RuntimeException implements ExceptionInterface
{
    private readonly ChieDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    final public function __construct(ChieDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
    {
        $validPair = match (true) {
            $this instanceof ArtifactUnavailableException => ChieDiagnostic::ArtifactUnavailable === $diagnostic,
            $this instanceof ArtifactStaleException => ChieDiagnostic::ArtifactStale === $diagnostic,
            $this instanceof InvalidChieRequestException => in_array($diagnostic, [ChieDiagnostic::InvalidRequest, ChieDiagnostic::ResponseLimitExceeded], true),
            $this instanceof InvalidArtifactException => in_array($diagnostic, [
                ChieDiagnostic::InvalidArtifact,
                ChieDiagnostic::InvalidRequest,
                ChieDiagnostic::CitationResolutionFailed,
                ChieDiagnostic::SourceSectionInvalid,
                ChieDiagnostic::ResponseLimitExceeded,
            ], true),
            $this instanceof InvalidChieProposalException => in_array($diagnostic, [
                ChieDiagnostic::InvalidProposalRequest,
                ChieDiagnostic::ProposalSpecInvalid,
                ChieDiagnostic::ProposalTargetUnsupported,
                ChieDiagnostic::ProposalReplacementInvalid,
                ChieDiagnostic::ProposalDiffLimitExceeded,
                ChieDiagnostic::ProposalConstructionFailed,
            ], true),
            $this instanceof ProposalSpecUnavailableException => ChieDiagnostic::ProposalSpecUnavailable === $diagnostic,
            $this instanceof ProposalTargetStaleException => ChieDiagnostic::ProposalTargetStale === $diagnostic,
            default => false,
        };
        if (!$validPair) {
            throw InvalidChieValueException::exceptionDiagnostic();
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
