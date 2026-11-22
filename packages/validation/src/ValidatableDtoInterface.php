<?php

declare(strict_types=1);

namespace Kongono\Validation;

/**
 * Optional cross-field validation hook on typed DTOs.
 *
 * Executed automatically by {@see Validator} immediately after every field has been coerced
 * and attribute-validated. Return a map of `field => message` or `field => list<string>`
 * errors, or `[]` when the DTO is valid.
 */
interface ValidatableDtoInterface
{
    /** @return array<string, string|list<string>> */
    public function validate(): array;
}
