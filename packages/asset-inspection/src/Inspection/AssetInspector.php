<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Inspection;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\AssetInspectionBindingException;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;
use Kongono\AssetResolution\Artifact\AssetResolutionEntry;
use Kongono\AssetResolution\Artifact\AssetResolutionObject;
use Throwable;

/** Independently reconstructs one supplied resolution artifact in memory. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetInspector
{
    public function inspect(AssetInspectionRequest $request): AssetInspection
    {
        $current = $request->current();
        $supplied = $current->resolutionArtifact();
        try {
            $entries = [];
            /** @var array<string, AssetResolutionObject> $objects */
            $objects = [];
            $contentIdentities = [];
            $preloadCount = 0;
            foreach ($supplied->entries() as $entry) {
                $primary = $this->object($entry->primary());
                $objects[$primary->objectKey()] = $primary;
                $contentIdentities[$primary->contentIdentity()->value()] = true;
                $dependencies = [];
                foreach ($entry->dependencies() as $dependency) {
                    $object = $this->object($dependency['object']);
                    $objects[$object->objectKey()] = $object;
                    $contentIdentities[$object->contentIdentity()->value()] = true;
                    $dependencies[] = ['kind' => $dependency['kind'], 'object' => $object];
                }
                $entries[] = new AssetResolutionEntry($entry->identity(), $primary, $dependencies);
            }
            $reconstructed = new AssetResolutionArtifact(
                $supplied->contractVersion(),
                $supplied->producerKind(),
                $supplied->buildIdentity(),
                $supplied->sourceSetIdentity(),
                $supplied->graphIdentity(),
                $entries,
            );
            foreach ($objects as $object) {
                if ('none' !== $object->preloadHint()->value) {
                    ++$preloadCount;
                }
            }
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw AssetInspectionBindingException::mismatch('artifact_structure', $failure);
        }
        if (!hash_equals($supplied->bytes(), $reconstructed->bytes())
            || !$supplied->identity()->equals($reconstructed->identity())
            || $supplied->entryCount() !== $reconstructed->entryCount()) {
            throw AssetInspectionBindingException::mismatch('artifact_comparison');
        }
        $reachableCount = count($objects);
        if ($current->declarationCount() < 0 || $current->declarationCount() > 1024
            || $current->dependencyCount() < 0 || $current->dependencyCount() > 4096
            || $current->declarationCount() !== $reconstructed->entryCount()
            || $current->entryPointCount() < 0 || $current->entryPointCount() > $current->declarationCount()
            || $current->outputCount() !== $reachableCount) {
            throw AssetInspectionBindingException::mismatch('current_result_binding');
        }
        $contentSet = array_keys($contentIdentities);
        sort($contentSet, SORT_STRING);

        return new AssetInspection(
            $reconstructed->producerKind(),
            [
                'resolution_sha256' => $reconstructed->identity()->value(),
                'build_sha256' => $reconstructed->buildIdentity(),
                'source_set_sha256' => $reconstructed->sourceSetIdentity(),
                'graph_sha256' => $reconstructed->graphIdentity(),
                'content_set_sha256' => hash('sha256', implode("\n", $contentSet)),
            ],
            [
                'declaration_count' => $current->declarationCount(),
                'dependency_count' => $current->dependencyCount(),
                'entry_point_count' => $current->entryPointCount(),
                'output_count' => $current->outputCount(),
                'resolution_entry_count' => $reconstructed->entryCount(),
                'reachable_object_count' => $reachableCount,
                'integrity_present_count' => $reachableCount,
                'preload_present_count' => $preloadCount,
            ],
            [
                'structurally_validated' => true,
                'supplied_current_evidence' => true,
                'filesystem_read' => false,
                'build_performed' => false,
                'resolution_performed' => false,
                'production_observed' => false,
            ],
        );
    }

    private function object(AssetResolutionObject $object): AssetResolutionObject
    {
        return new AssetResolutionObject(
            $object->objectKey(),
            $object->kind(),
            $object->contentType(),
            $object->contentIdentity(),
            $object->byteCount(),
            $object->integrity(),
            $object->preloadHint(),
        );
    }
}
