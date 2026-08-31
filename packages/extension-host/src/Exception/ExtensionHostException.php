<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ExtensionHost\Diagnostic\ExtensionHostDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes only fixed package evidence while retaining an exact previous throwable when present. */
#[Api(ApiStatus::Experimental)]
abstract class ExtensionHostException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly ExtensionHostDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidGeneratedExtensionHostException && !$this instanceof ExtensionHostUnavailableException && !$this instanceof ExtensionHostLifecycleException) {
            throw new LogicException('Extension host exception type is unsupported.');
        }
        $this->diagnosticContext = $diagnostic->context($context);
        parent::__construct($diagnostic->title() . '.', 0, $previous);
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

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }

    public function __toString(): string
    {
        return sprintf('%s: [%s] %s.', static::class, $this->diagnosticId(), $this->diagnosticTitle());
    }
}
