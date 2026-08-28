<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeSet;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventSchemaException;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventValueException;

/** Canonicalizes and validates one zero-to-thirty-field event schema. */
#[Api(ApiStatus::Experimental)]
final readonly class OperationalEventSchema
{
    /** @var list<OperationalEventField> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(array $fields)
    {
        if (!array_is_list($fields) || count($fields) > 30) {
            throw InvalidOperationalEventSchemaException::schema();
        }
        $canonical = [];
        $seen = [];
        foreach ($fields as $field) {
            if (!$field instanceof OperationalEventField || isset($seen[$field->key()->value()])) {
                throw InvalidOperationalEventSchemaException::schema();
            }
            $seen[$field->key()->value()] = true;
            $canonical[] = $field;
        }
        usort($canonical, static fn(OperationalEventField $left, OperationalEventField $right): int => strcmp($left->key()->value(), $right->key()->value()));
        $this->fields = $canonical;
    }

    /** @return list<OperationalEventField> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function validate(AttributeSet $attributes): DiagnosticDisclosure
    {
        $supplied = [];
        foreach ($attributes->attributes() as $attribute) {
            $supplied[$attribute->key()->value()] = $attribute->value();
        }
        $disclosure = DiagnosticDisclosure::Safe;
        foreach ($this->fields as $field) {
            $key = $field->key()->value();
            $value = $supplied[$key] ?? null;
            if (null === $value) {
                if ($field->required()) {
                    throw InvalidOperationalEventValueException::attributes();
                }
                continue;
            }
            unset($supplied[$key]);
            if ($value->kind() !== $field->kind() || !$field->matches($value)) {
                throw InvalidOperationalEventValueException::attributes();
            }
            if ($field->disclosure() === DiagnosticDisclosure::Protected) {
                $disclosure = DiagnosticDisclosure::Protected;
            }
        }
        if ([] !== $supplied) {
            throw InvalidOperationalEventValueException::attributes();
        }

        return $disclosure;
    }
}
