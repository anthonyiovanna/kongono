<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;

/** Computes the reviewed identity of the closed persistence-state generator. */
#[Api(ApiStatus::Internal)]
final class PersistenceGenerationImplementation
{
    /** @var list<string> */
    public const GENERATOR_PATHS = [
        'src/Diagnostic/PersistenceBuildDiagnostic.php',
        'src/Generated/GeneratedAggregateRepositoryArtifact.php',
        'src/Generated/GeneratedAggregateRepositoryGenerator.php',
        'src/Generated/GeneratedAggregateRepositoryPublisher.php',
        'src/Generated/GeneratedAggregateRepositoryValidator.php',
        'src/Exception/GeneratedPersistenceStatePublicationException.php',
        'src/Exception/InvalidGeneratedPersistenceStateException.php',
        'src/Generated/GeneratedPersistenceStateArtifact.php',
        'src/Generated/GeneratedPersistenceStateGenerator.php',
        'src/Generated/GeneratedPersistenceStatePublisher.php',
        'src/Generated/GeneratedPersistenceStateValidator.php',
        'src/Internal/GeneratedPersistenceStateEmitter.php',
        'src/Internal/GeneratedAggregateRepositoryEmitter.php',
        'src/Internal/AggregateRepositoryGenerationImplementation.php',
        'src/Internal/AggregateRepositorySourceCompiler.php',
        'src/Internal/PathGuard.php',
        'src/Internal/PersistenceBuildFailureCategory.php',
        'src/Internal/PersistenceGenerationImplementation.php',
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
                throw InvalidGeneratedPersistenceStateException::input();
            }
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
