<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes bounded package evidence without rendering foreign failure messages. */
#[Api(ApiStatus::Internal)]
abstract class NetworkEgressApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly NetworkEgressApplicationDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidGeneratedNetworkEgressWiringException
            && !$this instanceof InvalidNetworkEgressApplicationProfileException
            && !$this instanceof NetworkEgressWiringPublicationException
            && !$this instanceof NetworkEgressWiringLoadException
            && !$this instanceof NetworkEgressApplicationWorkflowException
            && !$this instanceof NetworkEgressApplicationLifecycleException
            && !$this instanceof NetworkEgressApplicationOperationException
        ) {
            throw new LogicException('Network Egress Application exception type is unsupported.');
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
