<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Build\PersistenceArtifactValidator;
use Kongono\PersistenceBuild\Exception\GeneratedPersistenceLoadException;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryArtifact;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryValidator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateArtifact;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateValidator;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/** Validates and loads exact-current generated state before aggregate repositories. */
#[Api(ApiStatus::Experimental)]
final class GeneratedPersistenceLoader
{
    public function loadCurrent(
        string $applicationRoot,
        string $persistenceSourceRelativePath,
        string $aggregateRepositorySourceRelativePath,
        string $persistenceArtifactBytes,
        string $generatedStatePhpFile,
        string $generatedRepositoryPhpFile,
    ): LoadedGeneratedPersistence {
        try {
            $root = $this->applicationRoot($applicationRoot);
            (new PersistenceArtifactValidator())->validateCurrent(
                $root,
                $persistenceSourceRelativePath,
                $persistenceArtifactBytes,
            );
            $stateSnapshot = $this->snapshot($root, $generatedStatePhpFile, GeneratedPersistenceStateGenerator::MAX_GENERATED_BYTES);
            $repositorySnapshot = $this->snapshot($root, $generatedRepositoryPhpFile, GeneratedAggregateRepositoryGenerator::MAX_GENERATED_BYTES);
            $state = (new GeneratedPersistenceStateValidator())->validateCurrent(
                $root,
                $persistenceSourceRelativePath,
                $persistenceArtifactBytes,
                $stateSnapshot['bytes'],
            );
            $repositories = (new GeneratedAggregateRepositoryValidator())->validateCurrent(
                $root,
                $aggregateRepositorySourceRelativePath,
                $persistenceArtifactBytes,
                $repositorySnapshot['bytes'],
            );
        } catch (Throwable $failure) {
            throw GeneratedPersistenceLoadException::failed($failure);
        }

        $this->assertBasename(
            $stateSnapshot['absolute'],
            'kongono-persistence-state-v1-' . $state->generatedFileSha256() . '.php',
        );
        $this->assertBasename(
            $repositorySnapshot['absolute'],
            'kongono-persistence-repositories-v1-' . $repositories->generatedFileSha256() . '.php',
        );
        $this->assertNoCollisions($state, $repositories);
        $this->reverify($stateSnapshot);
        $this->reverify($repositorySnapshot);

        try {
            require $stateSnapshot['absolute'];
            $this->validateStateClasses($stateSnapshot['absolute'], $state);
            $this->reverify($repositorySnapshot);
            require $repositorySnapshot['absolute'];
            $this->validateRepositoryClasses($repositorySnapshot['absolute'], $repositories);
        } catch (GeneratedPersistenceLoadException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw GeneratedPersistenceLoadException::failed($failure);
        }

        $aggregateMap = [];
        foreach ($repositories->classMap() as $aggregate => $definition) {
            $aggregateMap[$aggregate] = $definition['repository'];
        }

        return new LoadedGeneratedPersistence(
            $state->persistenceArtifactSha256(),
            $stateSnapshot['absolute'],
            $state->generatedFileSha256(),
            $state->generatedNamespace(),
            $state->classMap(),
            $repositorySnapshot['absolute'],
            $repositories->generatedFileSha256(),
            $repositories->generatedNamespace(),
            $aggregateMap,
        );
    }

    private function applicationRoot(string $applicationRoot): string
    {
        if ('' === $applicationRoot || str_contains($applicationRoot, "\0") || $this->hasStreamSyntax($applicationRoot)) {
            throw GeneratedPersistenceLoadException::failed();
        }
        $resolved = realpath($applicationRoot);
        if (false === $resolved || !is_dir($resolved) || is_link($applicationRoot)) {
            throw GeneratedPersistenceLoadException::failed();
        }

        return rtrim(self::normalize($resolved), '/');
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    private function snapshot(string $root, string $path, int $limit): array
    {
        if ('' === $path || str_contains($path, "\0") || $this->hasStreamSyntax($path)) {
            throw GeneratedPersistenceLoadException::failed();
        }
        $normalized = self::normalize($path);
        $resolved = realpath($path);
        if (
            false === $resolved
            || self::normalize($resolved) !== $normalized
            || !str_starts_with($normalized, $root . '/')
            || is_link($path)
            || !is_file($path)
        ) {
            throw GeneratedPersistenceLoadException::failed();
        }
        $relative = substr($normalized, strlen($root) + 1);
        $prefix = $root;
        foreach (explode('/', $relative) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw GeneratedPersistenceLoadException::failed();
            }
            $prefix .= '/' . $segment;
            $realPrefix = realpath($prefix);
            if (is_link($prefix) || false === $realPrefix || self::normalize($realPrefix) !== $prefix) {
                throw GeneratedPersistenceLoadException::failed();
            }
        }
        $before = @stat($normalized);
        if (false === $before || $before['size'] < 1 || $before['size'] > $limit) {
            throw GeneratedPersistenceLoadException::failed();
        }
        $handle = @fopen($normalized, 'rb');
        if (false === $handle) {
            throw GeneratedPersistenceLoadException::failed();
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, $limit + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw GeneratedPersistenceLoadException::failed();
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw GeneratedPersistenceLoadException::failed();
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw GeneratedPersistenceLoadException::failed();
        }

        return [
            'absolute' => $normalized,
            'bytes' => $bytes,
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'stat' => $before,
        ];
    }

    private function assertBasename(string $path, string $expected): void
    {
        if (basename($path) !== $expected) {
            throw GeneratedPersistenceLoadException::failed();
        }
    }

    private function assertNoCollisions(
        GeneratedPersistenceStateArtifact $state,
        GeneratedAggregateRepositoryArtifact $repositories,
    ): void {
        foreach ($state->classMap() as $class) {
            if (class_exists($class, false) || interface_exists($class, false) || enum_exists($class, false)) {
                throw GeneratedPersistenceLoadException::failed();
            }
        }
        foreach ($repositories->classMap() as $definition) {
            $class = $definition['repository'];
            if (class_exists($class, false) || interface_exists($class, false) || enum_exists($class, false)) {
                throw GeneratedPersistenceLoadException::failed();
            }
        }
    }

    private function validateStateClasses(string $expectedFile, GeneratedPersistenceStateArtifact $artifact): void
    {
        $expectedMethods = ['hydrate', 'extract', 'identity', 'snapshot', 'changes', 'insertion', 'deletion'];
        foreach ($artifact->classMap() as $class) {
            $this->validateClass($expectedFile, $class, $expectedMethods, true);
        }
    }

    private function validateRepositoryClasses(string $expectedFile, GeneratedAggregateRepositoryArtifact $artifact): void
    {
        foreach ($artifact->classMap() as $definition) {
            $expectedMethods = [
                'aggregateName',
                'rootType',
                'loadPlans',
                'cascadePlan',
                ...$definition['load_methods'],
                'persist',
                'remove',
            ];
            $this->validateClass($expectedFile, $definition['repository'], $expectedMethods, false);
        }
    }

    /** @param list<string> $expectedMethods */
    private function validateClass(string $expectedFile, string $class, array $expectedMethods, bool $static): void
    {
        if (!class_exists($class, false)) {
            throw GeneratedPersistenceLoadException::failed();
        }
        $reflection = new ReflectionClass($class);
        $declared = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($reflection->getName() === $method->getDeclaringClass()->getName()) {
                if ($method->isStatic() !== $static) {
                    throw GeneratedPersistenceLoadException::failed();
                }
                $declared[] = $method->getName();
            }
        }
        $declaringFile = $reflection->getFileName();
        if (
            !$reflection->isFinal()
            || !is_string($declaringFile)
            || self::normalize($declaringFile) !== $expectedFile
            || $declared !== $expectedMethods
        ) {
            throw GeneratedPersistenceLoadException::failed();
        }
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
    private function reverify(array $snapshot): void
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
            throw GeneratedPersistenceLoadException::failed();
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

    private function hasStreamSyntax(string $path): bool
    {
        $normalized = self::normalize($path);
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;

        return str_contains($withoutDrive, ':') || str_starts_with($normalized, '//');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
