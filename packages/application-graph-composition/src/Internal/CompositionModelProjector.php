<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphComposition\Exception\InvalidCompositionGraphProjectionException;
use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjection;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Converts one already-admitted Composition model into exact canonical Graph records. */
#[Api(ApiStatus::Internal)]
final class CompositionModelProjector
{
    /** @param array<string, mixed> $model */
    public function project(array $model, string $artifactSha256): CompositionIrProjection
    {
        /** @var list<array{contract:string,lifetime:string}> $inputs */
        $inputs = $model['inputs'];
        /** @var list<array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}> $services */
        $services = $model['services'];
        /** @var list<array{contract:string,service:string}> $bindings */
        $bindings = $model['bindings'];
        /** @var list<array{kind:string,contract:string}> $roots */
        $roots = $model['roots'];

        $deriver = new FramedIdentityDeriver();
        /** @var array<string, NodeIdentity> $inputNodes */
        $inputNodes = [];
        /** @var array<string, NodeIdentity> $serviceNodes */
        $serviceNodes = [];
        /** @var array<string, NodeIdentity> $contractNodes */
        $contractNodes = [];
        /** @var array<string, NodeIdentity> $rootNodes */
        $rootNodes = [];
        /** @var array<string, string> $identitySemantics */
        $identitySemantics = [];
        $nodes = [];

        foreach ($inputs as $input) {
            $identity = $deriver->node(NodeType::CompositionInput, [$input['contract']]);
            $this->claimIdentity($identitySemantics, $identity, 'input\0' . $input['contract']);
            $inputNodes[$input['contract']] = $identity;
            $nodes[] = new GraphNodeRecord(
                ContractVersion::Version1,
                $identity,
                NodeType::CompositionInput,
                [$this->field('lifetime', $input['lifetime']), $this->field('symbol', $input['contract'])],
                $this->provenance($deriver, $artifactSha256),
            );
        }

        foreach ($services as $service) {
            $identity = $deriver->node(NodeType::CompositionService, [$service['class']]);
            $this->claimIdentity($identitySemantics, $identity, 'service\0' . $service['class']);
            $serviceNodes[$service['class']] = $identity;
            $fields = [$this->field('class', $service['class'])];
            if (null !== $service['cleanup']) {
                $fields[] = $this->field('cleanup', $service['cleanup']);
            }
            $fields[] = $this->field('lifetime', $service['lifetime']);
            $nodes[] = new GraphNodeRecord(
                ContractVersion::Version1,
                $identity,
                NodeType::CompositionService,
                $fields,
                $this->provenance($deriver, $artifactSha256),
            );
        }

        $contracts = [];
        foreach ($bindings as $binding) {
            $contracts[$binding['contract']] = true;
        }
        foreach ($roots as $root) {
            $contracts[$root['contract']] = true;
        }
        $contractSymbols = array_keys($contracts);
        sort($contractSymbols, SORT_STRING);
        foreach ($contractSymbols as $contract) {
            $identity = $deriver->node(NodeType::CompositionContract, [$contract]);
            $this->claimIdentity($identitySemantics, $identity, 'contract\0' . $contract);
            $contractNodes[$contract] = $identity;
            $nodes[] = new GraphNodeRecord(
                ContractVersion::Version1,
                $identity,
                NodeType::CompositionContract,
                [$this->field('symbol', $contract)],
                $this->provenance($deriver, $artifactSha256),
            );
        }

        foreach ($roots as $root) {
            $key = $root['kind'] . "\0" . $root['contract'];
            $identity = $deriver->node(NodeType::CompositionRoot, [$root['kind'], $root['contract']]);
            $this->claimIdentity($identitySemantics, $identity, 'root\0' . $key);
            $rootNodes[$key] = $identity;
            $nodes[] = new GraphNodeRecord(
                ContractVersion::Version1,
                $identity,
                NodeType::CompositionRoot,
                [$this->field('contract', $root['contract']), $this->field('kind', $root['kind'])],
                $this->provenance($deriver, $artifactSha256),
            );
        }

        usort($nodes, static fn(GraphNodeRecord $left, GraphNodeRecord $right): int => strcmp($left->identity()->value(), $right->identity()->value()));

        /** @var array<string, string> $bindingMap */
        $bindingMap = [];
        foreach ($bindings as $binding) {
            $bindingMap[$binding['contract']] = $binding['service'];
        }
        $resolve = static fn(string $contract): string => isset($serviceNodes[$contract]) ? $contract : $bindingMap[$contract];

        $relations = [];
        /** @var array<string, string> $relationSemantics */
        $relationSemantics = [];
        foreach ($services as $service) {
            foreach ($service['arguments'] as $argument) {
                if (isset($argument['input']) && is_string($argument['input'])) {
                    $this->relation(
                        $relations,
                        $relationSemantics,
                        $deriver,
                        RelationType::ConsumesInput,
                        $serviceNodes[$service['class']],
                        $inputNodes[$argument['input']],
                        $artifactSha256,
                    );
                }
                if (isset($argument['service']) && is_string($argument['service'])) {
                    $target = $resolve($argument['service']);
                    $this->relation(
                        $relations,
                        $relationSemantics,
                        $deriver,
                        RelationType::DependsOn,
                        $serviceNodes[$service['class']],
                        $serviceNodes[$target],
                        $artifactSha256,
                    );
                }
            }
        }
        foreach ($bindings as $binding) {
            $this->relation(
                $relations,
                $relationSemantics,
                $deriver,
                RelationType::ImplementsContract,
                $serviceNodes[$binding['service']],
                $contractNodes[$binding['contract']],
                $artifactSha256,
            );
        }
        foreach ($roots as $root) {
            $service = $resolve($root['contract']);
            $this->relation(
                $relations,
                $relationSemantics,
                $deriver,
                RelationType::ImplementsContract,
                $serviceNodes[$service],
                $contractNodes[$root['contract']],
                $artifactSha256,
            );
            $this->relation(
                $relations,
                $relationSemantics,
                $deriver,
                RelationType::ExposesRoot,
                $rootNodes[$root['kind'] . "\0" . $root['contract']],
                $contractNodes[$root['contract']],
                $artifactSha256,
            );
        }

        usort($relations, static fn(GraphRelationRecord $left, GraphRelationRecord $right): int => strcmp($left->identity()->value(), $right->identity()->value()));

        return new CompositionIrProjection($nodes, $relations);
    }

    private function field(string $name, string $value): GraphFieldRecord
    {
        return new GraphFieldRecord(new FieldName($name), $value);
    }

    private function provenance(FramedIdentityDeriver $deriver, string $artifactSha256): GraphProvenance
    {
        return new GraphProvenance(
            $deriver->producer(),
            $deriver->source($artifactSha256),
            ProducerContract::CompositionV2,
            DerivationVersion::Version1,
            $artifactSha256,
        );
    }

    /** @param array<string, string> $semantics */
    private function claimIdentity(array &$semantics, NodeIdentity $identity, string $semantic): void
    {
        $value = $identity->value();
        if (isset($semantics[$value]) && $semantics[$value] !== $semantic) {
            throw InvalidCompositionGraphProjectionException::conflict('node_identity_collision', 'node');
        }
        $semantics[$value] = $semantic;
    }

    /**
     * @param list<GraphRelationRecord> $relations
     * @param array<string, string> $semantics
     */
    private function relation(
        array &$relations,
        array &$semantics,
        FramedIdentityDeriver $deriver,
        RelationType $type,
        NodeIdentity $source,
        NodeIdentity $target,
        string $artifactSha256,
    ): void {
        $semantic = $type->value . "\0" . $source->value() . "\0" . $target->value();
        if (in_array($semantic, $semantics, true)) {
            return;
        }
        $identity = $deriver->relation($type, $source, $target);
        $value = $identity->value();
        if (isset($semantics[$value]) && $semantics[$value] !== $semantic) {
            throw InvalidCompositionGraphProjectionException::conflict('relation_identity_collision', 'relation');
        }
        $semantics[$value] = $semantic;
        $relations[] = new GraphRelationRecord(
            ContractVersion::Version1,
            $identity,
            $type,
            $source,
            $target,
            [],
            $this->provenance($deriver, $artifactSha256),
        );
    }
}
