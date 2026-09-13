<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Profile\ViewApplicationProfileIdentity;
use Kongono\ViewApplication\Workflow\CurrentViewApplicationBuild;

/** Derives profile identity only from accepted source and artifact evidence. */
#[Api(ApiStatus::Internal)]
final class CanonicalViewApplicationProfile
{
    public static function identity(CurrentViewApplicationBuild $current): ViewApplicationProfileIdentity
    {
        return new ViewApplicationProfileIdentity(hash('sha256', implode("\n", [
            'kongono.view-application-profile',
            '1',
            $current->sourceIdentity()->value(),
            $current->compiledViewIdentity()->value(),
            $current->modelSha256(),
            $current->generatedPhpSha256(),
            (string) $current->viewCount(),
        ])));
    }

    private function __construct() {}
}
