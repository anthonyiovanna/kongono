<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\StorageApplication\Diagnostic\StorageApplicationDiagnostic;
use RuntimeException;
use Throwable;

/** Exposes bounded package-owned evidence without rejected or foreign values. */
#[Api(ApiStatus::Experimental)]
abstract class StorageApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly StorageApplicationDiagnostic $diagnostic,
        string $context,
        ?Throwable $previous = null,
    ) {
        $this->diagnosticContext = $diagnostic->context($context);
        // Previous exceptions and traces are trusted in-process debugging data, not sanitized exports.
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
