<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Retains one validated text, integer, or boolean result scalar without coercion. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResultValue
{
    private function __construct(
        private CommandResultValueKind $kind,
        private string|int|bool $value,
    ) {}

    public static function text(string $value): self
    {
        if (
            strlen($value) > 8192
            || 1 !== preg_match('//u', $value)
            || str_starts_with($value, "\xEF\xBB\xBF")
            || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value)
        ) {
            throw InvalidConsoleValueException::result();
        }

        return new self(CommandResultValueKind::Text, $value);
    }

    public static function integer(int $value): self
    {
        return new self(CommandResultValueKind::Integer, $value);
    }

    public static function boolean(bool $value): self
    {
        return new self(CommandResultValueKind::Boolean, $value);
    }

    public function kind(): CommandResultValueKind
    {
        return $this->kind;
    }

    public function value(): string|int|bool
    {
        return $this->value;
    }
}
