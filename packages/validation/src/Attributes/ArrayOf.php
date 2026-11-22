<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;
use Kongono\Core\CoreException;

/**
 * Validate and hydrate every item of an `array` DTO property into a target scalar type,
 * `BackedEnum`, or nested DTO class (e.g. `#[ArrayOf(OrderItemInput::class)]`).
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class ArrayOf
{
    /**
     * @param 'string'|'int'|'float'|'bool'|class-string $type     Target element type, BackedEnum, or nested DTO class
     * @param list<object>                               $rules    Optional per-item validation attributes (e.g. `[new Email()]`)
     * @param int                                        $maxItems Upper bound protecting persistent workers from large-array DoS
     */
    public function __construct(
        public string $type,
        public array $rules = [],
        public int $maxItems = 1000,
        public ?string $message = null,
    ) {
        if ($this->maxItems < 1) {
            throw new CoreException(sprintf('ArrayOf maxItems must be >= 1 (got %d).', $this->maxItems));
        }
    }
}
