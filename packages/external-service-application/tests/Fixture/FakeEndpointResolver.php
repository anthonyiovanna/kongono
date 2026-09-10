<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;

final class FakeEndpointResolver implements EndpointResolver
{
    public int $calls = 0;
    public ?CanonicalOrigin $origin = null;

    public function __construct(private EgressResolutionResult $result, private ?Closure $observer = null) {}

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        ++$this->calls;
        $this->origin = $origin;
        if (null !== $this->observer) {
            ($this->observer)('resolver.resolve');
        }

        return $this->result;
    }
}
