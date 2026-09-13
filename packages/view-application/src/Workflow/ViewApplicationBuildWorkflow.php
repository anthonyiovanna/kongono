<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Exception\ViewApplicationWorkflowException;
use Kongono\ViewApplication\Internal\ViewApplicationPathGuard;
use Kongono\ViewBuild\Artifact\CompiledViewArtifact;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactBuilder;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactPublisher;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactValidator;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewBuild\Source\ViewSourceSet;
use Throwable;

/** Plans explicitly builds and write-freely checks one exact compiled-view application artifact. */
#[Api(ApiStatus::Experimental)]
final class ViewApplicationBuildWorkflow
{
    public function plan(ViewApplicationDefinition $definition): PlannedViewApplicationBuild
    {
        [$canonical, $source, $artifact] = $this->expected($definition);
        $present = $this->targetPresent($canonical, $source, $artifact);

        return $this->planned($artifact, !$present);
    }

    public function build(ViewApplicationDefinition $definition): CurrentViewApplicationBuild
    {
        [$canonical, , $artifact] = $this->expected($definition);
        try {
            $published = (new CompiledViewArtifactPublisher())->publish($artifact, $canonical->artifactOutputRoot());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewApplicationWorkflowException::failed('publication', $failure);
        }
        if ($published !== $canonical->artifactOutputRoot() . '/kongono-view-v1-' . $artifact->compiledViewIdentity()->value()) {
            throw ViewApplicationWorkflowException::failed('publication');
        }

        return $this->check($canonical);
    }

    public function check(ViewApplicationDefinition $definition): CurrentViewApplicationBuild
    {
        [$canonical, $source, $artifact] = $this->expected($definition);
        $current = $this->readCurrent($canonical, $source, $artifact);
        if (!$current->equals($artifact)) {
            throw ViewApplicationWorkflowException::failed('current_check');
        }

        return new CurrentViewApplicationBuild($canonical, $current);
    }

    /** @return array{ViewApplicationDefinition,ViewSourceSet,CompiledViewArtifact} */
    private function expected(ViewApplicationDefinition $definition): array
    {
        try {
            $resolved = ViewApplicationPathGuard::resolve($definition);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            if ($failure instanceof \Kongono\ViewApplication\Exception\ExceptionInterface) {
                throw $failure;
            }
            throw ViewApplicationWorkflowException::failed('source_read', $failure);
        }
        try {
            $source = (new ViewSourceParser())->parse($resolved['source']['bytes']);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewApplicationWorkflowException::failed('source_parse', $failure);
        }
        try {
            $artifact = (new CompiledViewArtifactBuilder())->build($source);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewApplicationWorkflowException::failed('artifact_build', $failure);
        }

        return [$resolved['definition'], $source, $artifact];
    }

    private function targetPresent(ViewApplicationDefinition $definition, ViewSourceSet $source, CompiledViewArtifact $expected): bool
    {
        $target = $definition->artifactOutputRoot() . '/kongono-view-v1-' . $expected->compiledViewIdentity()->value();
        if (!file_exists($target) && !is_link($target)) {
            return false;
        }
        $this->readCurrent($definition, $source, $expected);

        return true;
    }

    private function readCurrent(ViewApplicationDefinition $definition, ViewSourceSet $source, CompiledViewArtifact $expected): CompiledViewArtifact
    {
        $target = $definition->artifactOutputRoot() . '/kongono-view-v1-' . $expected->compiledViewIdentity()->value();
        $resolved = realpath($target);
        if (false === $resolved || $this->normalize($resolved) !== $target || is_link($target) || !is_dir($target)) {
            throw ViewApplicationWorkflowException::failed('target_assessment');
        }
        $entries = @scandir($target);
        if (['.', '..', 'artifact.json', 'compiled.php'] !== $entries) {
            throw ViewApplicationWorkflowException::failed('target_assessment');
        }
        $metadata = $this->read($target . '/artifact.json');
        $generated = $this->read($target . '/compiled.php');
        if (!hash_equals($expected->metadataBytes(), $metadata) || !hash_equals($expected->generatedPhpBytes(), $generated)) {
            throw ViewApplicationWorkflowException::failed('current_check');
        }
        try {
            return (new CompiledViewArtifactValidator())->validateCurrent(
                $source,
                $metadata,
                $generated,
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewApplicationWorkflowException::failed('current_check', $failure);
        }
    }

    private function read(string $path): string
    {
        $resolved = realpath($path);
        if (false === $resolved || $this->normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw ViewApplicationWorkflowException::failed('target_assessment');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > 2_097_152) {
            throw ViewApplicationWorkflowException::failed('target_assessment');
        }
        $bytes = @file_get_contents($path);
        $after = @stat($path);
        if (false === $bytes || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw ViewApplicationWorkflowException::failed('target_assessment');
        }

        return $bytes;
    }

    /**
     * @param array<array-key, mixed>       $before
     * @param array<array-key, mixed>|false $after
     */
    private function sameMetadata(array $before, array|false $after): bool
    {
        if (false === $after) {
            return false;
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                return false;
            }
        }

        return true;
    }

    private function planned(CompiledViewArtifact $artifact, bool $publicationRequired): PlannedViewApplicationBuild
    {
        return new PlannedViewApplicationBuild(
            $artifact->sourceIdentity(),
            $artifact->compiledViewIdentity(),
            $artifact->modelSha256(),
            $artifact->generatedPhpSha256(),
            $artifact->viewCount(),
            'kongono-view-v1-' . $artifact->compiledViewIdentity()->value(),
            $publicationRequired,
        );
    }

    private function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        return 1 === preg_match('/\A[A-Z]:\//D', $normalized) ? strtolower($normalized[0]) . substr($normalized, 1) : $normalized;
    }
}
