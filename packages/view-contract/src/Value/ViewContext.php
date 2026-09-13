<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;

/** Owns the closed root record supplied to a render request. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewContext
{
    /** @var array<string, PresentationValue> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(array $fields)
    {
        $this->fields = ContractGuard::valueRecord($fields, 128);
        ContractGuard::valueTree($this->depth(), $this->nodeCount());
    }

    /** @return array<string, PresentationValue> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function equals(self $other): bool
    {
        return (new RecordValue($this->fields))->equals(new RecordValue($other->fields));
    }

    public function depth(): int
    {
        $depth = 0;
        foreach ($this->fields as $value) {
            $depth = max($depth, $value->depth());
        }

        return 1 + $depth;
    }

    public function nodeCount(): int
    {
        $count = 1;
        foreach ($this->fields as $value) {
            $count += $value->nodeCount();
        }

        return $count;
    }
}
