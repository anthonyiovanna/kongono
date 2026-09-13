<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Workflow\CurrentViewApplicationBuild;

/** Carries one exact-current compiled-view application profile as inert evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewApplicationProfile
{
    public function __construct(private CurrentViewApplicationBuild $currentBuild, private ViewApplicationProfileIdentity $identity) {}

    public function currentBuild(): CurrentViewApplicationBuild
    {
        return $this->currentBuild;
    }
    public function identity(): ViewApplicationProfileIdentity
    {
        return $this->identity;
    }
}
