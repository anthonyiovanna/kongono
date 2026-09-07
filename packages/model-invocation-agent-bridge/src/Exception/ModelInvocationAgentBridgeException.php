<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelInvocationAgentBridge\Diagnostic\ModelInvocationAgentBridgeDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic bounded failures without disclosing model or Agent values. */
#[Api(ApiStatus::Internal)]
abstract class ModelInvocationAgentBridgeException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final protected function __construct(
        private readonly ModelInvocationAgentBridgeDiagnostic $diagnostic,
        array $contextValues,
        ?Throwable $previous = null,
    ) {
        $invalidType = self::isInvalidDiagnostic($diagnostic) && !$this instanceof InvalidModelInvocationAgentBridgeException;
        $rejectedType = !self::isInvalidDiagnostic($diagnostic) && !$this instanceof ProposalBridgeRejectedException;
        if ($invalidType || $rejectedType) {
            throw new LogicException('Model invocation Agent bridge diagnostic and exception type do not match.');
        }

        $this->diagnosticContext = $diagnostic->context($contextValues);
        parent::__construct($diagnostic->title() . '.', 0, $previous);
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
        $context = [];
        foreach ($this->diagnosticContext->fields() as $field) {
            $context[] = $field->key() . '=' . $field->value();
        }

        return sprintf(
            '%s: [%s] %s. Remediation: %s. Context: %s',
            static::class,
            $this->diagnosticId(),
            $this->diagnosticTitle(),
            $this->diagnosticRemediation(),
            implode(', ', $context),
        );
    }

    private static function isInvalidDiagnostic(ModelInvocationAgentBridgeDiagnostic $diagnostic): bool
    {
        return ModelInvocationAgentBridgeDiagnostic::InvalidBridge === $diagnostic;
    }
}
