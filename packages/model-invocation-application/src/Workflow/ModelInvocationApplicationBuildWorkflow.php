<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationApplicationWorkflowException;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringPublisher;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringValidator;
use Kongono\ModelInvocationApplication\Internal\PathGuard;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationWiringLoader;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactValidator;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Throwable;

/** Builds or write-free checks one exact plan-to-generated-application workflow. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationApplicationBuildWorkflow
{
    private const int MAX_INVOCATION_PLAN_ARTIFACT_BYTES = 65_536;

    public function build(string $applicationRoot, string $artifactFile, InvocationPlanSource $source, string $outputDirectory): CurrentModelInvocationApplicationBuild
    {
        return $this->execute(true, $applicationRoot, $artifactFile, $source, $outputDirectory);
    }

    public function check(string $applicationRoot, string $artifactFile, InvocationPlanSource $source, string $outputDirectory): CurrentModelInvocationApplicationBuild
    {
        return $this->execute(false, $applicationRoot, $artifactFile, $source, $outputDirectory);
    }

    private function execute(bool $publish, string $applicationRoot, string $artifactFile, InvocationPlanSource $source, string $outputDirectory): CurrentModelInvocationApplicationBuild
    {
        try {
            $root = PathGuard::applicationRoot($applicationRoot);
            [, $output] = PathGuard::publicationLocations($root, $outputDirectory);
            $artifactPath = PathGuard::workflowFile($root, $artifactFile);
        } catch (Throwable $failure) {
            throw ModelInvocationApplicationWorkflowException::failed('path', $failure);
        }
        $artifactSnapshot = $this->snapshot($artifactPath, self::MAX_INVOCATION_PLAN_ARTIFACT_BYTES, 'artifact_snapshot');
        try {
            (new InvocationPlanArtifactValidator())->validateCurrent($source, $artifactSnapshot['bytes']);
            $profile = (new ModelInvocationApplicationProfileFactory())->create($source, $artifactSnapshot['bytes']);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationApplicationWorkflowException::failed('profile', $failure);
        }
        try {
            $generated = (new ModelInvocationWiringGenerator())->generate($profile);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationApplicationWorkflowException::failed('generate', $failure);
        }
        $generatedPath = $output . '/kongono-model-invocation-wiring-v1-' . $generated->generatedWiringSha256() . '.php';
        if ($publish) {
            try {
                if ((new ModelInvocationWiringPublisher())->publish($root, $output, $generated) !== $generatedPath) {
                    throw ModelInvocationApplicationWorkflowException::failed('publish');
                }
            } catch (ModelInvocationApplicationWorkflowException $failure) {
                throw $failure;
            } catch (Throwable $failure) {
                throw ModelInvocationApplicationWorkflowException::failed('publish', $failure);
            }
        }
        $generatedSnapshot = $this->snapshot($generatedPath, ModelInvocationWiringGenerator::MAX_GENERATED_BYTES, 'generated_snapshot');
        try {
            (new ModelInvocationWiringValidator())->validateCurrent($profile, $generatedSnapshot['bytes']);
            $loaded = (new ModelInvocationWiringLoader())->load($root, $profile, $generatedPath);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationApplicationWorkflowException::failed('load', $failure);
        }
        $this->reverify($artifactSnapshot);
        $this->reverify($generatedSnapshot);

        return new CurrentModelInvocationApplicationBuild($root, $profile, $generated, $loaded, $artifactSnapshot, $generatedSnapshot);
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,int>} */
    private function snapshot(string $path, int $limit, string $stage): array
    {
        $resolved = realpath($path);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $path || is_link($path) || !is_file($path)) {
            throw ModelInvocationApplicationWorkflowException::failed($stage);
        }
        $before = $this->stat($path);
        if (false === $before || $before['size'] > $limit) {
            throw ModelInvocationApplicationWorkflowException::failed($stage);
        }
        $bytes = @file_get_contents($path);
        $after = $this->stat($path);
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw ModelInvocationApplicationWorkflowException::failed($stage);
        }

        return ['absolute' => $path, 'bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,int>} $snapshot */
    private function reverify(array $snapshot): void
    {
        $stat = $this->stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $stat || false === $bytes || !$this->sameMetadata($snapshot['stat'], $stat) || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw ModelInvocationApplicationWorkflowException::failed('reverify');
        }
    }

    /** @return array<string|int, int>|false */
    private function stat(string $path): array|false
    {
        return @stat($path);
    }

    /**
     * @param array<string|int, int> $before
     * @param array<string|int, int> $after
     */
    private function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
