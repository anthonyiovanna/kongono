<?php

declare(strict_types=1);

namespace Kongono\PersistenceApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Composition\Generated\GeneratedWiringGenerator;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\DatabaseApplication\Workflow\DataAccessBuildWorkflow;
use Kongono\PersistenceApplication\Exception\PersistenceApplicationWorkflowException;
use Kongono\PersistenceBuild\Build\PersistenceArtifact;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Build\PersistenceArtifactPublisher;
use Kongono\PersistenceBuild\Build\PersistenceArtifactValidator;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryArtifact;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryPublisher;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateArtifact;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStatePublisher;
use Kongono\PersistenceBuild\Runtime\GeneratedPersistenceLoader;
use Throwable;

/** Orders data access persistence loading and Composition under exact-current evidence. */
#[Api(ApiStatus::Experimental)]
final class PersistenceApplicationBuildWorkflow
{
    public function build(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $persistenceSourceRelativePath,
        string $aggregateRepositorySourceRelativePath,
        string $outputDirectory,
    ): CurrentPersistenceApplicationBuild {
        return $this->run(
            $applicationRoot,
            $mappingSourceRelativePath,
            $persistenceSourceRelativePath,
            $aggregateRepositorySourceRelativePath,
            $outputDirectory,
            true,
        );
    }

    public function check(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $persistenceSourceRelativePath,
        string $aggregateRepositorySourceRelativePath,
        string $outputDirectory,
    ): CurrentPersistenceApplicationBuild {
        return $this->run(
            $applicationRoot,
            $mappingSourceRelativePath,
            $persistenceSourceRelativePath,
            $aggregateRepositorySourceRelativePath,
            $outputDirectory,
            false,
        );
    }

    private function run(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $persistenceSourceRelativePath,
        string $aggregateRepositorySourceRelativePath,
        string $outputDirectory,
        bool $build,
    ): CurrentPersistenceApplicationBuild {
        [$root, $output] = $this->locations($applicationRoot, $outputDirectory);
        $dataWorkflow = new DataAccessBuildWorkflow();
        try {
            $preparedData = $build
                ? $dataWorkflow->prepareBuild($root, $mappingSourceRelativePath, $output)
                : $dataWorkflow->prepareCheck($root, $mappingSourceRelativePath, $output);
        } catch (Throwable $failure) {
            throw new PersistenceApplicationWorkflowException('data_access', $failure);
        }

        try {
            $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSourceRelativePath);
            $state = (new GeneratedPersistenceStateGenerator())->generate(
                $root,
                $persistenceSourceRelativePath,
                $persistence->canonicalBytes(),
            );
            $repositories = (new GeneratedAggregateRepositoryGenerator())->generate(
                $root,
                $aggregateRepositorySourceRelativePath,
                $persistence->canonicalBytes(),
            );
        } catch (Throwable $failure) {
            throw new PersistenceApplicationWorkflowException('persistence_derivation', $failure);
        }

        $paths = $this->expectedPaths($output, $persistence, $state, $repositories);
        if ($build) {
            $this->publish($output, $paths, $persistence, $state, $repositories);
        }

        $persistenceSnapshot = $this->readExact($root, $paths['persistence'], PersistenceArtifactBuilder::MAX_ARTIFACT_BYTES);
        $stateSnapshot = $this->readExact($root, $paths['state'], GeneratedPersistenceStateGenerator::MAX_GENERATED_BYTES);
        $repositorySnapshot = $this->readExact($root, $paths['repositories'], GeneratedAggregateRepositoryGenerator::MAX_GENERATED_BYTES);
        if (
            !hash_equals($persistence->canonicalBytes(), $persistenceSnapshot['bytes'])
            || !hash_equals($state->canonicalBytes(), $stateSnapshot['bytes'])
            || !hash_equals($repositories->canonicalBytes(), $repositorySnapshot['bytes'])
        ) {
            throw new PersistenceApplicationWorkflowException('persistence_bytes_mismatch');
        }

        try {
            (new PersistenceArtifactValidator())->validateCurrent(
                $root,
                $persistenceSourceRelativePath,
                $persistenceSnapshot['bytes'],
            );
            $loaded = (new GeneratedPersistenceLoader())->loadCurrent(
                $root,
                $persistenceSourceRelativePath,
                $aggregateRepositorySourceRelativePath,
                $persistenceSnapshot['bytes'],
                $paths['state'],
                $paths['repositories'],
            );
            $data = $dataWorkflow->loadPrepared($preparedData);
        } catch (Throwable $failure) {
            throw new PersistenceApplicationWorkflowException('persistence_load', $failure);
        }

        try {
            $application = $build
                ? (new ApplicationBuildWorkflow())->build($root, $output)
                : (new ApplicationBuildWorkflow())->check($root, $output);
        } catch (Throwable $failure) {
            throw new PersistenceApplicationWorkflowException('composition_workflow', $failure);
        }

        $compositionSnapshot = $this->readExact(
            $root,
            $application->compositionArtifactPath(),
            CompositionArtifactBuilder::MAX_ARTIFACT_BYTES,
        );
        $wiringSnapshot = $this->readExact(
            $root,
            $application->generatedPhpPath(),
            GeneratedWiringGenerator::MAX_GENERATED_BYTES,
        );
        $dataWorkflow->reverify($data);
        $this->reverify($persistenceSnapshot);
        $this->reverify($stateSnapshot);
        $this->reverify($repositorySnapshot);
        $this->reverify($compositionSnapshot);
        $this->reverify($wiringSnapshot);

        return new CurrentPersistenceApplicationBuild(
            $data,
            $paths['persistence'],
            $persistenceSnapshot['bytes'],
            $persistence->artifactSha256(),
            $paths['state'],
            $state->generatedFileSha256(),
            $paths['repositories'],
            $repositories->generatedFileSha256(),
            $loaded,
            $application,
        );
    }

    /**
     * @param array{persistence:string,state:string,repositories:string} $paths
     */
    private function publish(
        string $output,
        array $paths,
        PersistenceArtifact $persistence,
        GeneratedPersistenceStateArtifact $state,
        GeneratedAggregateRepositoryArtifact $repositories,
    ): void {
        try {
            if ((new PersistenceArtifactPublisher())->publish($persistence, $output) !== $paths['persistence']) {
                throw new PersistenceApplicationWorkflowException('persistence_publication_path');
            }
            if ((new GeneratedPersistenceStatePublisher())->publish($state, $output) !== $paths['state']) {
                throw new PersistenceApplicationWorkflowException('state_publication_path');
            }
            if ((new GeneratedAggregateRepositoryPublisher())->publish($repositories, $output) !== $paths['repositories']) {
                throw new PersistenceApplicationWorkflowException('repository_publication_path');
            }
        } catch (PersistenceApplicationWorkflowException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new PersistenceApplicationWorkflowException('persistence_publication', $failure);
        }
    }

    /** @return array{string,string} */
    private function locations(string $applicationRoot, string $outputDirectory): array
    {
        $root = realpath($applicationRoot);
        $output = realpath($outputDirectory);
        if (false === $root || false === $output || !is_dir($root) || !is_dir($output) || is_link($applicationRoot) || is_link($outputDirectory)) {
            throw new PersistenceApplicationWorkflowException('invalid_location');
        }
        $root = rtrim(self::normalize($root), '/');
        $output = rtrim(self::normalize($output), '/');
        if ($root === $output || !str_starts_with($output, $root . '/')) {
            throw new PersistenceApplicationWorkflowException('output_not_contained');
        }
        $prefix = $root;
        foreach (explode('/', substr($output, strlen($root) + 1)) as $segment) {
            $prefix .= '/' . $segment;
            $resolved = realpath($prefix);
            if (is_link($prefix) || false === $resolved || self::normalize($resolved) !== $prefix || !is_dir($prefix)) {
                throw new PersistenceApplicationWorkflowException('unsafe_output');
            }
        }

        return [$root, $output];
    }

    /** @return array{persistence:string,state:string,repositories:string} */
    private function expectedPaths(
        string $output,
        PersistenceArtifact $persistence,
        GeneratedPersistenceStateArtifact $state,
        GeneratedAggregateRepositoryArtifact $repositories,
    ): array {
        return [
            'persistence' => $output . '/persistence-artifacts/v1/kongono-persistence-v1-' . $persistence->artifactSha256() . '.json',
            'state' => $output . '/kongono-persistence-state-v1-' . $state->generatedFileSha256() . '.php',
            'repositories' => $output . '/kongono-persistence-repositories-v1-' . $repositories->generatedFileSha256() . '.php',
        ];
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    private function readExact(string $root, string $path, int $limit): array
    {
        if (!str_starts_with($path, $root . '/')) {
            throw new PersistenceApplicationWorkflowException('unsafe_output_file');
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw new PersistenceApplicationWorkflowException('required_output_missing');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] < 1 || $before['size'] > $limit) {
            throw new PersistenceApplicationWorkflowException('unsafe_output_file');
        }
        $bytes = @file_get_contents($path);
        $after = @stat($path);
        if (false === $bytes || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new PersistenceApplicationWorkflowException('changed_during_read');
        }

        return ['absolute' => $path, 'bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
    private function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $stat || !$this->sameMetadata($snapshot['stat'], $stat) || false === $bytes || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw new PersistenceApplicationWorkflowException('changed_during_read');
        }
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
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

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
