<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeSet;
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditSchemaException;
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditValueException;

/** Canonicalizes and validates one one-to-twenty-eight-field audit schema. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityAuditSchema
{
    /** @var list<SecurityAuditField> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(array $fields)
    {
        if (!array_is_list($fields) || count($fields) < 1 || count($fields) > 28) {
            throw InvalidSecurityAuditSchemaException::schema();
        }
        $canonical = [];
        $seen = [];
        foreach ($fields as $field) {
            if (!$field instanceof SecurityAuditField || isset($seen[$field->key()->value()])) {
                throw InvalidSecurityAuditSchemaException::schema();
            }
            $seen[$field->key()->value()] = true;
            $canonical[] = $field;
        }
        usort($canonical, static fn(SecurityAuditField $left, SecurityAuditField $right): int => strcmp($left->key()->value(), $right->key()->value()));
        $this->fields = $canonical;
    }

    /** @return list<SecurityAuditField> */
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
                    throw InvalidSecurityAuditValueException::attributes();
                }
                continue;
            }
            unset($supplied[$key]);
            if ($value->kind() !== $field->kind() || !$field->matches($value)) {
                throw InvalidSecurityAuditValueException::attributes();
            }
            if ($field->disclosure() === DiagnosticDisclosure::Protected) {
                $disclosure = DiagnosticDisclosure::Protected;
            }
        }
        if ([] !== $supplied) {
            throw InvalidSecurityAuditValueException::attributes();
        }

        return $disclosure;
    }
}
