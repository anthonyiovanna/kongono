<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifact;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactValidator;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidGeneratedExternalServiceBindingsException;
use Kongono\ExternalServiceBindingBuild\Internal\BindingArtifactCodec;
use Kongono\ExternalServiceBindingBuild\Internal\GeneratedBindingsEmitter;
use Kongono\ExternalServiceBindingBuild\Internal\GeneratedBindingsImplementation;
use Throwable;

/** Generates deterministic inert ordinary PHP for one validated binding artifact. */
#[Api(ApiStatus::Experimental)]
final class GeneratedExternalServiceBindingsGenerator
{
    public function generate(ExternalServiceBindingArtifact $artifact): GeneratedExternalServiceBindings
    {
        try {
            $validated = (new ExternalServiceBindingArtifactValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedExternalServiceBindingsException::invalid('artifact_mismatch', previous: $failure);
        }
        if (
            !hash_equals($validated->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($validated->sourceSha256(), $artifact->sourceSha256())
            || !$validated->source()->equals($artifact->source())
        ) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('artifact_mismatch');
        }
        $metadata = [
            'schema' => 'kongono.external-service-bindings.generated-php',
            'version' => 1,
            'contract_version' => 1,
            'generator' => [
                'package' => 'kongono/external-service-binding-build',
                'version' => 1,
                'implementation_sha256' => GeneratedBindingsImplementation::sha256(),
            ],
            'source_identity' => $validated->sourceIdentity()->value(),
            'source_sha256' => $validated->sourceSha256(),
            'binding_artifact_sha256' => $validated->artifactSha256(),
            'bindings' => BindingArtifactCodec::sourceData($validated->source())['bindings'],
        ];
        $bytes = GeneratedBindingsEmitter::emit($metadata, $validated->bindings());

        return (new GeneratedExternalServiceBindingsValidator())->validateStructure($bytes);
    }
}
