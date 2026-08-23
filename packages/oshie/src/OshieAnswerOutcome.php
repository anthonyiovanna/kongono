<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the two deterministic exact-answer outcomes. */
#[Api(ApiStatus::Experimental)]
enum OshieAnswerOutcome: string
{
    case Correct = 'correct';
    case Incorrect = 'incorrect';
}
