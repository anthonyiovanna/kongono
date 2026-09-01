<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Support;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentRuntime\Authority\AgentAuthorityCurrentness;
use Throwable;

final class RecordingAuthorityCurrentness implements AgentAuthorityCurrentness
{
    public int $calls = 0;
    /** @param non-empty-list<bool|Throwable> $observations */
    public function __construct(private array $observations = [true, true]) {}

    public function isCurrent(AgentAuthorityPlan $plan, OperationReference $operation): bool
    {
        unset($plan, $operation);
        $value = $this->observations[min($this->calls, count($this->observations) - 1)];
        ++$this->calls;
        if ($value instanceof Throwable) {
            throw $value;
        }
        return $value;
    }
}
