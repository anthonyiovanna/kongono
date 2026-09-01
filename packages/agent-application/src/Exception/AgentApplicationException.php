<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes fixed package evidence while retaining exact throwable identities. */
#[Api(ApiStatus::Internal)]
abstract class AgentApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly AgentApplicationDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidAgentApplicationProfileException && !$this instanceof InvalidGeneratedAgentWiringException && !$this instanceof AgentWiringPublicationException && !$this instanceof AgentWiringLoadException && !$this instanceof AgentApplicationWorkflowException && !$this instanceof AgentApplicationOperationException && !$this instanceof AgentApplicationCleanupException && !$this instanceof AgentApplicationOperationAndCleanupException && !$this instanceof AgentApplicationLifecycleException) {
            throw new LogicException('Agent Application exception type is unsupported.');
        }
        $this->diagnosticContext = $diagnostic->context($context);
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
        return sprintf('%s: [%s] %s.', static::class, $this->diagnosticId(), $this->diagnosticTitle());
    }
}
