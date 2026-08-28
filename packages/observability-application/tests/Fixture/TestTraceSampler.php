<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;

final class TestTraceSampler implements TraceSampler
{
    public function decide(TraceSamplingRequest $request): TraceSamplingDecision
    {
        unset($request);

        return TraceSamplingDecision::Record;
    }
}
