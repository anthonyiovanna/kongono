<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Build\MappingArtifactValidator;
use Kongono\DatabaseMapping\Exception\ExceptionInterface;
use Kongono\DatabaseMapping\Exception\GeneratedDataAccessLoadException;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessArtifact;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessValidator;
use Kongono\DatabaseMapping\Internal\PathGuard;
use ReflectionClass;
use ReflectionMethod;

/** Admits and loads one caller-selected exact-current generated data-access file. */
#[Api(ApiStatus::Experimental)]
final class GeneratedDataAccessLoader
{
    public function loadCurrent(
        string $applicationRoot,
        string $sourceRelativePath,
        string $mappingArtifactBytes,
        string $generatedPhpFile,
    ): LoadedGeneratedDataAccess {
        try {
            $root = PathGuard::resolveApplicationRoot($applicationRoot);
            $snapshot = PathGuard::readGeneratedFile($root, $generatedPhpFile);
            (new MappingArtifactValidator())->validateCurrent($root, $sourceRelativePath, $mappingArtifactBytes);
            $artifact = (new GeneratedDataAccessValidator())->validateCurrent(
                $root,
                $sourceRelativePath,
                $mappingArtifactBytes,
                $snapshot['bytes'],
            );
        } catch (ExceptionInterface $failure) {
            throw GeneratedDataAccessLoadException::failed($failure);
        }

        $expectedBasename = 'kongono-data-access-v1-' . $artifact->generatedFileSha256() . '.php';
        if (basename(str_replace('\\', '/', $snapshot['absolute'])) !== $expectedBasename) {
            throw GeneratedDataAccessLoadException::failed();
        }

        foreach ($artifact->classes() as $class) {
            if (class_exists($class['mapper'], false) || class_exists($class['repository'], false)) {
                throw GeneratedDataAccessLoadException::failed();
            }
        }

        try {
            PathGuard::reverify($snapshot);
        } catch (ExceptionInterface $failure) {
            throw GeneratedDataAccessLoadException::failed($failure);
        }

        require $snapshot['absolute'];
        $this->validateLoadedShape($snapshot['absolute'], $artifact);

        return new LoadedGeneratedDataAccess(
            $artifact->generatedFileSha256(),
            $artifact->mappingArtifactSha256(),
            $artifact->generatedNamespace(),
            $artifact->classes(),
        );
    }

    private function validateLoadedShape(string $expectedFile, GeneratedDataAccessArtifact $artifact): void
    {
        foreach ($artifact->classes() as $class) {
            $this->validateClass($expectedFile, $class['mapper'], ['hydrate', 'extract']);
            $this->validateClass($expectedFile, $class['repository'], ['__construct', 'find', 'insert', 'update', 'delete']);
        }
    }

    /** @param list<string> $expectedMethods */
    private function validateClass(string $expectedFile, string $class, array $expectedMethods): void
    {
        if (!class_exists($class, false)) {
            throw GeneratedDataAccessLoadException::failed();
        }
        $reflection = new ReflectionClass($class);
        $declared = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($reflection->getName() === $method->getDeclaringClass()->getName()) {
                $declared[] = $method->getName();
            }
        }
        $declaringFile = $reflection->getFileName();
        if (
            !$reflection->isFinal()
            || !is_string($declaringFile)
            || str_replace('\\', '/', $declaringFile) !== $expectedFile
            || $declared !== $expectedMethods
        ) {
            throw GeneratedDataAccessLoadException::failed();
        }
    }
}
