<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;

final class FakeAuthorityCurrentness implements EgressAuthorityCurrentness
{
    public int $calls = 0;

    /** @param non-empty-list<bool> $answers */
    public function __construct(private array $answers = [true, true]) {}

    public function isCurrent(EgressAuthorityReference $reference, CanonicalOrigin $origin): bool
    {
        $position = min($this->calls, count($this->answers) - 1);
        ++$this->calls;

        return $this->answers[$position];
    }
}
