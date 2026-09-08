<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;

final class FakeResolver implements EgressResolver
{
    public int $calls = 0;
    public ?CanonicalOrigin $origin = null;

    public function __construct(private EgressResolutionResult $result) {}

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        ++$this->calls;
        $this->origin = $origin;

        return $this->result;
    }
}
