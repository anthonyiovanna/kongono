<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetGraphException;
use Kongono\AssetBuild\Internal\BuildGuard;
use Kongono\AssetBuild\Internal\CanonicalJson;
use Kongono\AssetBuild\Source\AssetSourceSet;

/** Builds one complete deterministic graph without filesystem or external I/O. */
#[Api(ApiStatus::Experimental)]
final class AssetGraphBuilder
{
    public function build(AssetSourceSet $sourceSet): AssetGraph
    {
        $set = $sourceSet->declarationSet();
        if (0 === $set->declarationCount()) {
            $identity = new AssetGraphIdentity(hash('sha256', CanonicalJson::encode([
                'contract_version' => $set->contractVersion()->value,
                'declarations' => [], 'dependencies' => [], 'entry_points' => [], 'topological_order' => [], 'maximum_depth' => 0,
            ])));

            return new AssetGraph($set, [], [], 0, $identity);
        }
        if (0 === $set->entryPointCount()) {
            throw InvalidAssetGraphException::invalid('missing_entry_point', entryCount: 0);
        }
        $declarations = [];
        $targets = [];
        $dependents = [];
        $indegree = [];
        foreach ($set->declarations() as $declaration) {
            $id = $declaration->identity()->value();
            $declarations[$id] = $declaration;
            $targets[$id] = [];
            $dependents[$id] = [];
            $indegree[$id] = 0;
        }
        foreach ($set->dependencies() as $dependency) {
            $source = $dependency->source()->value();
            $target = $dependency->target()->value();
            $targets[$source][] = $dependency;
            $dependents[$target][] = $source;
            ++$indegree[$source];
        }
        foreach ($targets as &$relations) {
            usort($relations, static fn($left, $right): int => strcmp($left->orderingKey(), $right->orderingKey()));
        }
        unset($relations);

        $reachable = [];
        $pending = array_map(static fn($entry): string => $entry->identity()->value(), $set->entryPoints());
        while ([] !== $pending) {
            $id = array_shift($pending);
            if (isset($reachable[$id])) {
                continue;
            }
            $reachable[$id] = true;
            foreach ($targets[$id] as $dependency) {
                $pending[] = $dependency->target()->value();
            }
        }
        if (count($reachable) !== count($declarations)) {
            throw InvalidAssetGraphException::invalid('unreachable_declaration', entryCount: count($declarations) - count($reachable));
        }

        $ready = [];
        foreach ($indegree as $id => $count) {
            if (0 === $count) {
                $ready[] = $id;
            }
        }
        sort($ready, SORT_STRING);
        $order = [];
        while ([] !== $ready) {
            $id = array_shift($ready);
            $order[] = $id;
            foreach ($dependents[$id] as $dependent) {
                --$indegree[$dependent];
                if (0 === $indegree[$dependent]) {
                    $ready[] = $dependent;
                    sort($ready, SORT_STRING);
                }
            }
        }
        if (count($order) !== count($declarations)) {
            throw InvalidAssetGraphException::invalid('dependency_cycle');
        }

        $depths = [];
        $maximumDepth = 0;
        foreach ($order as $id) {
            $depth = 1;
            foreach ($targets[$id] as $dependency) {
                $targetDepth = $depths[$dependency->target()->value()] ?? null;
                if (!is_int($targetDepth)) {
                    throw new \LogicException('Topological dependency depth relation was lost.');
                }
                $candidate = 1 + $targetDepth;
                if ($candidate > $depth) {
                    $depth = $candidate;
                }
            }
            $depths[$id] = $depth;
            if ($depth > $maximumDepth) {
                $maximumDepth = $depth;
            }
        }
        if ($maximumDepth > 256) {
            throw InvalidAssetGraphException::invalid('dependency_depth', depth: $maximumDepth, limit: 256);
        }
        ksort($declarations, SORT_STRING);
        $nodes = [];
        foreach ($declarations as $id => $declaration) {
            $nodes[] = new AssetGraphNode($declaration, $targets[$id]);
        }
        $identity = new AssetGraphIdentity(hash('sha256', CanonicalJson::encode([
            'contract_version' => $set->contractVersion()->value,
            'declarations' => BuildGuard::declarations($set),
            'dependencies' => BuildGuard::dependencies($set),
            'entry_points' => BuildGuard::entryPoints($set),
            'topological_order' => $order,
            'maximum_depth' => $maximumDepth,
        ])));

        return new AssetGraph($set, $nodes, $order, $maximumDepth, $identity);
    }
}
