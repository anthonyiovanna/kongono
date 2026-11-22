<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;

/**
 * Conditionally require a nullable/optional field when another input field matches
 * a target value, belongs to a list (`in: [...]`), or is non-empty/truthy.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class RequiredIf
{
    /** @param ?list<mixed> $in */
    public function __construct(
        public string $otherField,
        public mixed $equals = null,
        public ?array $in = null,
        public ?string $message = null,
    ) {}

    /** @param array<string, mixed> $allInput */
    public function isTriggered(array $allInput): bool
    {
        $present = array_key_exists($this->otherField, $allInput);
        $actual = $present ? $allInput[$this->otherField] : null;

        if (null !== $this->in) {
            foreach ($this->in as $candidate) {
                if ($this->valuesMatch($actual, $candidate)) {
                    return true;
                }
            }

            return false;
        }

        if (null !== $this->equals) {
            return $this->valuesMatch($actual, $this->equals);
        }

        if (!$present || null === $actual || false === $actual || 0 === $actual || [] === $actual) {
            return false;
        }
        if (is_string($actual)) {
            $trimmed = strtolower(trim($actual));

            return '' !== $trimmed && '0' !== $trimmed && 'false' !== $trimmed && 'no' !== $trimmed && 'off' !== $trimmed;
        }

        return true;
    }

    private function valuesMatch(mixed $actual, mixed $expected): bool
    {
        if ($actual === $expected) {
            return true;
        }

        if (is_bool($expected) && is_string($actual)) {
            $norm = strtolower(trim($actual));
            if ($expected && in_array($norm, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (!$expected && in_array($norm, ['0', 'false', 'no', 'off'], true)) {
                return true;
            }
        }

        if (is_int($expected) && is_string($actual)) {
            $trimmed = trim($actual);
            if (1 === preg_match('/\A-?\d+\z/', $trimmed) && (int) $trimmed === $expected) {
                return true;
            }
        }

        if (is_string($expected) && is_string($actual) && trim($actual) === $expected) {
            return true;
        }

        return false;
    }
}
