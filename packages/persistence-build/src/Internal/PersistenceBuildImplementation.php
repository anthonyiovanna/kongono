<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;

/** Computes the reviewed identity of the closed persistence builder implementation. */
#[Api(ApiStatus::Internal)]
final class PersistenceBuildImplementation
{
    /** @var list<string> */
    public const BUILDER_PATHS = [
        'src/Build/PersistenceArtifact.php',
        'src/Build/PersistenceArtifactBuilder.php',
        'src/Build/PersistenceArtifactValidator.php',
        'src/Diagnostic/PersistenceBuildDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/InvalidPersistenceArtifactException.php',
        'src/Exception/InvalidPersistenceSourceException.php',
        'src/Exception/PersistenceBuildException.php',
        'src/Exception/StalePersistenceArtifactException.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/AggregateRepositorySourceCompiler.php',
        'src/Internal/PathGuard.php',
        'src/Internal/PersistenceBuildFailureCategory.php',
        'src/Internal/PersistenceBuildImplementation.php',
        'src/Internal/PersistenceModelCompiler.php',
        'src/Internal/StrictJsonDecoder.php',
        'src/Model/AggregateDefinition.php',
        'src/Model/AggregateRepositoryDefinition.php',
        'src/Model/ConstructionDefinition.php',
        'src/Model/ConstructionKind.php',
        'src/Model/EntityDefinition.php',
        'src/Model/FieldDefinition.php',
        'src/Model/FieldRole.php',
        'src/Model/FieldValueKind.php',
        'src/Model/LoadPlanDefinition.php',
        'src/Model/PersistenceModel.php',
        'src/Model/RelationshipCardinality.php',
        'src/Model/RelationshipCascadeDefinition.php',
        'src/Model/RelationshipDefinition.php',
        'src/Model/RelationshipOrder.php',
        'src/Model/RelationshipOwnership.php',
        'src/Model/RelationshipLoadDefinition.php',
        'src/Model/SortDirection.php',
    ];

    public static function builderSha256(): string
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $records = [];
        foreach (self::BUILDER_PATHS as $path) {
            $bytes = @file_get_contents($root . '/' . $path);
            if (false === $bytes) {
                throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourcePath);
            }
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
