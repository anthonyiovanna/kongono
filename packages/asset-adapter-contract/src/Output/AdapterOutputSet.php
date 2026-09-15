<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Output;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetBuild\Source\AssetSourceSet;

/** Holds one complete detached canonical reachable adapter-output graph. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterOutputSet
{
    /** @var list<AdaptedAssetOutput> */
    private array $outputs;
    /** @var list<AdapterEntryBinding> */
    private array $bindings;
    /** @var list<AdapterOutputDependency> */
    private array $dependencies;
    private int $maximumDepth;

    /**
     * @param array<array-key, mixed> $outputs
     * @param array<array-key, mixed> $bindings
     * @param array<array-key, mixed> $dependencies
     */
    public function __construct(private AssetSourceSet $sourceSet, array $outputs, array $bindings, array $dependencies, int $depthLimit = 256)
    {
        if (!array_is_list($outputs)) {
            throw InvalidAssetAdapterContractException::output('outputs_not_list');
        }if (!array_is_list($bindings)) {
            throw InvalidAssetAdapterContractException::output('bindings_not_list');
        }if (!array_is_list($dependencies)) {
            throw InvalidAssetAdapterContractException::output('dependencies_not_list');
        }if (count($outputs) > 2048) {
            throw InvalidAssetAdapterContractException::output('output_count', count($outputs), 2048);
        }if ($depthLimit < 0 || $depthLimit > 256) {
            throw InvalidAssetAdapterContractException::output('dependency_depth', limit: 256);
        }
        $byId = [];
        foreach ($outputs as $position => $output) {
            if (!$output instanceof AdaptedAssetOutput) {
                throw InvalidAssetAdapterContractException::output('invalid_output_entry', entryCount: count($outputs));
            }$id = $output->identity()->value();
            if (isset($byId[$id])) {
                throw InvalidAssetAdapterContractException::output('duplicate_output');
            }$byId[$id] = $output;
        }ksort($byId, SORT_STRING);
        $this->outputs = array_values($byId);
        $entryIds = [];
        foreach ($sourceSet->declarationSet()->entryPoints() as $entry) {
            $entryIds[$entry->identity()->value()] = true;
        }
        $byEntry = [];
        foreach ($bindings as $binding) {
            if (!$binding instanceof AdapterEntryBinding) {
                throw InvalidAssetAdapterContractException::output('invalid_binding_entry');
            }$logical = $binding->entryPoint()->value();
            if (isset($byEntry[$logical])) {
                throw InvalidAssetAdapterContractException::output('duplicate_binding');
            }if (!isset($byId[$binding->output()->value()])) {
                throw InvalidAssetAdapterContractException::output('missing_output');
            }$byEntry[$logical] = $binding;
        }ksort($byEntry, SORT_STRING);
        if (array_keys($entryIds) !== array_keys($byEntry)) {
            throw InvalidAssetAdapterContractException::output('entry_binding_relation');
        }$this->bindings = array_values($byEntry);
        $byEdge = [];
        $targets = [];
        foreach ($byId as $id => $unused) {
            $targets[$id] = [];
        }foreach ($dependencies as $dependency) {
            if (!$dependency instanceof AdapterOutputDependency) {
                throw InvalidAssetAdapterContractException::output('invalid_dependency_entry');
            }$source = $dependency->source()->value();
            $target = $dependency->target()->value();
            if (!isset($byId[$source]) || !isset($byId[$target])) {
                throw InvalidAssetAdapterContractException::output('missing_output');
            }$key = $dependency->orderingKey();
            if (isset($byEdge[$key])) {
                throw InvalidAssetAdapterContractException::output('duplicate_dependency');
            }$byEdge[$key] = $dependency;
            $targets[$source][] = $target;
        }ksort($byEdge, SORT_STRING);
        $this->dependencies = array_values($byEdge);
        if ([] === $byId) {
            if ([] !== $byEntry || [] !== $byEdge || 0 !== $sourceSet->sourceCount()) {
                throw InvalidAssetAdapterContractException::output('entry_binding_relation');
            }$this->maximumDepth = 0;
            return;
        }
        $reachable = [];
        $pending = array_map(static fn(AdapterEntryBinding $binding): string => $binding->output()->value(), $this->bindings);
        while ([] !== $pending) {
            $id = array_shift($pending);
            if (isset($reachable[$id])) {
                continue;
            }$reachable[$id] = true;
            foreach ($targets[$id] as $target) {
                $pending[] = $target;
            }
        }if (count($reachable) !== count($byId)) {
            throw InvalidAssetAdapterContractException::output('unreachable_output');
        }
        $visiting = [];
        $visited = [];
        $depths = [];
        $visit = function (string $id) use (&$visit, &$visiting, &$visited, &$depths, $targets): int {
            if (isset($visiting[$id])) {
                throw InvalidAssetAdapterContractException::output('dependency_cycle');
            }if (isset($visited[$id])) {
                return $depths[$id];
            }$visiting[$id] = true;
            $depth = 1;
            foreach ($targets[$id] as $target) {
                $depth = max($depth, 1 + $visit($target));
            }unset($visiting[$id]);
            $visited[$id] = true;
            return $depths[$id] = $depth;
        };
        $maximum = 0;
        foreach (array_keys($byId) as $outputId) {
            $maximum = max($maximum, $visit($outputId));
        }if ($maximum > $depthLimit) {
            throw InvalidAssetAdapterContractException::output('dependency_depth', limit: $depthLimit);
        }$this->maximumDepth = $maximum;
    }
    public function sourceSet(): AssetSourceSet
    {
        return $this->sourceSet;
    } /** @return list<AdaptedAssetOutput> */public function outputs(): array
    {
        return [...$this->outputs];
    }/** @return list<AdapterEntryBinding> */public function entryBindings(): array
    {
        return [...$this->bindings];
    }/** @return list<AdapterOutputDependency> */public function dependencies(): array
    {
        return [...$this->dependencies];
    } public function outputCount(): int
    {
        return count($this->outputs);
    } public function bindingCount(): int
    {
        return count($this->bindings);
    } public function dependencyCount(): int
    {
        return count($this->dependencies);
    } public function maximumDepth(): int
    {
        return $this->maximumDepth;
    }
    public function output(AdapterOutputIdentity $identity): ?AdaptedAssetOutput
    {
        foreach ($this->outputs as $output) {
            if ($output->identity()->equals($identity)) {
                return $output;
            }
        }return null;
    }
}
