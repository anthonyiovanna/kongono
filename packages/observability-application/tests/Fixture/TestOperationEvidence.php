<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\ObservabilityApplication\ApplicationObservabilityOperation;

final class TestOperationEvidence
{
    public int $calls = 0;
    public ?ApplicationObservabilityOperation $operation = null;
}
