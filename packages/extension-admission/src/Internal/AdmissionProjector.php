<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Throwable;

/** Creates fresh canonical winning records and performs one complete structural Graph admission. */
#[Api(ApiStatus::Internal)]
final class AdmissionProjector
{
    /**
     * @param list<ExtensionGraphContribution> $winningBundles
     * @return array{0: list<GraphNodeRecord>, 1: list<GraphRelationRecord>}
     */
    public function project(array $winningBundles): array
    {
        $nodes = [];
        $relations = [];
        foreach ($winningBundles as $bundle) {
            foreach ($bundle->nodeRecords() as $record) {
                $nodes[] = new GraphNodeRecord($record->version(), $record->identity(), $record->type(), $record->fields(), $record->provenance());
            }
            foreach ($bundle->relationRecords() as $record) {
                $relations[] = new GraphRelationRecord($record->version(), $record->identity(), $record->type(), $record->source(), $record->target(), $record->fields(), $record->provenance());
            }
        }
        usort($nodes, static fn(GraphNodeRecord $left, GraphNodeRecord $right): int => strcmp($left->identity()->value(), $right->identity()->value()));
        usort($relations, static fn(GraphRelationRecord $left, GraphRelationRecord $right): int => strcmp($left->identity()->value(), $right->identity()->value()));

        try {
            (new ApplicationGraphBuilder())->build($nodes, $relations);
        } catch (InvalidGraphBuildException $failure) {
            throw InvalidExtensionAdmissionException::invalid('graph_admission_failed', 'graph', previous: $failure);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidExtensionAdmissionException::invalid('graph_admission_failed', 'graph', previous: $failure);
        }

        return [$nodes, $relations];
    }
}
