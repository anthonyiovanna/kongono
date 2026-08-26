<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Exception\InvalidConsoleArtifactException;
use Kongono\ConsoleBuild\Exception\StaleConsoleArtifactException;
use Kongono\ConsoleBuild\Internal\CanonicalJson;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;
use Kongono\ConsoleBuild\Internal\ConsoleBuildImplementation;
use Kongono\ConsoleBuild\Internal\ConsoleModelCompiler;
use Kongono\ConsoleBuild\Internal\PathGuard;
use Throwable;

/** Separately proves command artifact structure and exact current equality. */
#[Api(ApiStatus::Experimental)]
final class ConsoleArtifactValidator
{
    public function validateStructure(string $artifactBytes): ConsoleArtifact
    {
        if (
            '' === $artifactBytes
            || strlen($artifactBytes) > ConsoleArtifactBuilder::MAX_ARTIFACT_BYTES
            || str_starts_with($artifactBytes, "\xEF\xBB\xBF")
            || str_contains($artifactBytes, "\r")
            || str_contains($artifactBytes, "\0")
            || 1 !== preg_match('//u', $artifactBytes)
            || !str_ends_with($artifactBytes, "\n")
            || str_ends_with($artifactBytes, "\n\n")
        ) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactBytes);
        }
        try {
            $artifact = CanonicalJson::decodeObject($artifactBytes, 64);
        } catch (JsonException) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
        if (CanonicalJson::encode($artifact) !== $artifactBytes) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
        $this->assertKeys($artifact, ['schema', 'version', 'model_schema', 'model_version', 'source', 'provenance', 'model']);
        if (
            'kongono.command-artifact' !== ($artifact['schema'] ?? null)
            || 1 !== ($artifact['version'] ?? null)
            || 'kongono.command-model' !== ($artifact['model_schema'] ?? null)
            || 1 !== ($artifact['model_version'] ?? null)
        ) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }

        $source = $this->sourceIdentity($artifact['source'] ?? null);
        $provenance = $this->object($artifact['provenance'] ?? null);
        $this->assertKeys($provenance, ['console_builder', 'command_source', 'command_model']);
        $builder = $this->object($provenance['console_builder'] ?? null);
        $this->assertKeys($builder, ['package', 'contract_version', 'implementation_sha256']);
        if ('kongono/console-build' !== ($builder['package'] ?? null) || 1 !== ($builder['contract_version'] ?? null)) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
        $implementationSha256 = $this->digest($builder['implementation_sha256'] ?? null);
        if (!hash_equals(ConsoleBuildImplementation::builderSha256(), $implementationSha256)) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
        }
        if ($source !== $this->sourceIdentity($provenance['command_source'] ?? null)) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
        }

        $modelData = $this->object($artifact['model'] ?? null);
        try {
            $compiler = new ConsoleModelCompiler();
            $model = $compiler->hydrateModel($modelData);
        } catch (Throwable) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
        $modelBytes = CanonicalJson::encode($modelData);
        if (CanonicalJson::encode($compiler->modelData($model)) !== $modelBytes) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
        $modelIdentity = $this->byteIdentity($provenance['command_model'] ?? null, ConsoleArtifactBuilder::MAX_ARTIFACT_BYTES);
        if ($modelIdentity['bytes'] !== strlen($modelBytes) || !hash_equals($modelIdentity['sha256'], hash('sha256', $modelBytes))) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
        }
        $normalizedSourceBytes = CanonicalJson::encode([
            'schema' => 'kongono.command-source',
            'version' => 1,
            'commands' => $modelData['commands'],
        ]);
        if ($source['bytes'] !== strlen($normalizedSourceBytes) || !hash_equals($source['sha256'], hash('sha256', $normalizedSourceBytes))) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactIdentity);
        }

        return new ConsoleArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $source['sha256'],
            $modelIdentity['sha256'],
            $implementationSha256,
            $model,
        );
    }

    public function validateCurrent(string $applicationRoot, string $sourceRelativePath, string $artifactBytes): ConsoleArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new ConsoleArtifactBuilder())->build($applicationRoot, $sourceRelativePath);
        if (!hash_equals($current->canonicalBytes(), $artifactBytes)) {
            throw StaleConsoleArtifactException::currentMismatch();
        }

        return $artifact;
    }

    /** @return array{path:string,bytes:int,sha256:string} */
    private function sourceIdentity(mixed $value): array
    {
        $identity = $this->object($value);
        $this->assertKeys($identity, ['path', 'bytes', 'sha256']);
        $path = $identity['path'] ?? null;
        if (!is_string($path) || PathGuard::SOURCE_BASENAME !== basename($path) || str_contains($path, '\\') || str_starts_with($path, '/') || str_contains($path, ':')) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
            }
        }

        return [
            'path' => $path,
            'bytes' => $this->positiveInteger($identity['bytes'] ?? null, PathGuard::MAX_SOURCE_BYTES),
            'sha256' => $this->digest($identity['sha256'] ?? null),
        ];
    }

    /** @return array{bytes:int,sha256:string} */
    private function byteIdentity(mixed $value, int $maximum): array
    {
        $identity = $this->object($value);
        $this->assertKeys($identity, ['bytes', 'sha256']);

        return [
            'bytes' => $this->positiveInteger($identity['bytes'] ?? null, $maximum),
            'sha256' => $this->digest($identity['sha256'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private function assertKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }
    }

    private function positiveInteger(mixed $value, int $maximum): int
    {
        if (!is_int($value) || $value < 1 || $value > $maximum) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid(ConsoleBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function invalid(ConsoleBuildFailureCategory $category): never
    {
        throw InvalidConsoleArtifactException::invalid($category);
    }
}
