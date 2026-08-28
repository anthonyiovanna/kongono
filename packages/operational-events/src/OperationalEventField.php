<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventSchemaException;
use Kongono\OperationalEvents\Internal\OperationalEventValidation;

/** Declares one exact bounded structured operational-event field domain. */
#[Api(ApiStatus::Experimental)]
final readonly class OperationalEventField
{
    /** @var list<AttributeValue> */
    private array $allowedValues;

    /** @param array<array-key, mixed> $allowedValues */
    public function __construct(
        private AttributeKey $key,
        private AttributeValueKind $kind,
        private DiagnosticDisclosure $disclosure,
        private bool $required,
        array $allowedValues,
    ) {
        if (!OperationalEventValidation::isFieldKeyAllowed($key->value()) || !array_is_list($allowedValues) || count($allowedValues) < 1 || count($allowedValues) > 16) {
            throw InvalidOperationalEventSchemaException::field();
        }

        $canonical = [];
        foreach ($allowedValues as $value) {
            if (!$value instanceof AttributeValue || !OperationalEventValidation::isDomainValueAllowed($value, $kind)) {
                throw InvalidOperationalEventSchemaException::field();
            }
            foreach ($canonical as $existing) {
                if ($value->equals($existing)) {
                    throw InvalidOperationalEventSchemaException::field();
                }
            }
            $canonical[] = $value;
        }
        usort($canonical, self::compareValues(...));
        $this->allowedValues = $canonical;
    }

    public function key(): AttributeKey
    {
        return $this->key;
    }

    public function kind(): AttributeValueKind
    {
        return $this->kind;
    }

    public function disclosure(): DiagnosticDisclosure
    {
        return $this->disclosure;
    }

    public function required(): bool
    {
        return $this->required;
    }

    /** @return list<AttributeValue> */
    public function allowedValues(): array
    {
        return $this->allowedValues;
    }

    public function matches(AttributeValue $value): bool
    {
        foreach ($this->allowedValues as $allowed) {
            if ($allowed->equals($value)) {
                return true;
            }
        }

        return false;
    }

    private static function compareValues(AttributeValue $left, AttributeValue $right): int
    {
        $order = ['boolean' => 0, 'integer' => 1, 'floating_point' => 2, 'text' => 3];
        $kind = $order[$left->kind()->value] <=> $order[$right->kind()->value];

        return 0 !== $kind ? $kind : match ($left->kind()) {
            AttributeValueKind::Boolean, AttributeValueKind::Integer, AttributeValueKind::FloatingPoint => $left->value() <=> $right->value(),
            AttributeValueKind::Text => strcmp((string) $left->value(), (string) $right->value()),
        };
    }
}
