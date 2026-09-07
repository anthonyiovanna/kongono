<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Internal\GeneratedWiringEmitter;
use Kongono\ModelInvocationApplication\Internal\GeneratedWiringImplementation;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;

/** Generates deterministic inert PHP for one exact application profile. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationWiringGenerator
{
    public const int MAX_GENERATED_BYTES = 1_048_576;

    public function generate(ModelInvocationApplicationProfile $profile): GeneratedModelInvocationWiring
    {
        $identity = $profile->identity()->value();
        $source = $profile->source();
        $modelProfile = $source->request()->profile();
        $artifact = $profile->artifact();
        $namespace = 'KongonoGenerated\\ModelInvocation\\V1\\P' . $identity;
        $metadata = [
            'schema' => 'kongono.model-invocation-application.generated-wiring',
            'version' => 1,
            'contract_version' => $source->request()->version()->value,
            'application_profile_identity' => $identity,
            'plan_identity' => $source->identity()->value(),
            'invocation_identity' => $source->request()->identity()->value(),
            'model_profile_identity' => $modelProfile->identity()->value(),
            'provider_identity' => $modelProfile->providerIdentity()->value(),
            'model_identity' => $modelProfile->modelIdentity()->value(),
            'adapter_identity' => $source->adapterIdentity()->value(),
            'artifact_sha256' => $artifact->artifactSha256(),
            'source_sha256' => $artifact->sourceSha256(),
            'request_sha256' => $artifact->requestSha256(),
            'generator_implementation_sha256' => GeneratedWiringImplementation::sha256(),
            'generated_namespace' => $namespace,
            'generated_class' => $namespace . '\\ModelInvocationApplicationBoundary',
            'generated_interface' => GeneratedModelInvocationApplicationBoundary::class,
        ];

        return (new ModelInvocationWiringValidator())->validateStructure(GeneratedWiringEmitter::emit($metadata));
    }
}
