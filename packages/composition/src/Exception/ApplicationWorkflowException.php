<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Reports one fixed, bounded build/check workflow-stage failure. */
#[Api(ApiStatus::Experimental)]
final class ApplicationWorkflowException extends RuntimeException implements ExceptionInterface
{
    /** @var list<string> */
    private const STAGES = [
        'application_root',
        'output_directory',
        'artifact_build',
        'wiring_generation',
        'artifact_publication',
        'wiring_publication',
        'artifact_check',
        'wiring_check',
    ];

    /** @var list<string> */
    private const VIOLATIONS = [
        'invalid_application_root',
        'invalid_output_directory',
        'output_not_strictly_contained',
        'unsafe_output_directory',
        'artifact_build_failed',
        'wiring_generation_failed',
        'artifact_publication_failed',
        'wiring_publication_failed',
        'publication_path_mismatch',
        'required_file_missing',
        'unsafe_output_file',
        'changed_during_read',
        'artifact_bytes_mismatch',
        'wiring_bytes_mismatch',
        'current_validation_failed',
    ];

    private readonly DiagnosticContext $diagnosticContext;

    public function __construct(string $workflowStage, string $violation, ?Throwable $previous = null)
    {
        $stage = in_array($workflowStage, self::STAGES, true) ? $workflowStage : 'application_root';
        $fixedViolation = in_array($violation, self::VIOLATIONS, true) ? $violation : 'invalid_application_root';
        $diagnostic = CompositionDiagnostic::ApplicationWorkflowFailed;
        $this->diagnosticContext = $diagnostic->workflowContext($stage, $fixedViolation);
        parent::__construct($diagnostic->title(), 0, $previous);
    }

    public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }

    public function diagnosticId(): string
    {
        return CompositionDiagnostic::ApplicationWorkflowFailed->value;
    }

    public function diagnosticTitle(): string
    {
        return CompositionDiagnostic::ApplicationWorkflowFailed->title();
    }

    public function diagnosticRemediation(): string
    {
        return CompositionDiagnostic::ApplicationWorkflowFailed->remediation();
    }

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnosticId(), $this->diagnosticTitle());
    }
}
