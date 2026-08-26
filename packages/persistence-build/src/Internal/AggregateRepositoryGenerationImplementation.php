<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;

/** Computes the reviewed identity of the closed aggregate-repository generator. */
#[Api(ApiStatus::Internal)]
final class AggregateRepositoryGenerationImplementation
{
    /** @var list<string> */
    public const GENERATOR_PATHS = [
        'src/Diagnostic/PersistenceBuildDiagnostic.php',
        'src/Generated/GeneratedAggregateRepositoryArtifact.php',
        'src/Generated/GeneratedAggregateRepositoryGenerator.php',
        'src/Generated/GeneratedAggregateRepositoryPublisher.php',
        'src/Generated/GeneratedAggregateRepositoryValidator.php',
        'src/Internal/AggregateRepositoryGenerationImplementation.php',
        'src/Internal/AggregateRepositorySourceCompiler.php',
        'src/Internal/GeneratedAggregateRepositoryEmitter.php',
        'src/Internal/PathGuard.php',
        'src/Internal/PersistenceBuildFailureCategory.php',
        'src/Internal/PersistenceGenerationImplementation.php',
        'src/Internal/StrictJsonDecoder.php',
        'src/Model/AggregateRepositoryDefinition.php',
        'src/Model/LoadPlanDefinition.php',
        'src/Model/RelationshipCascadeDefinition.php',
        'src/Model/RelationshipLoadDefinition.php',
    ];

    public static function generatorSha256(): string
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $records = [];
        foreach (self::GENERATOR_PATHS as $path) {
            $bytes = @file_get_contents($root . '/' . $path);
            if (false === $bytes) {
                throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidGeneratedAggregateRepositories, PersistenceBuildFailureCategory::GeneratedAggregateRepositories);
            }
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
