<?php

declare(strict_types=1);

namespace Kongono\Composition\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Build\CompositionArtifact;
use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Composition\Build\CompositionArtifactPublisher;
use Kongono\Composition\Build\CompositionArtifactValidator;
use Kongono\Composition\Exception\ApplicationWorkflowException;
use Kongono\Composition\Generated\GeneratedWiringArtifact;
use Kongono\Composition\Generated\GeneratedWiringGenerator;
use Kongono\Composition\Generated\GeneratedWiringPublisher;
use Kongono\Composition\Generated\GeneratedWiringValidator;
use Kongono\Composition\Internal\PathGuard;
use Throwable;

/** Builds or non-mutatingly checks one exact current application assembly. */
#[Api(ApiStatus::Experimental)]
final class ApplicationBuildWorkflow
{
    public function build(string $applicationRoot, string $outputDirectory): CurrentApplicationBuild
    {
        $root = $this->applicationRoot($applicationRoot);
        $output = $this->outputDirectory($root, $outputDirectory);
        $artifact = $this->artifact($root);
        $wiring = $this->wiring($root, $artifact);
        $paths = $this->expectedPaths($output, $artifact, $wiring);

        try {
            $publishedArtifact = (new CompositionArtifactPublisher())->publish($artifact, $output);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('artifact_publication', 'artifact_publication_failed', $failure);
        }
        if ($publishedArtifact !== $paths['artifact']) {
            throw new ApplicationWorkflowException('artifact_publication', 'publication_path_mismatch');
        }

        try {
            $publishedWiring = (new GeneratedWiringPublisher())->publish($wiring, $output);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('wiring_publication', 'wiring_publication_failed', $failure);
        }
        if ($publishedWiring !== $paths['wiring']) {
            throw new ApplicationWorkflowException('wiring_publication', 'publication_path_mismatch');
        }

        return $this->checked($root, $output, $artifact, $wiring);
    }

    public function check(string $applicationRoot, string $outputDirectory): CurrentApplicationBuild
    {
        $root = $this->applicationRoot($applicationRoot);
        $output = $this->outputDirectory($root, $outputDirectory);
        $artifact = $this->artifact($root);
        $wiring = $this->wiring($root, $artifact);

        return $this->checked($root, $output, $artifact, $wiring);
    }

    private function applicationRoot(string $applicationRoot): string
    {
        try {
            return PathGuard::resolveApplicationRoot($applicationRoot);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('application_root', 'invalid_application_root', $failure);
        }
    }

    private function outputDirectory(string $root, string $outputDirectory): string
    {
        try {
            $output = PathGuard::resolveOutputDirectory($outputDirectory);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('output_directory', 'invalid_output_directory', $failure);
        }
        if ($output === $root || !str_starts_with($output, $root . '/')) {
            throw new ApplicationWorkflowException('output_directory', 'output_not_strictly_contained');
        }

        $prefix = $root;
        foreach (explode('/', substr($output, strlen($root) + 1)) as $segment) {
            $prefix .= '/' . $segment;
            $resolved = realpath($prefix);
            if (is_link($prefix) || false === $resolved || self::normalize($resolved) !== $prefix || !is_dir($prefix)) {
                throw new ApplicationWorkflowException('output_directory', 'unsafe_output_directory');
            }
        }

        return $output;
    }

    private function artifact(string $root): CompositionArtifact
    {
        try {
            return (new CompositionArtifactBuilder())->build($root);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('artifact_build', 'artifact_build_failed', $failure);
        }
    }

    private function wiring(string $root, CompositionArtifact $artifact): GeneratedWiringArtifact
    {
        try {
            return (new GeneratedWiringGenerator())->generate($root, $artifact->canonicalBytes());
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('wiring_generation', 'wiring_generation_failed', $failure);
        }
    }

    private function checked(
        string $root,
        string $output,
        CompositionArtifact $artifact,
        GeneratedWiringArtifact $wiring,
    ): CurrentApplicationBuild {
        $paths = $this->expectedPaths($output, $artifact, $wiring);
        $artifactSnapshot = $this->readExact($root, $paths['artifact'], CompositionArtifactBuilder::MAX_ARTIFACT_BYTES, 'artifact_check');
        if (!hash_equals($artifact->canonicalBytes(), $artifactSnapshot['bytes'])) {
            throw new ApplicationWorkflowException('artifact_check', 'artifact_bytes_mismatch');
        }
        try {
            (new CompositionArtifactValidator())->validateCurrent($root, $artifactSnapshot['bytes']);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('artifact_check', 'current_validation_failed', $failure);
        }

        $wiringSnapshot = $this->readExact($root, $paths['wiring'], GeneratedWiringGenerator::MAX_GENERATED_BYTES, 'wiring_check');
        if (!hash_equals($wiring->canonicalBytes(), $wiringSnapshot['bytes'])) {
            throw new ApplicationWorkflowException('wiring_check', 'wiring_bytes_mismatch');
        }
        try {
            (new GeneratedWiringValidator())->validateCurrent($root, $artifactSnapshot['bytes'], $wiringSnapshot['bytes']);
        } catch (Throwable $failure) {
            throw new ApplicationWorkflowException('wiring_check', 'current_validation_failed', $failure);
        }

        $this->reverify($artifactSnapshot, 'artifact_check');
        $this->reverify($wiringSnapshot, 'wiring_check');

        return new CurrentApplicationBuild(
            $paths['artifact'],
            $artifactSnapshot['bytes'],
            $artifact->artifactSha256(),
            $wiring->wiringSha256(),
            $paths['wiring'],
        );
    }

    /** @return array{artifact: string, wiring: string} */
    private function expectedPaths(string $output, CompositionArtifact $artifact, GeneratedWiringArtifact $wiring): array
    {
        return [
            'artifact' => $output . '/kongono-composition-v1-' . $artifact->artifactSha256() . '.json',
            'wiring' => $output . '/kongono-wiring-v1-' . $wiring->wiringSha256() . '.php',
        ];
    }

    /**
     * @return array{absolute: string, bytes: string, size: int, sha256: string, stat: array<string|int, mixed>}
     */
    private function readExact(string $root, string $path, int $limit, string $stage): array
    {
        if (!str_starts_with($path, $root . '/')) {
            throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
        }
        $resolved = realpath($path);
        if (false === $resolved) {
            throw new ApplicationWorkflowException($stage, 'required_file_missing');
        }
        if (self::normalize($resolved) !== $path || is_link($path) || !is_file($path)) {
            throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
        }

        $prefix = $root;
        foreach (explode('/', substr($path, strlen($root) + 1)) as $segment) {
            $prefix .= '/' . $segment;
            $resolvedPrefix = realpath($prefix);
            if (is_link($prefix) || false === $resolvedPrefix || self::normalize($resolvedPrefix) !== $prefix) {
                throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
            }
        }

        $before = @stat($path);
        if (false === $before || $before['size'] > $limit) {
            throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw new ApplicationWorkflowException($stage, 'unsafe_output_file');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new ApplicationWorkflowException($stage, 'changed_during_read');
        }

        return ['absolute' => $path, 'bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{absolute: string, bytes: string, size: int, sha256: string, stat: array<string|int, mixed>} $snapshot */
    private function reverify(array $snapshot, string $stage): void
    {
        $stat = @stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (
            false === $stat
            || !$this->sameMetadata($snapshot['stat'], $stat)
            || false === $bytes
            || strlen($bytes) !== $snapshot['size']
            || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))
        ) {
            throw new ApplicationWorkflowException($stage, 'changed_during_read');
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
