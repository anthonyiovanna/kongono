<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\AggregateRepositoryGenerationImplementation;
use Kongono\PersistenceBuild\Internal\AggregateRepositorySourceCompiler;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceGenerationImplementation;
use ParseError;

/** Structurally proves generated repositories and exact-current identity without execution. */
#[Api(ApiStatus::Experimental)]
final class GeneratedAggregateRepositoryValidator
{
    public function validateStructure(string $generatedPhpBytes): GeneratedAggregateRepositoryArtifact
    {
        $this->validateBytes($generatedPhpBytes);
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([^;\n]+);\n/D', $generatedPhpBytes, $matches)) {
            $this->invalid();
        }
        try {
            $metadataValue = json_decode($matches[1], true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid();
        }
        $metadata = $this->object($metadataValue);
        if (
            array_keys($metadata) !== [
                'schema',
                'version',
                'aggregate_repository_source_path',
                'aggregate_repository_source_sha256',
                'persistence_artifact_sha256',
                'persistence_model_sha256',
                'persistence_state_generator_implementation_sha256',
                'aggregate_repository_generator_implementation_sha256',
                'generated_namespace',
                'class_map',
            ]
            || 'kongono.generated-aggregate-repositories' !== ($metadata['schema'] ?? null)
            || 1 !== ($metadata['version'] ?? null)
            || json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !== $matches[1]
        ) {
            $this->invalid();
        }
        $sourcePath = $metadata['aggregate_repository_source_path'] ?? null;
        if (!is_string($sourcePath) || basename($sourcePath) !== AggregateRepositorySourceCompiler::SOURCE_BASENAME || str_contains($sourcePath, '\\') || str_starts_with($sourcePath, '/') || str_contains($sourcePath, ':')) {
            $this->invalid();
        }
        foreach (explode('/', $sourcePath) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                $this->invalid();
            }
        }
        $sourceSha256 = $this->digest($metadata['aggregate_repository_source_sha256'] ?? null);
        $artifactSha256 = $this->digest($metadata['persistence_artifact_sha256'] ?? null);
        $modelSha256 = $this->digest($metadata['persistence_model_sha256'] ?? null);
        $stateGeneratorSha256 = $this->digest($metadata['persistence_state_generator_implementation_sha256'] ?? null);
        $generatorSha256 = $this->digest($metadata['aggregate_repository_generator_implementation_sha256'] ?? null);
        if (!hash_equals(PersistenceGenerationImplementation::generatorSha256(), $stateGeneratorSha256) || !hash_equals(AggregateRepositoryGenerationImplementation::generatorSha256(), $generatorSha256)) {
            $this->invalid();
        }
        $namespace = $this->phpName($metadata['generated_namespace'] ?? null);
        if ($matches[2] !== $namespace) {
            $this->invalid();
        }
        $mapValues = $metadata['class_map'] ?? null;
        if (!is_array($mapValues) || !array_is_list($mapValues) || [] === $mapValues || count($mapValues) > AggregateRepositorySourceCompiler::MAX_REPOSITORIES) {
            $this->invalid();
        }
        $classMap = [];
        $classes = [];
        $methodLists = [];
        $previousAggregate = null;
        foreach ($mapValues as $mapValue) {
            $map = $this->object($mapValue);
            if (array_keys($map) !== ['aggregate', 'repository', 'root_type', 'load_methods']) {
                $this->invalid();
            }
            $aggregate = $this->identifier($map['aggregate'] ?? null);
            $repository = $this->phpName($map['repository'] ?? null);
            $rootType = $this->phpName($map['root_type'] ?? null);
            $loadValues = $map['load_methods'] ?? null;
            if (!is_array($loadValues) || !array_is_list($loadValues) || [] === $loadValues || count($loadValues) > AggregateLoadPlan::MAX_LOAD_PLANS_PER_REPOSITORY || (null !== $previousAggregate && strcmp($previousAggregate, $aggregate) >= 0) || !str_starts_with($repository, $namespace . '\\')) {
                $this->invalid();
            }
            $loadMethods = [];
            foreach ($loadValues as $loadValue) {
                $method = $this->identifier($loadValue);
                if (isset($loadMethods[$method])) {
                    $this->invalid();
                }
                $loadMethods[$method] = true;
            }
            $short = substr($repository, strlen($namespace) + 1);
            if (str_contains($short, '\\') || isset($classMap[$aggregate])) {
                $this->invalid();
            }
            $classMap[$aggregate] = ['repository' => $repository, 'root_type' => $rootType, 'load_methods' => array_keys($loadMethods)];
            $classes[] = $short;
            $methodLists[] = array_keys($loadMethods);
            $previousAggregate = $aggregate;
        }
        $this->validateLines($generatedPhpBytes);
        $this->validateTokens($generatedPhpBytes, $classes, $methodLists);

        return new GeneratedAggregateRepositoryArtifact(
            $generatedPhpBytes,
            hash('sha256', $generatedPhpBytes),
            $sourcePath,
            $sourceSha256,
            $artifactSha256,
            $modelSha256,
            $stateGeneratorSha256,
            $generatorSha256,
            $namespace,
            $classMap,
        );
    }

    public function validateCurrent(
        string $applicationRoot,
        string $sourceRelativePath,
        string $persistenceArtifactBytes,
        string $generatedPhpBytes,
    ): GeneratedAggregateRepositoryArtifact {
        $artifact = $this->validateStructure($generatedPhpBytes);
        $current = (new GeneratedAggregateRepositoryGenerator())->generate($applicationRoot, $sourceRelativePath, $persistenceArtifactBytes);
        if (!hash_equals($current->canonicalBytes(), $generatedPhpBytes)) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::StaleGeneratedAggregateRepositories, PersistenceBuildFailureCategory::GeneratedAggregateCurrent);
        }

        return $artifact;
    }

    private function validateBytes(string $bytes): void
    {
        if (
            '' === $bytes
            || strlen($bytes) > GeneratedAggregateRepositoryGenerator::MAX_GENERATED_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            $this->invalid();
        }
    }

    private function validateLines(string $bytes): void
    {
        $blank = false;
        foreach (explode("\n", substr($bytes, 0, -1)) as $line) {
            if (str_contains($line, "\t") || rtrim($line, ' ') !== $line || ('' === $line && $blank)) {
                $this->invalid();
            }
            $blank = '' === $line;
        }
    }

    /**
     * @param list<string>       $expectedClasses
     * @param list<list<string>> $loadMethodLists
     */
    private function validateTokens(string $bytes, array $expectedClasses, array $loadMethodLists): void
    {
        if (1 === preg_match('/(?:->|::)\s*\$|\$\$|\b(?:__call|__get|__set|eval|unserialize|Reflection|beginTransaction|commit|rollBack|rollback|reconnect|retry|register_shutdown_function|serviceLocator|container)\b/i', $bytes)) {
            $this->invalid();
        }
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError) {
            $this->invalid();
        }
        $classes = [];
        $methods = [];
        $classOrdinal = -1;
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }
            if (
                in_array($token[0], [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_TRAIT, T_INTERFACE, T_ENUM, T_ATTRIBUTE, T_EXIT, T_HALT_COMPILER, T_YIELD, T_YIELD_FROM], true)
                || (T_VARIABLE === $token[0] && in_array($token[1], ['$GLOBALS', '$' . '_ENV', '$' . '_SERVER'], true))
                || (T_NAME_FULLY_QUALIFIED === $token[0] && str_starts_with(strtolower($token[1]), '\\reflection'))
            ) {
                $this->invalid();
            }
            if (T_CLASS === $token[0] && T_DOUBLE_COLON !== $this->previousSignificantTokenId($tokens, $index)) {
                $name = $this->followingName($tokens, $index);
                if (null === $name || T_FINAL !== $this->previousSignificantTokenId($tokens, $index)) {
                    $this->invalid();
                }
                $classes[] = $name;
                ++$classOrdinal;
                $methods[$classOrdinal] = [];
            }
            if (T_FUNCTION === $token[0]) {
                $name = $this->followingName($tokens, $index);
                if (null === $name || $classOrdinal < 0) {
                    $this->invalid();
                }
                $methods[$classOrdinal][] = $name;
            }
        }
        if ($classes !== $expectedClasses || count($methods) !== count($expectedClasses)) {
            $this->invalid();
        }
        foreach ($expectedClasses as $ordinal => $_class) {
            $loadMethods = $loadMethodLists[$ordinal] ?? null;
            if (null === $loadMethods) {
                $this->invalid();
            }
            $expectedMethods = ['aggregateName', 'rootType', 'loadPlans', 'cascadePlan', ...$loadMethods, 'persist', 'remove'];
            foreach (array_keys($loadMethods) as $planOrdinal) {
                $expectedMethods[] = 'loadPlan' . sprintf('%04d', $planOrdinal + 1);
            }
            array_push($expectedMethods, 'aggregateCascadePlan', 'assertCascadeAggregate', 'assertLoaded');
            if (($methods[$ordinal] ?? null) !== $expectedMethods) {
                $this->invalid();
            }
        }
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function followingName(array $tokens, int $after): ?string
    {
        for ($index = $after + 1, $count = count($tokens); $index < $count; ++$index) {
            if (is_array($tokens[$index]) && T_STRING === $tokens[$index][0]) {
                return $tokens[$index][1];
            }
            if ('{' === $tokens[$index] || ';' === $tokens[$index]) {
                return null;
            }
        }

        return null;
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function previousSignificantTokenId(array $tokens, int $before): ?int
    {
        for ($index = $before - 1; $index >= 0; --$index) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) ? $token[0] : null;
        }

        return null;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid();
        }

        return $value;
    }

    private function identifier(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 191 || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value)) {
            $this->invalid();
        }

        return $value;
    }

    private function phpName(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 255 || 1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $value)) {
            $this->invalid();
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid();
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    private function invalid(): never
    {
        throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidGeneratedAggregateRepositories, PersistenceBuildFailureCategory::GeneratedAggregateRepositories);
    }
}
