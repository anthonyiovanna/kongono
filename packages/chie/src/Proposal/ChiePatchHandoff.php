<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;

/** Records that a human must apply the candidate before a fresh Hantei judgment. */
#[Api(ApiStatus::Experimental)]
final readonly class ChiePatchHandoff
{
    public function __construct(private string $expectedTargetSha256)
    {
        if (1 !== preg_match('/^[A-F0-9]{64}$/D', $expectedTargetSha256)) {
            throw InvalidChieValueException::proposal();
        }
    }

    /** @return array{state: string, required_profile: string, expected_target_sha256: string, requires_fresh_hantei: true} */
    public function toArray(): array
    {
        return [
            'state' => 'pending-human-application',
            'required_profile' => 'framework-quality-v1',
            'expected_target_sha256' => $this->expectedTargetSha256,
            'requires_fresh_hantei' => true,
        ];
    }

    public function expectedTargetSha256(): string
    {
        return $this->expectedTargetSha256;
    }
}
