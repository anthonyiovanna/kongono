<?php

declare(strict_types=1);

namespace Kongono\Composition\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidGeneratedWiringException;
use Kongono\Composition\Internal\GeneratedWiringImplementation;
use ParseError;

/** Validates detached generated PHP without loading or executing it. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWiringValidator
{
    public function validateStructure(string $generatedPhpBytes): GeneratedWiringArtifact
    {
        $this->validateBytes($generatedPhpBytes);
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([A-Za-z0-9_\\\\]+);\n/D', $generatedPhpBytes, $matches)) {
            $this->invalid('generated_header');
        }
        try {
            $metadata = json_decode($matches[1], true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid('metadata_json');
        }
        if (!is_array($metadata) || array_is_list($metadata) || json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !== $matches[1]) {
            $this->invalid('metadata_canonical');
        }
        $expectedKeys = ['schema', 'version', 'composition_artifact_sha256', 'model_sha256', 'generator_implementation_sha256', 'generated_namespace', 'application_class', 'operation_class'];
        if (array_keys($metadata) !== $expectedKeys || 'kongono.generated-wiring' !== $metadata['schema'] || 1 !== $metadata['version']) {
            $this->invalid('metadata_shape');
        }
        $compositionSha256 = $this->digest($metadata['composition_artifact_sha256']);
        $modelSha256 = $this->digest($metadata['model_sha256']);
        $implementationSha256 = $this->digest($metadata['generator_implementation_sha256']);
        if (!hash_equals(GeneratedWiringImplementation::sha256(), $implementationSha256)) {
            $this->invalid('generator_implementation_identity');
        }
        $namespace = $this->text($metadata['generated_namespace']);
        $applicationClass = $this->text($metadata['application_class']);
        $operationClass = $this->text($metadata['operation_class']);
        $expectedNamespace = 'KongonoGenerated\\Composition\\V1\\A' . $compositionSha256;
        if ($matches[2] !== $expectedNamespace || $namespace !== $expectedNamespace || $applicationClass !== $namespace . '\\ApplicationAssembly' || $operationClass !== $namespace . '\\OperationAssembly') {
            $this->invalid('generated_identity');
        }
        $this->validateCanonicalLines($generatedPhpBytes);
        $this->validateTokens($generatedPhpBytes);
        foreach ([
            'final class ApplicationAssembly extends GeneratedApplicationState implements GeneratedApplicationBoundary',
            'final class OperationAssembly implements GeneratedOperationBoundary',
            'public static function start(',
            'public function beginOperation(): OperationAssembly',
            'public function shutdown(?Throwable $primaryFailure = null): void',
            'public function isRetired(): bool',
            'public function close(?Throwable $primaryFailure = null): void',
            'public function isClosed(): bool',
        ] as $required) {
            if (!str_contains($generatedPhpBytes, $required)) {
                $this->invalid('generated_grammar');
            }
        }

        return new GeneratedWiringArtifact(
            $generatedPhpBytes,
            hash('sha256', $generatedPhpBytes),
            $compositionSha256,
            $modelSha256,
            $implementationSha256,
            $namespace,
            $applicationClass,
            $operationClass,
        );
    }

    public function validateCurrent(string $applicationRoot, string $compositionArtifactBytes, string $generatedPhpBytes): GeneratedWiringArtifact
    {
        $artifact = $this->validateStructure($generatedPhpBytes);
        $current = (new GeneratedWiringGenerator())->generate($applicationRoot, $compositionArtifactBytes);
        if (!hash_equals($current->canonicalBytes(), $generatedPhpBytes)) {
            $this->invalid('current_state_mismatch');
        }

        return $artifact;
    }

    private function validateBytes(string $bytes): void
    {
        if ('' === $bytes || strlen($bytes) > GeneratedWiringGenerator::MAX_GENERATED_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            $this->invalid('generated_bytes');
        }
    }

    private function validateCanonicalLines(string $bytes): void
    {
        $blank = false;
        foreach (explode("\n", substr($bytes, 0, -1)) as $line) {
            if (str_contains($line, "\t") || rtrim($line, ' ') !== $line) {
                $this->invalid('canonical_whitespace');
            }
            if ('' === $line) {
                if ($blank) {
                    $this->invalid('canonical_blank_lines');
                }
                $blank = true;
            } else {
                $blank = false;
            }
        }
    }

    private function validateTokens(string $bytes): void
    {
        foreach (['Reflection', 'DependencyResolver', 'ApplicationGraph', 'class_exists', 'interface_exists', 'method_exists', 'call_user_func', 'eval(', '$GLOBALS', '$_SERVER', 'getenv(', 'putenv(', 'container', 'service locator', 'services:'] as $forbidden) {
            if (false !== stripos($bytes, $forbidden)) {
                $this->invalid('forbidden_construct');
            }
        }
        if (1 === preg_match('/\bnew\s+\$/i', $bytes)) {
            $this->invalid('variable_class');
        }
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError) {
            $this->invalid('php_syntax');
        }
        $classes = [];
        $forbiddenTokens = [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_TRAIT, T_INTERFACE, T_ENUM];
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }
            if (in_array($token[0], $forbiddenTokens, true)) {
                $this->invalid('forbidden_token');
            }
            if (T_CLASS === $token[0]) {
                for ($next = $index + 1, $count = count($tokens); $next < $count; ++$next) {
                    if (is_array($tokens[$next]) && T_STRING === $tokens[$next][0]) {
                        $classes[] = $tokens[$next][1];
                        break;
                    }
                }
            }
        }
        if (['OperationAssembly', 'ApplicationAssembly'] !== $classes) {
            $this->invalid('generated_classes');
        }
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid('metadata_digest');
        }
        return $value;
    }

    private function text(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 512) {
            $this->invalid('metadata_text');
        }
        return $value;
    }

    private function invalid(string $violation): never
    {
        throw new InvalidGeneratedWiringException(CompositionDiagnostic::InvalidGeneratedWiring, $violation);
    }
}
