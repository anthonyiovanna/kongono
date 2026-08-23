<?php

declare(strict_types=1);

namespace Kongono\Hantei\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\HanteiCheckOutcome;
use Kongono\Hantei\HanteiVerdict;

/** Applies the fixed FAIL-before-BLOCKED verdict precedence to complete evidence. */
#[Api(ApiStatus::Internal)]
final class VerdictEvaluator
{
    /** @param list<HanteiCheckEvidence> $checks */
    public function evaluate(array $checks): HanteiVerdict
    {
        foreach ($checks as $check) {
            if (HanteiCheckOutcome::Fail === $check->outcome()) {
                return HanteiVerdict::Fail;
            }
        }
        foreach ($checks as $check) {
            if (HanteiCheckOutcome::Blocked === $check->outcome()) {
                return HanteiVerdict::Blocked;
            }
        }

        return HanteiVerdict::Pass;
    }
}
