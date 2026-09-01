<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Capability;

use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\AgentRuntime\Internal\RuntimeGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Retains only a hard-bounded opaque capability result and evidence digest. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentCapabilityResult
{
    private const int MAX_OUTPUT_BYTES = 1_048_576;
    private string $evidenceSha256;

    public function __construct(
        private AgentCapabilityOutcome $outcome,
        private string $outputBytes,
        string $evidenceSha256,
    ) {
        if (strlen($outputBytes) > self::MAX_OUTPUT_BYTES) {
            throw InvalidAgentRuntimeException::invalid('capability_output_limit');
        }
        $this->evidenceSha256 = RuntimeGuard::digest($evidenceSha256);
    }

    public function outcome(): AgentCapabilityOutcome
    {
        return $this->outcome;
    }
    public function outputBytes(): string
    {
        return $this->outputBytes;
    }
    public function evidenceSha256(): string
    {
        return $this->evidenceSha256;
    }
}
