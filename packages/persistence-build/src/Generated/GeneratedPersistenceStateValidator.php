<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceGenerationImplementation;
use ParseError;

/** Proves generated-state structure and exact-current identity without execution. */
#[Api(ApiStatus::Experimental)]
final class GeneratedPersistenceStateValidator
{
    public function validateStructure(string $generatedPhpBytes): GeneratedPersistenceStateArtifact
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
                'persistence_artifact_sha256',
                'persistence_model_sha256',
                'persistence_builder_implementation_sha256',
                'persistence_generator_implementation_sha256',
                'generated_namespace',
                'class_map',
            ]
            || 'kongono.generated-persistence-state' !== ($metadata['schema'] ?? null)
            || 1 !== ($metadata['version'] ?? null)
            || json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !== $matches[1]
        ) {
            $this->invalid();
        }
        $artifactSha256 = $this->digest($metadata['persistence_artifact_sha256'] ?? null);
        $modelSha256 = $this->digest($metadata['persistence_model_sha256'] ?? null);
        $builderSha256 = $this->digest($metadata['persistence_builder_implementation_sha256'] ?? null);
        $generatorSha256 = $this->digest($metadata['persistence_generator_implementation_sha256'] ?? null);
        if (!hash_equals(PersistenceGenerationImplementation::generatorSha256(), $generatorSha256)) {
            $this->invalid();
        }
        $namespace = $this->phpName($metadata['generated_namespace'] ?? null);
        if ($matches[2] !== $namespace || !str_ends_with($namespace, '\\Artifact_' . substr($artifactSha256, 0, 32))) {
            $this->invalid();
        }
        $mapValues = $metadata['class_map'] ?? null;
        if (!is_array($mapValues) || !array_is_list($mapValues) || [] === $mapValues || count($mapValues) > 256) {
            $this->invalid();
        }
        $classMap = [];
        $expectedClasses = [];
        $expectedMethods = [];
        foreach ($mapValues as $ordinal => $mapValue) {
            $map = $this->object($mapValue);
            if (array_keys($map) !== ['entity', 'state_adapter']) {
                $this->invalid();
            }
            $entity = $this->phpName($map['entity'] ?? null);
            $adapter = $this->phpName($map['state_adapter'] ?? null);
            $short = sprintf('EntityState_%04d', $ordinal + 1);
            if ($adapter !== $namespace . '\\' . $short || array_key_exists($entity, $classMap)) {
                $this->invalid();
            }
            $classMap[$entity] = $adapter;
            $expectedClasses[] = $short;
            array_push($expectedMethods, '__construct', 'hydrate', 'extract', 'identity', 'snapshot', 'changes', 'insertion', 'deletion');
            foreach ([
                'final class ' . $short,
                'public static function hydrate(array $record): \\' . $entity,
                'public static function extract(\\' . $entity . ' $entity): array',
                'public static function identity(\\' . $entity . ' $entity): EntityIdentity',
                'public static function snapshot(\\' . $entity . ' $entity): array',
                'public static function changes(\\' . $entity . ' $entity, array $snapshot): ChangeSet',
                'public static function insertion(\\' . $entity . ' $entity): ChangeSet',
                'public static function deletion(\\' . $entity . ' $entity): ChangeSet',
            ] as $required) {
                if (1 !== substr_count($generatedPhpBytes, $required)) {
                    $this->invalid();
                }
            }
        }
        $preamble = implode("\n", [
            'namespace ' . $namespace . ';',
            '',
            'use Kongono\\Persistence\\ChangeKind;',
            'use Kongono\\Persistence\\ChangeSet;',
            'use Kongono\\Persistence\\ConcurrencyToken;',
            'use Kongono\\Persistence\\EntityIdentity;',
            'use Kongono\\Persistence\\PersistenceChange;',
            'use Kongono\\PersistenceBuild\\Exception\\InvalidGeneratedPersistenceStateException;',
            '',
            '/** Provides detached state evidence through authenticated application construction and accessors. */',
            'final class ' . $expectedClasses[0],
        ]);
        if (1 !== substr_count($generatedPhpBytes, $preamble)) {
            $this->invalid();
        }
        if (count($classMap) !== substr_count($generatedPhpBytes, 'private function __construct() {}')) {
            $this->invalid();
        }
        $this->validateLines($generatedPhpBytes);
        $this->validateTokens($generatedPhpBytes, $expectedClasses, $expectedMethods);

        return new GeneratedPersistenceStateArtifact(
            $generatedPhpBytes,
            hash('sha256', $generatedPhpBytes),
            $artifactSha256,
            $modelSha256,
            $builderSha256,
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
    ): GeneratedPersistenceStateArtifact {
        $artifact = $this->validateStructure($generatedPhpBytes);
        $current = (new GeneratedPersistenceStateGenerator())->generate(
            $applicationRoot,
            $sourceRelativePath,
            $persistenceArtifactBytes,
        );
        if (!hash_equals($current->canonicalBytes(), $generatedPhpBytes)) {
            throw InvalidGeneratedPersistenceStateException::stale();
        }

        return $artifact;
    }

    private function validateBytes(string $bytes): void
    {
        if (
            '' === $bytes
            || strlen($bytes) > GeneratedPersistenceStateGenerator::MAX_GENERATED_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            throw InvalidGeneratedPersistenceStateException::invalid(PersistenceBuildFailureCategory::GeneratedBytes);
        }
    }

    private function validateLines(string $bytes): void
    {
        $blank = false;
        foreach (explode("\n", substr($bytes, 0, -1)) as $line) {
            if (str_contains($line, "\t") || rtrim($line, ' ') !== $line) {
                $this->invalid();
            }
            if ('' === $line) {
                if ($blank) {
                    $this->invalid();
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
        if (
            1 === preg_match('/(?:->|::)\s*\$/', $bytes)
            || 1 === preg_match('/\bnew\s+\$/i', $bytes)
            || 1 === preg_match('/\$\$/', $bytes)
        ) {
            $this->invalid();
        }
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError) {
            $this->invalid();
        }
        $classes = [];
        $methods = [];
        $forbiddenTokens = [
            T_EVAL,
            T_INCLUDE,
            T_INCLUDE_ONCE,
            T_REQUIRE,
            T_REQUIRE_ONCE,
            T_GLOBAL,
            T_TRAIT,
            T_INTERFACE,
            T_ENUM,
            T_ATTRIBUTE,
            T_ECHO,
            T_EXIT,
            T_HALT_COMPILER,
            T_PRINT,
            T_YIELD,
            T_YIELD_FROM,
        ];
        $forbiddenNames = [
            'call_user_func',
            'class_exists',
            'curl_exec',
            'exec',
            'file_get_contents',
            'fsockopen',
            'getenv',
            'interface_exists',
            'method_exists',
            'mysqli_connect',
            'passthru',
            'pfsockopen',
            'pg_connect',
            'proc_open',
            'putenv',
            'register_shutdown_function',
            'serialize',
            'shell_exec',
            'system',
            'unserialize',
        ];
        $allowedFunctions = [
            'array_key_exists',
            'base64_encode',
            'bin2hex',
            'count',
            'is_bool',
            'is_finite',
            'is_float',
            'is_int',
            'is_string',
            'pack',
            'preg_match',
            'strlen',
        ];
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }
            if (
                in_array($token[0], $forbiddenTokens, true)
                || (T_VARIABLE === $token[0] && in_array($token[1], ['$GLOBALS', '$' . '_ENV', '$' . '_SERVER'], true))
                || (T_STRING === $token[0] && in_array(strtolower($token[1]), $forbiddenNames, true))
                || (T_NAME_FULLY_QUALIFIED === $token[0] && str_starts_with(strtolower($token[1]), '\\reflection'))
            ) {
                $this->invalid();
            }
            if (
                T_STRING === $token[0]
                && '(' === $this->nextSignificantToken($tokens, $index)
                && !in_array($this->previousSignificantTokenId($tokens, $index), [T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)
                && !in_array(strtolower($token[1]), $allowedFunctions, true)
            ) {
                $this->invalid();
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
            $this->invalid();
        }
        $this->validateTopLevelDeclarations($tokens, $expectedClasses);
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     * @param list<string> $expectedClasses
     */
    private function validateTopLevelDeclarations(array $tokens, array $expectedClasses): void
    {
        $firstClass = null;
        foreach ($tokens as $index => $token) {
            if (is_array($token) && T_FINAL === $token[0]) {
                $firstClass = $index;
                break;
            }
        }
        if (null === $firstClass) {
            $this->invalid();
        }
        $depth = 0;
        $ordinal = 0;
        $state = 'between';
        for ($index = $firstClass, $count = count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];
            if ($depth > 0) {
                if ('{' === $token) {
                    ++$depth;
                } elseif ('}' === $token) {
                    --$depth;
                    if (0 === $depth) {
                        ++$ordinal;
                        $state = 'between';
                    }
                }
                continue;
            }
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            if ('between' === $state && is_array($token) && T_FINAL === $token[0]) {
                $state = 'final';
                continue;
            }
            if ('final' === $state && is_array($token) && T_CLASS === $token[0]) {
                $state = 'class';
                continue;
            }
            if ('class' === $state && is_array($token) && T_STRING === $token[0] && ($expectedClasses[$ordinal] ?? null) === $token[1]) {
                $state = 'name';
                continue;
            }
            if ('name' === $state && '{' === $token) {
                $depth = 1;
                continue;
            }
            $this->invalid();
        }
        if (0 !== $depth || 'between' !== $state || count($expectedClasses) !== $ordinal) {
            $this->invalid();
        }
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     *
     * @return array{int, string, int}|string|null
     */
    private function nextSignificantToken(array $tokens, int $after): array|string|null
    {
        for ($index = $after + 1, $count = count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
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

    private function phpName(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 512 || 1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $value)) {
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
        throw InvalidGeneratedPersistenceStateException::invalid(PersistenceBuildFailureCategory::GeneratedStructure);
    }
}
