<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Build\MappingArtifactValidator;
use Kongono\DatabaseMapping\Exception\InvalidGeneratedDataAccessException;
use Kongono\DatabaseMapping\Internal\CanonicalJson;
use Kongono\DatabaseMapping\Internal\GeneratedDataAccessEmitter;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use Kongono\DatabaseMapping\Internal\MappingImplementation;

/** Generates deterministic typed data access from one exact current artifact. */
#[Api(ApiStatus::Experimental)]
final class GeneratedDataAccessGenerator
{
    public const MAX_GENERATED_BYTES = 16_777_216;

    public function generate(
        string $applicationRoot,
        string $sourceRelativePath,
        string $mappingArtifactBytes,
    ): GeneratedDataAccessArtifact {
        $artifact = (new MappingArtifactValidator())->validateCurrent($applicationRoot, $sourceRelativePath, $mappingArtifactBytes);
        $decoded = CanonicalJson::decodeObject($artifact->canonicalBytes(), 64);
        $model = $decoded['model'] ?? null;
        if (!is_array($model) || array_is_list($model)) {
            throw InvalidGeneratedDataAccessException::invalid(MappingFailureCategory::GeneratedStructure, true);
        }
        /** @var array<string, mixed> $model */
        $bytes = (new GeneratedDataAccessEmitter())->emit(
            $model,
            $artifact->artifactSha256(),
            $artifact->modelSha256(),
            MappingImplementation::generatorSha256(),
        );
        if (strlen($bytes) > self::MAX_GENERATED_BYTES) {
            throw InvalidGeneratedDataAccessException::invalid(MappingFailureCategory::GeneratedBytes, true);
        }

        return (new GeneratedDataAccessValidator())->validateStructure($bytes);
    }
}
