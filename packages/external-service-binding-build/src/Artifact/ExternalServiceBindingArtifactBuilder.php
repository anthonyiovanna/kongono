<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Internal\BindingArtifactCodec;
use Kongono\ExternalServiceBindingBuild\Internal\CanonicalJson;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Version\ExternalServiceBindingArtifactVersion;

/** Builds canonical inert binding evidence from one explicit typed source. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceBindingArtifactBuilder
{
    public function build(ExternalServiceBindingSource $source): ExternalServiceBindingArtifact
    {
        try {
            $sourceData = BindingArtifactCodec::sourceData($source);
            $sourceSha256 = hash('sha256', CanonicalJson::encode([
                'contract_version' => 1,
                'source' => $sourceData,
            ]));
            $bytes = CanonicalJson::encode([
                'schema' => 'kongono.external-service-binding-artifact',
                'artifact_version' => ExternalServiceBindingArtifactVersion::V1->value,
                'contract_version' => 1,
                'builder' => ['package' => 'kongono/external-service-binding-build', 'version' => 1],
                'source' => $sourceData,
                'source_sha256' => $sourceSha256,
            ]);
        } catch (JsonException $failure) {
            throw InvalidExternalServiceBindingArtifactException::invalid('encoding_failed', previous: $failure);
        }
        if (strlen($bytes) > CanonicalJson::MAX_ARTIFACT_BYTES) {
            throw InvalidExternalServiceBindingArtifactException::limitExceeded(strlen($bytes), CanonicalJson::MAX_ARTIFACT_BYTES);
        }

        return new ExternalServiceBindingArtifact($bytes, hash('sha256', $bytes), $sourceSha256, $source);
    }
}
