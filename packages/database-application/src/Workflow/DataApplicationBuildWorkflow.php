<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\DatabaseApplication\Exception\DataApplicationWorkflowException;
use Kongono\DatabaseMapping\Build\MappingArtifact;
use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\DatabaseMapping\Build\MappingArtifactPublisher;
use Kongono\DatabaseMapping\Build\MappingArtifactValidator;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessArtifact;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessGenerator;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessPublisher;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessValidator;
use Kongono\DatabaseMapping\Runtime\GeneratedDataAccessLoader;
use Throwable;

/** Builds or non-mutatingly checks mapping output before accepted composition output. */
#[Api(ApiStatus::Experimental)]
final class DataApplicationBuildWorkflow
{
    public function build(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $outputDirectory,
    ): CurrentDataApplicationBuild {
        [$root, $output] = $this->locations($applicationRoot, $outputDirectory);
        [$artifact, $generated] = $this->derive($root, $mappingSourceRelativePath);
        $paths = $this->expectedPaths($output, $artifact, $generated);

        try {
            if ((new MappingArtifactPublisher())->publish($artifact, $output) !== $paths['mapping']) {
                throw new DataApplicationWorkflowException('mapping_publication_path');
            }
            if ((new GeneratedDataAccessPublisher())->publish($generated, $output) !== $paths['generated']) {
                throw new DataApplicationWorkflowException('generated_publication_path');
            }
        } catch (DataApplicationWorkflowException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new DataApplicationWorkflowException('publication', $failure);
        }

        return $this->checked($root, $mappingSourceRelativePath, $output, $artifact, $generated, true);
    }

    public function check(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $outputDirectory,
    ): CurrentDataApplicationBuild {
        [$root, $output] = $this->locations($applicationRoot, $outputDirectory);
        [$artifact, $generated] = $this->derive($root, $mappingSourceRelativePath);

        return $this->checked($root, $mappingSourceRelativePath, $output, $artifact, $generated, false);
    }

    /** @return array{string, string} */
    private function locations(string $applicationRoot, string $outputDirectory): array
    {
        $root = realpath($applicationRoot);
        $output = realpath($outputDirectory);
        if (false === $root || false === $output || !is_dir($root) || !is_dir($output) || is_link($applicationRoot) || is_link($outputDirectory)) {
            throw new DataApplicationWorkflowException('invalid_location');
        }
        $root = rtrim(self::normalize($root), '/');
        $output = rtrim(self::normalize($output), '/');
        if ($root === $output || !str_starts_with($output, $root . '/')) {
            throw new DataApplicationWorkflowException('output_not_contained');
        }
        $prefix = $root;
        foreach (explode('/', substr($output, strlen($root) + 1)) as $segment) {
            $prefix .= '/' . $segment;
            $resolved = realpath($prefix);
            if (is_link($prefix) || false === $resolved || self::normalize($resolved) !== $prefix || !is_dir($prefix)) {
                throw new DataApplicationWorkflowException('unsafe_output');
            }
        }

        return [$root, $output];
    }

    /** @return array{MappingArtifact, GeneratedDataAccessArtifact} */
    private function derive(string $root, string $source): array
    {
        try {
            $artifact = (new MappingArtifactBuilder())->build($root, $source);
            $generated = (new GeneratedDataAccessGenerator())->generate($root, $source, $artifact->canonicalBytes());
        } catch (Throwable $failure) {
            throw new DataApplicationWorkflowException('mapping_derivation', $failure);
        }

        return [$artifact, $generated];
    }

    private function checked(
        string $root,
        string $source,
        string $output,
        MappingArtifact $artifact,
        GeneratedDataAccessArtifact $generated,
        bool $build,
    ): CurrentDataApplicationBuild {
        $paths = $this->expectedPaths($output, $artifact, $generated);
        $mappingSnapshot = $this->readExact($root, $paths['mapping'], MappingArtifactBuilder::MAX_ARTIFACT_BYTES);
        $generatedSnapshot = $this->readExact($root, $paths['generated'], GeneratedDataAccessGenerator::MAX_GENERATED_BYTES);
        try {
            if (!hash_equals($artifact->canonicalBytes(), $mappingSnapshot['bytes'])) {
                throw new DataApplicationWorkflowException('mapping_bytes_mismatch');
            }
            if (!hash_equals($generated->canonicalBytes(), $generatedSnapshot['bytes'])) {
                throw new DataApplicationWorkflowException('generated_bytes_mismatch');
            }
            (new MappingArtifactValidator())->validateCurrent($root, $source, $mappingSnapshot['bytes']);
            (new GeneratedDataAccessValidator())->validateCurrent($root, $source, $mappingSnapshot['bytes'], $generatedSnapshot['bytes']);
            $loaded = (new GeneratedDataAccessLoader())->loadCurrent($root, $source, $mappingSnapshot['bytes'], $paths['generated']);
        } catch (DataApplicationWorkflowException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new DataApplicationWorkflowException('mapping_check', $failure);
        }

        try {
            $application = $build
                ? (new ApplicationBuildWorkflow())->build($root, $output)
                : (new ApplicationBuildWorkflow())->check($root, $output);
        } catch (Throwable $failure) {
            throw new DataApplicationWorkflowException('composition_workflow', $failure);
        }

        $this->reverify($mappingSnapshot);
        $this->reverify($generatedSnapshot);

        return new CurrentDataApplicationBuild(
            $paths['mapping'],
            $mappingSnapshot['bytes'],
            $artifact->artifactSha256(),
            $paths['generated'],
            $generated->generatedFileSha256(),
            $loaded,
            $application,
        );
    }

    /** @return array{mapping:string, generated:string} */
    private function expectedPaths(string $output, MappingArtifact $artifact, GeneratedDataAccessArtifact $generated): array
    {
        return [
            'mapping' => $output . '/kongono-data-mapping-v1-' . $artifact->artifactSha256() . '.json',
            'generated' => $output . '/kongono-data-access-v1-' . $generated->generatedFileSha256() . '.php',
        ];
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    private function readExact(string $root, string $path, int $limit): array
    {
        if (!str_starts_with($path, $root . '/')) {
            throw new DataApplicationWorkflowException('unsafe_output_file');
        }
        $resolved = realpath($path);
        if (false === $resolved || self::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw new DataApplicationWorkflowException('required_output_missing');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > $limit) {
            throw new DataApplicationWorkflowException('unsafe_output_file');
        }
        $bytes = @file_get_contents($path);
        $after = @stat($path);
        if (false === $bytes || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new DataApplicationWorkflowException('changed_during_read');
        }

        return ['absolute' => $path, 'bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
    private function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $stat || !$this->sameMetadata($snapshot['stat'], $stat) || false === $bytes || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw new DataApplicationWorkflowException('changed_during_read');
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
