<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Diagnostic\CacheBuildDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic non-disclosing failures for cache-profile builds. */
#[Api(ApiStatus::Internal)]
abstract class CacheBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final protected function __construct(
        private readonly CacheBuildDiagnostic $diagnostic,
        array $contextValues,
        ?Throwable $previous = null,
    ) {
        $expected = match ($diagnostic) {
            CacheBuildDiagnostic::InvalidProfile => InvalidCacheProfileException::class,
            CacheBuildDiagnostic::InvalidArtifact => InvalidCacheProfileArtifactException::class,
            CacheBuildDiagnostic::StaleArtifact => StaleCacheProfileArtifactException::class,
            CacheBuildDiagnostic::PublicationFailed => CacheProfilePublicationException::class,
        };
        if (!$this instanceof $expected) {
            throw new LogicException('Cache build diagnostic and exception type do not match.');
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

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $context));
    }
}
