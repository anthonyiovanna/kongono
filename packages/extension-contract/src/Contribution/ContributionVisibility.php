<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Contribution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes contribution eligibility without granting authorization or declassification. */
#[Api(ApiStatus::Experimental)]
enum ContributionVisibility: string
{
    case Public = 'public';
    case Application = 'application';
    case FrameworkInternal = 'framework_internal';
}
