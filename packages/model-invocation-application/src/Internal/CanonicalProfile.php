<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifact;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;

/** Produces a versioned LF-terminated projection of bounded identity evidence. */
#[Api(ApiStatus::Internal)]
final class CanonicalProfile
{
    public static function bytes(InvocationPlanSource $source, InvocationPlanArtifact $artifact): string
    {
        $profile = $source->request()->profile();
        $claims = [
            ['application_profile_version', '1'],
            ['plan_identity', $source->identity()->value()],
            ['invocation_identity', $source->request()->identity()->value()],
            ['model_profile_identity', $profile->identity()->value()],
            ['provider_identity', $profile->providerIdentity()->value()],
            ['model_identity', $profile->modelIdentity()->value()],
            ['adapter_identity', $source->adapterIdentity()->value()],
            ['artifact_sha256', $artifact->artifactSha256()],
            ['source_sha256', $artifact->sourceSha256()],
            ['request_sha256', $artifact->requestSha256()],
        ];
        foreach ($profile->capabilities()->capabilities() as $position => $capability) {
            $claims[] = ['capability.' . $position, $capability->value];
        }

        $bytes = '';
        foreach ($claims as [$name, $value]) {
            $bytes .= strlen($name) . ':' . $name . ':' . strlen($value) . ':' . $value . "\n";
        }

        return $bytes;
    }

    private function __construct() {}
}
