<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Profile\ViewApplicationProfile;

/** Carries exactly one explicit already exact-current view-application profile. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewInspectionRequest
{
    public function __construct(private ViewApplicationProfile $profile) {}

    public function profile(): ViewApplicationProfile
    {
        return $this->profile;
    }
}
