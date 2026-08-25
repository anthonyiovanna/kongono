<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\InvalidGeneratedDataAccessException;
use Kongono\DatabaseMapping\Internal\CanonicalJson;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use Kongono\DatabaseMapping\Internal\MappingImplementation;
use ParseError;

/** Validates generated PHP structurally and against current evidence without execution. */
#[Api(ApiStatus::Experimental)]
final class GeneratedDataAccessValidator
{
    public function validateStructure(string $generatedPhpBytes): GeneratedDataAccessArtifact
    {
        $this->validateBytes($generatedPhpBytes);
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([^;\n]+);\n/D', $generatedPhpBytes, $matches)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        try {
            $decodedMetadata = json_decode($matches[1], true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $metadata = $this->object($decodedMetadata);
        if (CanonicalJson::inline($metadata) !== $matches[1]) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $expectedKeys = ['schema', 'version', 'mapping_artifact_sha256', 'model_sha256', 'generator_implementation_sha256', 'generated_namespace', 'classes'];
        if (array_keys($metadata) !== $expectedKeys || 'kongono.generated-data-access' !== ($metadata['schema'] ?? null) || 1 !== ($metadata['version'] ?? null)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $artifactSha256 = $this->digest($metadata['mapping_artifact_sha256'] ?? null);
        $modelSha256 = $this->digest($metadata['model_sha256'] ?? null);
        $implementationSha256 = $this->digest($metadata['generator_implementation_sha256'] ?? null);
        if (!hash_equals(MappingImplementation::generatorSha256(), $implementationSha256)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $namespace = $this->phpName($metadata['generated_namespace'] ?? null, false);
        if ($matches[2] !== $namespace) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $classValues = $metadata['classes'] ?? null;
        if (!is_array($classValues) || !array_is_list($classValues) || [] === $classValues || count($classValues) > 256) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $classes = [];
        $expectedClassNames = [];
        $expectedMethods = [];
        foreach ($classValues as $classValue) {
            if (!is_array($classValue) || array_is_list($classValue) || array_keys($classValue) !== ['record', 'mapper', 'repository']) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            $record = $this->phpName($classValue['record'] ?? null, true);
            $mapper = $this->phpName($classValue['mapper'] ?? null, true);
            $repository = $this->phpName($classValue['repository'] ?? null, true);
            if (!str_starts_with($mapper, $namespace . '\\') || !str_starts_with($repository, $namespace . '\\')) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            $mapperShort = substr($mapper, strlen($namespace) + 1);
            $repositoryShort = substr($repository, strlen($namespace) + 1);
            if (str_contains($mapperShort, '\\') || str_contains($repositoryShort, '\\')) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            $classes[] = ['record' => $record, 'mapper' => $mapper, 'repository' => $repository];
            $expectedClassNames[] = $mapperShort;
            $expectedClassNames[] = $repositoryShort;
            array_push($expectedMethods, 'hydrate', 'extract', '__construct', 'find', 'insert', 'update', 'delete');
            foreach ([
                'final class ' . $mapperShort,
                'public function hydrate(DatabaseRow $row): \\' . $record,
                'public function extract(\\' . $record . ' $record): array',
                'final class ' . $repositoryShort,
                'public function find(',
                'public function insert(\\' . $record . ' $record): CommandResult',
                'public function update(\\' . $record . ' $record): CommandResult',
                'public function delete(',
            ] as $required) {
                if (!str_contains($generatedPhpBytes, $required)) {
                    $this->invalid(MappingFailureCategory::GeneratedStructure);
                }
            }
        }
        $this->validateLines($generatedPhpBytes);
        $this->validateTokens($generatedPhpBytes, $expectedClassNames, $expectedMethods);

        return new GeneratedDataAccessArtifact(
            $generatedPhpBytes,
            hash('sha256', $generatedPhpBytes),
            $artifactSha256,
            $modelSha256,
            $implementationSha256,
            $namespace,
            $classes,
        );
    }

    public function validateCurrent(
        string $applicationRoot,
        string $sourceRelativePath,
        string $mappingArtifactBytes,
        string $generatedPhpBytes,
    ): GeneratedDataAccessArtifact {
        $artifact = $this->validateStructure($generatedPhpBytes);
        $current = (new GeneratedDataAccessGenerator())->generate($applicationRoot, $sourceRelativePath, $mappingArtifactBytes);
        if (!hash_equals($current->canonicalBytes(), $generatedPhpBytes)) {
            $this->invalid(MappingFailureCategory::GeneratedCurrent, true);
        }

        return $artifact;
    }

    private function validateBytes(string $bytes): void
    {
        if (
            '' === $bytes
            || strlen($bytes) > GeneratedDataAccessGenerator::MAX_GENERATED_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            $this->invalid(MappingFailureCategory::GeneratedBytes);
        }
    }

    private function validateLines(string $bytes): void
    {
        $blank = false;
        foreach (explode("\n", substr($bytes, 0, -1)) as $line) {
            if (str_contains($line, "\t") || rtrim($line, ' ') !== $line) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            if ('' === $line) {
                if ($blank) {
                    $this->invalid(MappingFailureCategory::GeneratedStructure);
                }
                $blank = true;
            } else {
                $blank = false;
            }
        }
    }

    /**
     * @param list<string> $expectedClasses
     * @param list<string> $expectedMethods
     */
    private function validateTokens(string $bytes, array $expectedClasses, array $expectedMethods): void
    {
        if (1 === preg_match('/(?:->|::)\s*\$/', $bytes) || 1 === preg_match('/\bnew\s+\$/i', $bytes)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
        $classes = [];
        $methods = [];
        $forbiddenTokens = [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_TRAIT, T_INTERFACE, T_ENUM, T_ATTRIBUTE];
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }
            if (in_array($token[0], $forbiddenTokens, true) || (T_VARIABLE === $token[0] && '$GLOBALS' === $token[1])) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            if (T_STRING === $token[0] && in_array(strtolower($token[1]), ['class_exists', 'interface_exists', 'method_exists', 'call_user_func', 'serialize', 'unserialize', 'getenv', 'putenv'], true)) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            if (T_CLASS === $token[0] || T_FUNCTION === $token[0]) {
                for ($next = $index + 1, $count = count($tokens); $next < $count; ++$next) {
                    if (is_array($tokens[$next]) && T_STRING === $tokens[$next][0]) {
                        if (T_CLASS === $token[0]) {
                            $classes[] = $tokens[$next][1];
                        } else {
                            $methods[] = $tokens[$next][1];
                        }
                        break;
                    }
                }
            }
        }
        if ($classes !== $expectedClasses || $methods !== $expectedMethods) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }

        return $value;
    }

    private function phpName(mixed $value, bool $requireNamespace): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 512 || ($requireNamespace && !str_contains($value, '\\')) || 1 !== preg_match('/^(?:[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\\\\)*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $value)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid(MappingFailureCategory::GeneratedStructure);
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                $this->invalid(MappingFailureCategory::GeneratedStructure);
            }
            $object[$key] = $item;
        }

        return $object;
    }

    private function invalid(MappingFailureCategory $category, bool $artifactCurrent = false): never
    {
        throw InvalidGeneratedDataAccessException::invalid($category, $artifactCurrent);
    }
}
