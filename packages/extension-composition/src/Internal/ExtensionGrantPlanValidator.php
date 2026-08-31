<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;
use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;

/** Enforces exact list, type, bound, uniqueness, ordering, parameter, and symbol grammar. */
#[Api(ApiStatus::Internal)]
final class ExtensionGrantPlanValidator
{
    /**
     * @param array<array-key, mixed> $values
     * @return list<TrustedExtensionImplementation>
     */
    public static function implementations(array $values): array
    {
        if (!array_is_list($values)) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_not_list', count($values));
        }
        if (count($values) > 64) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_count', count($values));
        }
        $accepted = [];
        $previous = null;
        foreach ($values as $position => $value) {
            if (!$value instanceof TrustedExtensionImplementation) {
                throw InvalidExtensionGrantPlanException::invalid('invalid_implementation_entry', count($values), $position);
            }
            $key = $value->identity()->value();
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                throw InvalidExtensionGrantPlanException::invalid($previous === $key ? 'duplicate_implementation' : 'noncanonical_implementation_order', count($values), $position);
            }
            $previous = $key;
            $accepted[] = $value;
        }

        return $accepted;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<CapabilityDecision>
     */
    public static function decisions(array $values): array
    {
        if (!array_is_list($values)) {
            throw InvalidExtensionGrantPlanException::invalid('decision_not_list', count($values));
        }
        if (count($values) > 4096) {
            throw InvalidExtensionGrantPlanException::invalid('decision_count', count($values));
        }
        $accepted = [];
        $previous = null;
        $parameters = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof CapabilityDecision) {
                throw InvalidExtensionGrantPlanException::invalid('invalid_decision_entry', count($values), $position);
            }
            $key = self::decisionKey($value);
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                throw InvalidExtensionGrantPlanException::invalid($previous === $key ? 'duplicate_decision' : 'noncanonical_decision_order', count($values), $position);
            }
            $parameterKey = $value->extensionIdentity()->value() . "\0" . $value->constructorParameter();
            if (isset($parameters[$parameterKey])) {
                throw InvalidExtensionGrantPlanException::invalid('duplicate_constructor_parameter', count($values), $position);
            }
            $parameters[$parameterKey] = true;
            $previous = $key;
            $accepted[] = $value;
        }

        return $accepted;
    }

    public static function parameter(string $value): void
    {
        if (strlen($value) > 128 || 1 !== preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $value)) {
            throw InvalidExtensionGrantPlanException::invalid('constructor_parameter_grammar');
        }
    }

    public static function symbol(string $value): void
    {
        if (strlen($value) > 255 || 1 !== preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+\z/D', $value)) {
            throw InvalidExtensionGrantPlanException::invalid('symbol_grammar');
        }
    }

    public static function decisionKey(CapabilityDecision $decision): string
    {
        return implode("\0", [
            $decision->extensionIdentity()->value(),
            $decision->capabilityIdentity()->value(),
            (string) $decision->capabilityIdentity()->version(),
            $decision->purpose()->value(),
            $decision->requirement()->value,
        ]);
    }

    private function __construct() {}
}
