<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;
use DateTimeImmutable;
use DateTimeZone;
use Kongono\Validation\RuleInterface;
use Throwable;

/**
 * Validate calendar-valid ISO-8601 dates/datetimes (or an explicit PHP DateTime format)
 * with optional `after` and `before` bounds (literal dates, `today`/`now`, or sibling field names).
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Date implements RuleInterface
{
    public function __construct(
        public ?string $format = null,
        public ?string $after = null,
        public ?string $before = null,
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, string $field, array $allInput): ?string
    {
        if (!is_string($value) || 1 === preg_match('/[\x00-\x1f\x7f]/', $value)) {
            return $this->message ?? sprintf('The %s field must be a valid date.', $field);
        }

        $parsed = $this->parseDate($value);
        if (null === $parsed) {
            return $this->message ?? (null !== $this->format
                ? sprintf('The %s field must match the date format %s.', $field, $this->format)
                : sprintf('The %s field must be a valid ISO-8601 date.', $field));
        }

        if (null !== $this->after) {
            $afterDate = $this->resolveBound($this->after, $allInput);
            if (null === $afterDate || $parsed <= $afterDate) {
                return $this->message ?? sprintf('The %s field must be a date after %s.', $field, $this->after);
            }
        }

        if (null !== $this->before) {
            $beforeDate = $this->resolveBound($this->before, $allInput);
            if (null === $beforeDate || $parsed >= $beforeDate) {
                return $this->message ?? sprintf('The %s field must be a date before %s.', $field, $this->before);
            }
        }

        return null;
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');

        if (null !== $this->format) {
            $fmt = ltrim($this->format, '!');
            $dt = DateTimeImmutable::createFromFormat('!' . $fmt, $value, $utc);
            $errors = DateTimeImmutable::getLastErrors();
            if (false === $dt) {
                return null;
            }
            if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                return null;
            }

            return $dt;
        }

        return self::parseIsoDate($value);
    }

    private static function parseIsoDate(string $value): ?DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');

        if (1 === preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/', $value, $m)) {
            if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return null;
            }

            $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $utc);

            return false !== $dt ? $dt : null;
        }

        if (1 === preg_match('/\A(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2})(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:\d{2})?\z/', $value, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
            $hour = (int) $m[4];
            $minute = (int) $m[5];
            $second = isset($m[6]) ? (int) $m[6] : 0;

            if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
                return null;
            }

            try {
                return new DateTimeImmutable($value, $utc);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $allInput */
    private function resolveBound(string $bound, array $allInput): ?DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        $trimmedBound = trim($bound);
        $lowerBound = strtolower($trimmedBound);

        // 1. Always resolve reserved keywords (`today`, `now`) before user input so injected fields cannot shadow them
        if ('today' === $lowerBound) {
            return new DateTimeImmutable('today', $utc);
        }
        if ('now' === $lowerBound) {
            return new DateTimeImmutable('now', $utc);
        }

        // 2. Always resolve literal calendar/ISO date bounds (both configured format AND ISO-8601) before user input
        $literal = $this->parseDate($trimmedBound) ?? self::parseIsoDate($trimmedBound);
        if (null !== $literal) {
            return $literal;
        }

        // 3. Only treat `$bound` as a sibling field name when it is a valid identifier (prevents date-literal fallback to input keys)
        if (1 !== preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $bound) || !array_key_exists($bound, $allInput)) {
            return null;
        }

        $sibling = $allInput[$bound];
        if (!is_string($sibling) || '' === trim($sibling)) {
            return null;
        }

        return $this->parseDate(trim($sibling));
    }
}
