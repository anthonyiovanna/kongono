<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/**
 * Provides the Internal base for deliberate Application Graph failures.
 *
 * Consumers should catch {@see ExceptionInterface}. This class defines no
 * logging, transport, localization, telemetry, or retry policy.
 */
#[Api(ApiStatus::Internal)]
abstract class GraphException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final public function __construct(
        private readonly GraphDiagnostic $diagnostic,
        string $message,
        array $contextValues,
        ?Throwable $previous = null,
    ) {
        $this->diagnosticContext = $diagnostic->context($contextValues);
        parent::__construct($message, 0, $previous);
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
}
