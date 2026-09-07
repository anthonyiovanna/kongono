<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\InvalidModelInvocationApplicationProfileException;
use Kongono\ModelInvocationApplication\Internal\CanonicalProfile;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactValidator;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Throwable;

/** Delegates exact-current proof to Phase 14C and binds one immutable profile. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationApplicationProfileFactory
{
    public function create(InvocationPlanSource $source, string $artifactBytes): ModelInvocationApplicationProfile
    {
        try {
            $artifact = (new InvocationPlanArtifactValidator())->validateCurrent($source, $artifactBytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidModelInvocationApplicationProfileException::invalid('artifact_mismatch', $failure);
        }
        if (!$artifact->planIdentity()->equals($source->identity())
            || !$artifact->adapterIdentity()->equals($source->adapterIdentity())
            || !$artifact->invocationIdentity()->equals($source->request()->identity())
        ) {
            throw InvalidModelInvocationApplicationProfileException::invalid('artifact_identity_mismatch');
        }
        $identity = new ModelInvocationApplicationProfileIdentity(hash('sha256', CanonicalProfile::bytes($source, $artifact)));

        return new ModelInvocationApplicationProfile($source, $artifact, $identity);
    }
}
