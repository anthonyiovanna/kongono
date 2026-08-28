<?php

declare(strict_types=1);

namespace KongonoObservabilityMinimal;

use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;

/** Records this one bounded example operation; it is not a production sampling policy. */
final readonly class RecordTraceSampler implements TraceSampler
{
    public function decide(TraceSamplingRequest $request): TraceSamplingDecision
    {
        unset($request);

        return TraceSamplingDecision::Record;
    }
}
