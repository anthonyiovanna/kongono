<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Schema\PresentationValueKind;

/** Carries an ordered bounded list of closed presentation values. */
#[Api(ApiStatus::Experimental)]
final readonly class ListValue implements PresentationValue
{
    /** @var list<PresentationValue> */
    private array $values;

    /** @param array<array-key, mixed> $values */
    public function __construct(array $values)
    {
        $this->values = ContractGuard::valueList($values, 256);
        ContractGuard::valueTree($this->depth(), $this->nodeCount());
    }

    /** @return list<PresentationValue> */
    public function values(): array
    {
        return $this->values;
    }

    public function kind(): PresentationValueKind
    {
        return PresentationValueKind::List;
    }

    public function equals(PresentationValue $other): bool
    {
        if (!$other instanceof self || count($this->values) !== count($other->values)) {
            return false;
        }
        foreach ($this->values as $position => $value) {
            if (!$value->equals($other->values[$position])) {
                return false;
            }
        }

        return true;
    }

    public function depth(): int
    {
        $depth = 0;
        foreach ($this->values as $value) {
            $depth = max($depth, $value->depth());
        }

        return 1 + $depth;
    }

    public function nodeCount(): int
    {
        $count = 1;
        foreach ($this->values as $value) {
            $count += $value->nodeCount();
        }

        return $count;
    }
}
