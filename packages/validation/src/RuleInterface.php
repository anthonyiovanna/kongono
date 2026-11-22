<?php

declare(strict_types=1);

namespace Kongono\Validation;

/**
 * Contract for custom (or built-in) PHP 8 validation rule attributes.
 *
 * Any user-defined PHP 8 attribute implementing `RuleInterface` is automatically discovered
 * and evaluated by {@see Validator} with zero manual registration.
 */
interface RuleInterface
{
    /**
     * Validate `$value` for `$field`. Return `null` when valid, or an error message string when invalid.
     *
     * @param array<string, mixed> $allInput Full raw input array for cross-field inspection
     */
    public function validate(mixed $value, string $field, array $allInput): ?string;
}
