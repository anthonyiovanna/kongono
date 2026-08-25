<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Computes reviewed identities for mapping build and generation implementations. */
#[Api(ApiStatus::Internal)]
final class MappingImplementation
{
    /** @var list<string> */
    public const BUILDER_PATHS = [
        'src/Build/MappingArtifact.php',
        'src/Build/MappingArtifactBuilder.php',
        'src/Build/MappingArtifactValidator.php',
        'src/Diagnostic/MappingDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/GeneratedDataAccessPublicationException.php',
        'src/Exception/HydrationException.php',
        'src/Exception/InvalidGeneratedDataAccessException.php',
        'src/Exception/InvalidMappingArtifactException.php',
        'src/Exception/InvalidMappingSourceException.php',
        'src/Exception/MappingException.php',
        'src/Exception/RepositoryCleanupException.php',
        'src/Exception/RepositoryExecutionException.php',
        'src/Exception/RowCardinalityException.php',
        'src/Exception/StaleMappingArtifactException.php',
        'src/Generated/GeneratedDataAccessArtifact.php',
        'src/Generated/GeneratedDataAccessGenerator.php',
        'src/Generated/GeneratedDataAccessPublisher.php',
        'src/Generated/GeneratedDataAccessValidator.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/GeneratedDataAccessEmitter.php',
        'src/Internal/MappingFailureCategory.php',
        'src/Internal/MappingImplementation.php',
        'src/Internal/MappingModelCompiler.php',
        'src/Internal/PathGuard.php',
        'src/Internal/StrictJsonDecoder.php',
    ];

    /** @var list<string> */
    public const GENERATOR_PATHS = [
        'src/Diagnostic/MappingDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/GeneratedDataAccessPublicationException.php',
        'src/Exception/HydrationException.php',
        'src/Exception/InvalidGeneratedDataAccessException.php',
        'src/Exception/MappingException.php',
        'src/Exception/RepositoryCleanupException.php',
        'src/Exception/RepositoryExecutionException.php',
        'src/Exception/RowCardinalityException.php',
        'src/Generated/GeneratedDataAccessArtifact.php',
        'src/Generated/GeneratedDataAccessGenerator.php',
        'src/Generated/GeneratedDataAccessPublisher.php',
        'src/Generated/GeneratedDataAccessValidator.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/GeneratedDataAccessEmitter.php',
        'src/Internal/MappingFailureCategory.php',
        'src/Internal/MappingImplementation.php',
        'src/Internal/PathGuard.php',
    ];

    public static function builderSha256(): string
    {
        return self::hash(self::BUILDER_PATHS);
    }

    public static function generatorSha256(): string
    {
        return self::hash(self::GENERATOR_PATHS);
    }

    /** @param list<string> $paths */
    private static function hash(array $paths): string
    {
        $packageRoot = PathGuard::resolveApplicationRoot(dirname(__DIR__, 2));
        $records = [];
        foreach ($paths as $path) {
            $snapshot = PathGuard::readProjectFile($packageRoot, $path);
            $records[] = implode("\t", [$path, (string) $snapshot['size'], $snapshot['sha256']]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
