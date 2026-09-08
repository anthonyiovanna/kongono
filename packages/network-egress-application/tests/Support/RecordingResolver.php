<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Support;

use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;

final class RecordingResolver implements EgressResolver
{
    public int $calls = 0;
    public ?CanonicalOrigin $origin = null;

    public function __construct(private EgressResolutionResult $result, private mixed $onResolve = null) {}

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        ++$this->calls;
        $this->origin = $origin;
        if (is_callable($this->onResolve)) {
            ($this->onResolve)();
        }

        return $this->result;
    }
}
