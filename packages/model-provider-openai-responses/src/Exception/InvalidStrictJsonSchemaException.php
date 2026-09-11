<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use RuntimeException;

/** Reports a closed strict-schema violation without retaining rejected values. */
#[Api(ApiStatus::Experimental)]
final class InvalidStrictJsonSchemaException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    private function __construct(string $violation, ?string $field = null, ?int $limit = null, ?int $count = null)
    {
        $values = [
            'violation' => self::token($violation, [
                'source_bytes', 'utf8', 'json_syntax', 'json_depth', 'duplicate_member', 'root_object',
                'node_object', 'node_count', 'logical_depth', 'type_required', 'type_unsupported',
                'object_members', 'description', 'properties', 'property_count', 'property_name',
                'required', 'required_count', 'required_relation', 'additional_properties', 'items',
                'enum', 'enum_count', 'enum_duplicate', 'enum_type', 'value_depth', 'value_count',
                'value_type', 'value_properties', 'value_enum',
            ], 'unsupported_violation'),
            'mapping_version' => 'version_2',
        ];
        if (null !== $field) {
            $values['field'] = self::token($field, ['schema', 'type', 'description', 'properties', 'required', 'additional_properties', 'items', 'enum', 'value'], 'unsupported_field');
        }
        if (null !== $limit) {
            $values['limit'] = max(0, $limit);
        }
        if (null !== $count) {
            $values['entry_count'] = max(0, $count);
        }

        $diagnostic = OpenAiResponsesDiagnostic::InvalidStrictJsonSchema;
        $this->diagnosticContext = $diagnostic->context($values);
        parent::__construct($diagnostic->title() . '.');
    }

    public static function violation(string $violation, ?string $field = null, ?int $limit = null, ?int $count = null): self
    {
        return new self($violation, $field, $limit, $count);
    }

    public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }

    public function diagnosticId(): string
    {
        return OpenAiResponsesDiagnostic::InvalidStrictJsonSchema->value;
    }

    public function diagnosticTitle(): string
    {
        return OpenAiResponsesDiagnostic::InvalidStrictJsonSchema->title();
    }

    public function diagnosticRemediation(): string
    {
        return OpenAiResponsesDiagnostic::InvalidStrictJsonSchema->remediation();
    }

    public function __toString(): string
    {
        $context = [];
        foreach ($this->diagnosticContext->fields() as $field) {
            $context[] = $field->key() . '=' . $field->value();
        }

        return sprintf(
            '%s: [%s] %s. Remediation: %s. Context: %s',
            self::class,
            $this->diagnosticId(),
            $this->diagnosticTitle(),
            $this->diagnosticRemediation(),
            implode(', ', $context),
        );
    }

    /** @param list<string> $allowed */
    private static function token(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
