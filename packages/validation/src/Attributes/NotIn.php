<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;
use Kongono\Validation\RuleInterface;

/** Reject values matching a disallowed list (strict comparison). */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class NotIn implements RuleInterface
{
    /** @param list<string|int|float|bool> $disallowed */
    public function __construct(
        public array $disallowed,
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, string $field, array $allInput): ?string
    {
        if (in_array($value, $this->disallowed, true)) {
            return $this->message ?? sprintf('The selected %s value is not allowed.', $field);
        }

        return null;
    }
}
