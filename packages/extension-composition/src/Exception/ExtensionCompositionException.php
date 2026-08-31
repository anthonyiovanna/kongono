<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ExtensionComposition\Diagnostic\ExtensionCompositionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves causes while exposing only fixed bounded package-owned evidence. */
#[Api(ApiStatus::Experimental)]
abstract class ExtensionCompositionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    final protected function __construct(array $context, ?Throwable $previous = null)
    {
        if (!$this instanceof InvalidExtensionGrantPlanException && !$this instanceof InvalidGeneratedExtensionWiringException && !$this instanceof ExtensionWiringPublicationException) {
            throw new LogicException('Extension composition exception type is unsupported.');
        }
        $diagnostic = ExtensionCompositionDiagnostic::CompositionFailed;
        $this->diagnosticContext = $diagnostic->context($context);
        parent::__construct($diagnostic->title() . '.', 0, $previous);
    }

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    final public function diagnosticId(): string
    {
        return ExtensionCompositionDiagnostic::CompositionFailed->value;
    }
    final public function diagnosticTitle(): string
    {
        return ExtensionCompositionDiagnostic::CompositionFailed->title();
    }
    final public function diagnosticRemediation(): string
    {
        return ExtensionCompositionDiagnostic::CompositionFailed->remediation();
    }

    final public function __toString(): string
    {
        $context = array_map(static fn($field): string => $field->key() . '=' . $field->value(), $this->diagnosticContext->fields());

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $context));
    }
}
