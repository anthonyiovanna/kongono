<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic nondisclosing failures for the closed mapping boundary. */
#[Api(ApiStatus::Internal)]
abstract class OpenAiResponsesException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final protected function __construct(
        private readonly OpenAiResponsesDiagnostic $diagnostic,
        array $contextValues,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidOpenAiResponsesProfileException
            && !$this instanceof InvalidOpenAiResponsesApplicationProfileException
            && !$this instanceof OpenAiResponsesApplicationWorkflowException
            && !$this instanceof OpenAiResponsesApplicationLifecycleException
            && !$this instanceof OpenAiResponsesApplicationOperationException
            && !$this instanceof ResponsesRequestEncodingException
            && !$this instanceof ResponsesResponseDecodingException
            && !$this instanceof OpenAiResponsesInvocationException
        ) {
            throw new LogicException('OpenAI Responses diagnostic and exception type do not match.');
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

    /** @param list<string> $allowed */
    final protected static function safeToken(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
