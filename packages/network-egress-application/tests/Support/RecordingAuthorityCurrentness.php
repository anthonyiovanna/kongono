<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Support;

use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;

final class RecordingAuthorityCurrentness implements EgressAuthorityCurrentness
{
    public int $calls = 0;
    public ?EgressAuthorityReference $reference = null;
    public ?CanonicalOrigin $origin = null;

    /** @param non-empty-list<bool> $answers */
    public function __construct(private array $answers = [true, true]) {}

    public function isCurrent(EgressAuthorityReference $reference, CanonicalOrigin $origin): bool
    {
        $this->reference = $reference;
        $this->origin = $origin;
        $position = min($this->calls, count($this->answers) - 1);
        ++$this->calls;

        return $this->answers[$position];
    }
}
