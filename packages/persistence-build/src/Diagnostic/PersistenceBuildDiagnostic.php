<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed, actionable, non-disclosing persistence-build diagnostics. */
#[Api(ApiStatus::Internal)]
enum PersistenceBuildDiagnostic: string
{
    case InvalidSource = 'KGN-PERSISTENCE-BUILD-1001';
    case InvalidMappingEvidence = 'KGN-PERSISTENCE-BUILD-1002';
    case InvalidConstruction = 'KGN-PERSISTENCE-BUILD-1003';
    case InvalidField = 'KGN-PERSISTENCE-BUILD-1004';
    case InvalidAggregate = 'KGN-PERSISTENCE-BUILD-1005';
    case InvalidRelationship = 'KGN-PERSISTENCE-BUILD-1006';
    case InvalidArtifact = 'KGN-PERSISTENCE-BUILD-1007';
    case StaleArtifact = 'KGN-PERSISTENCE-BUILD-1008';
    case PublicationFailed = 'KGN-PERSISTENCE-BUILD-1009';
    case InputChanged = 'KGN-PERSISTENCE-BUILD-1010';
    case InvalidGenerationInput = 'KGN-PERSISTENCE-BUILD-1011';
    case InvalidGeneratedState = 'KGN-PERSISTENCE-BUILD-1012';
    case StaleGeneratedState = 'KGN-PERSISTENCE-BUILD-1013';
    case GeneratedStatePublicationFailed = 'KGN-PERSISTENCE-BUILD-1014';
    case InvalidGeneratedStateValue = 'KGN-PERSISTENCE-BUILD-1015';
    case InvalidAggregateRepositorySource = 'KGN-PERSISTENCE-BUILD-1016';
    case InvalidGeneratedAggregateRepositories = 'KGN-PERSISTENCE-BUILD-1017';
    case StaleGeneratedAggregateRepositories = 'KGN-PERSISTENCE-BUILD-1018';
    case AggregateRepositoryPublicationFailed = 'KGN-PERSISTENCE-BUILD-1019';
    case GeneratedPersistenceLoadFailed = 'KGN-PERSISTENCE-BUILD-1020';

    public function context(string $failureCategory, bool $artifactCurrent = false): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [
                new DiagnosticFieldSchema('schema_version', 'Schema version', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('artifact_current', 'Artifact current', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
            ],
        ), [
            'schema_version' => 1,
            'failure_category' => $failureCategory,
            'artifact_current' => $artifactCurrent,
        ]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Validate persistence source',
            self::InvalidMappingEvidence => 'Validate current mapping evidence',
            self::InvalidConstruction => 'Validate entity construction',
            self::InvalidField => 'Validate entity field bindings',
            self::InvalidAggregate => 'Validate aggregate declarations',
            self::InvalidRelationship => 'Validate relationship declarations',
            self::InvalidArtifact => 'Validate persistence artifact',
            self::StaleArtifact => 'Match persistence artifact to current inputs',
            self::PublicationFailed => 'Publish persistence artifact',
            self::InputChanged => 'Authenticate stable build input',
            self::InvalidGenerationInput => 'Validate current persistence generation input',
            self::InvalidGeneratedState => 'Validate generated persistence state',
            self::StaleGeneratedState => 'Match generated persistence state to current inputs',
            self::GeneratedStatePublicationFailed => 'Publish generated persistence state',
            self::InvalidGeneratedStateValue => 'Validate generated persistence state value',
            self::InvalidAggregateRepositorySource => 'Validate aggregate-repository source',
            self::InvalidGeneratedAggregateRepositories => 'Validate generated aggregate repositories',
            self::StaleGeneratedAggregateRepositories => 'Match generated aggregate repositories to current inputs',
            self::AggregateRepositoryPublicationFailed => 'Publish generated aggregate repositories',
            self::GeneratedPersistenceLoadFailed => 'Load exact-current generated persistence',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidSource => 'The explicit source must satisfy the closed bounded version-1 vocabulary',
            self::InvalidMappingEvidence => 'Persistence compilation must consume exact current mapping evidence',
            self::InvalidConstruction => 'Entity construction must be explicit public typed and non-variadic',
            self::InvalidField => 'Every mapped record field must have one compatible entity binding',
            self::InvalidAggregate => 'Every entity must belong to exactly one aggregate with one explicit root',
            self::InvalidRelationship => 'Relationships must reference compatible declared fields and ownership boundaries',
            self::InvalidArtifact => 'The persistence artifact must be canonical bounded and internally consistent',
            self::StaleArtifact => 'The artifact must equal a complete build from exact current evidence',
            self::PublicationFailed => 'Artifact bytes must publish content-addressed atomically and without overwrite',
            self::InputChanged => 'Authenticated input bytes must remain unchanged throughout the build',
            self::InvalidGenerationInput => 'Generation must consume one exact-current persistence artifact',
            self::InvalidGeneratedState => 'Generated persistence-state PHP must satisfy the closed canonical grammar',
            self::StaleGeneratedState => 'Generated persistence-state PHP must equal regeneration from exact current evidence',
            self::GeneratedStatePublicationFailed => 'Generated state must publish atomically under its content identity without overwrite',
            self::InvalidGeneratedStateValue => 'Generated state evidence must accept only the declared bounded scalar and snapshot shapes',
            self::InvalidAggregateRepositorySource => 'The separate repository source must satisfy the closed bounded version-1 vocabulary',
            self::InvalidGeneratedAggregateRepositories => 'Generated aggregate-repository PHP must satisfy the closed canonical grammar',
            self::StaleGeneratedAggregateRepositories => 'Generated aggregate-repository PHP must equal regeneration from exact current evidence',
            self::AggregateRepositoryPublicationFailed => 'Generated aggregate repositories must publish atomically under their content identity without overwrite',
            self::GeneratedPersistenceLoadFailed => 'Generated persistence state and repositories must be exact current collision-free ordered and loaded from their admitted files',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidSource => 'Invalid persistence source',
            self::InvalidMappingEvidence => 'Invalid mapping evidence',
            self::InvalidConstruction => 'Invalid entity construction',
            self::InvalidField => 'Invalid entity field contract',
            self::InvalidAggregate => 'Invalid aggregate contract',
            self::InvalidRelationship => 'Invalid relationship contract',
            self::InvalidArtifact => 'Invalid persistence artifact',
            self::StaleArtifact => 'Stale persistence artifact',
            self::PublicationFailed => 'Persistence artifact publication failed',
            self::InputChanged => 'Persistence build input changed',
            self::InvalidGenerationInput => 'Invalid persistence generation input',
            self::InvalidGeneratedState => 'Invalid generated persistence state',
            self::StaleGeneratedState => 'Stale generated persistence state',
            self::GeneratedStatePublicationFailed => 'Generated persistence state publication failed',
            self::InvalidGeneratedStateValue => 'Invalid generated persistence state value',
            self::InvalidAggregateRepositorySource => 'Invalid aggregate-repository source',
            self::InvalidGeneratedAggregateRepositories => 'Invalid generated aggregate repositories',
            self::StaleGeneratedAggregateRepositories => 'Stale generated aggregate repositories',
            self::AggregateRepositoryPublicationFailed => 'Aggregate-repository publication failed',
            self::GeneratedPersistenceLoadFailed => 'Generated persistence load failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Correct the explicit persistence source and rebuild',
            self::InvalidMappingEvidence => 'Rebuild and validate the mapping source through its supported builder',
            self::InvalidConstruction => 'Declare one supported typed constructor or public static factory',
            self::InvalidField => 'Bind each mapped record field once with compatible typed access',
            self::InvalidAggregate => 'Declare complete unique aggregate membership and an included root',
            self::InvalidRelationship => 'Correct the declared endpoints cardinality ownership or ordering',
            self::InvalidArtifact => 'Regenerate the artifact with the supported persistence builder',
            self::StaleArtifact => 'Build a new artifact from the exact current source set',
            self::PublicationFailed => 'Use an existing safe destination root and resolve the publication conflict',
            self::InputChanged => 'Stop concurrent mutation and rebuild from a stable source set',
            self::InvalidGenerationInput => 'Rebuild and validate the exact current persistence artifact before generation',
            self::InvalidGeneratedState => 'Regenerate the PHP with the supported persistence-state generator',
            self::StaleGeneratedState => 'Generate new state evidence from the exact current persistence artifact',
            self::GeneratedStatePublicationFailed => 'Use an existing safe output root and resolve the publication conflict',
            self::InvalidGeneratedStateValue => 'Provide the exact declared record or snapshot shape and bounded scalar values',
            self::InvalidAggregateRepositorySource => 'Correct the explicit bounded repository source and rebuild',
            self::InvalidGeneratedAggregateRepositories => 'Regenerate repositories with the supported aggregate-repository generator',
            self::StaleGeneratedAggregateRepositories => 'Generate new repositories from the exact current source and persistence artifact',
            self::AggregateRepositoryPublicationFailed => 'Use an existing safe output root and resolve the publication conflict',
            self::GeneratedPersistenceLoadFailed => 'Rebuild exact-current generated persistence and restart with an unloaded process',
        };
    }
}
