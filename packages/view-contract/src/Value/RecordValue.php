<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Schema\PresentationValueKind;

/** Carries a canonical bounded record of closed presentation values. */
#[Api(ApiStatus::Experimental)]
final readonly class RecordValue implements PresentationValue
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

    public function kind(): PresentationValueKind
    {
        return PresentationValueKind::Record;
    }

    public function equals(PresentationValue $other): bool
    {
        if (!$other instanceof self || array_keys($this->fields) !== array_keys($other->fields)) {
            return false;
        }
        foreach ($this->fields as $name => $value) {
            if (!$value->equals($other->fields[$name])) {
                return false;
            }
        }

        return true;
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
