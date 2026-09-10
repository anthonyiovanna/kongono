<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ExternalServiceResolverSystem\Diagnostic\SystemResolverDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes deterministic local evidence without stringifying rejected inputs. */
#[Api(ApiStatus::Internal)]
abstract class ExternalServiceResolverSystemException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly SystemResolverDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidSystemResolverProfileException && !$this instanceof SystemResolverEnvironmentException) {
            throw new LogicException('External-service system resolver exception type is unsupported.');
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
