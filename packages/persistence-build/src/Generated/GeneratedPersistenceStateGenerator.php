<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Build\PersistenceArtifactValidator;
use Kongono\PersistenceBuild\Exception\ExceptionInterface;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Internal\GeneratedPersistenceStateEmitter;
use Kongono\PersistenceBuild\Internal\PersistenceGenerationImplementation;

/** Generates deterministic ordinary PHP from one exact-current persistence artifact. */
#[Api(ApiStatus::Experimental)]
final class GeneratedPersistenceStateGenerator
{
    public const MAX_GENERATED_BYTES = 16_777_216;

    public function generate(
        string $applicationRoot,
        string $sourceRelativePath,
        string $persistenceArtifactBytes,
    ): GeneratedPersistenceStateArtifact {
        try {
            $artifact = (new PersistenceArtifactValidator())->validateCurrent(
                $applicationRoot,
                $sourceRelativePath,
                $persistenceArtifactBytes,
            );
        } catch (ExceptionInterface $failure) {
            throw InvalidGeneratedPersistenceStateException::input($failure);
        }
        $generatorSha256 = PersistenceGenerationImplementation::generatorSha256();
        $bytes = (new GeneratedPersistenceStateEmitter())->emit(
            $artifact->model(),
            $artifact->artifactSha256(),
            $artifact->modelSha256(),
            $artifact->builderImplementationSha256(),
            $generatorSha256,
        );
        if (strlen($bytes) > self::MAX_GENERATED_BYTES) {
            throw InvalidGeneratedPersistenceStateException::invalid();
        }

        return (new GeneratedPersistenceStateValidator())->validateStructure($bytes);
    }
}
