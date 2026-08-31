<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Admission\AdmittedExtensionGraphInput;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact;
use Kongono\ExtensionComposition\Exception\InvalidGeneratedExtensionWiringException;
use Kongono\ExtensionComposition\Identity\GrantPlanIdentity;
use Kongono\ExtensionComposition\Internal\CanonicalJson;
use Kongono\ExtensionComposition\Internal\GeneratedExtensionWiringImplementation;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use ParseError;

/** Validates generated PHP structurally or against exact current inputs without loading it. */
#[Api(ApiStatus::Experimental)]
final class ExtensionWiringValidator
{
    public function validateStructure(string $bytes): GeneratedExtensionWiringArtifact
    {
        if ('' === $bytes || strlen($bytes) > ExtensionWiringGenerator::MAX_GENERATED_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            $this->invalid('generated_bytes');
        }
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([A-Za-z0-9_\\\\]+);\n/D', $bytes, $matches)) {
            $this->invalid('generated_header');
        }
        try {
            $metadata = json_decode($matches[1], true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            $this->invalid('metadata_json', $failure);
        }
        if (!is_array($metadata) || array_is_list($metadata)) {
            $this->invalid('metadata_shape');
        }
        $keys = ['schema', 'version', 'declaration_set_identity', 'admission_identity', 'grant_plan_identity', 'generator_implementation_sha256', 'generated_namespace', 'application_class', 'operation_class', 'implementation_count', 'decision_count', 'application_provider_count', 'operation_provider_count'];
        if (array_keys($metadata) !== $keys || 'kongono.generated-extension-wiring' !== $metadata['schema'] || 1 !== $metadata['version']) {
            $this->invalid('metadata_shape');
        }
        if (json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !== $matches[1]) {
            $this->invalid('metadata_json');
        }
        $declarationIdentity = new DeclarationSetIdentity($this->digest($metadata['declaration_set_identity']));
        $admissionIdentity = new AdmissionIdentity($this->digest($metadata['admission_identity']));
        $planIdentity = new GrantPlanIdentity($this->digest($metadata['grant_plan_identity']));
        $implementationIdentity = $this->digest($metadata['generator_implementation_sha256']);
        if (!hash_equals(GeneratedExtensionWiringImplementation::sha256(), $implementationIdentity)) {
            $this->invalid('generator_identity');
        }
        $namespace = $this->text($metadata['generated_namespace']);
        $expectedPrefix = 'KongonoGenerated\\Extension\\V1\\A';
        $inputIdentity = hash('sha256', CanonicalJson::encode([
            'schema' => 'kongono.generated-extension-wiring-input',
            'version' => 1,
            'declaration_set_identity' => $declarationIdentity->value(),
            'admission_identity' => $admissionIdentity->value(),
            'grant_plan_identity' => $planIdentity->value(),
            'generator_implementation_sha256' => $implementationIdentity,
        ]));
        if ($matches[2] !== $namespace || $namespace !== $expectedPrefix . $inputIdentity) {
            $this->invalid('metadata_identity');
        }
        $applicationClass = $this->text($metadata['application_class']);
        $operationClass = $this->text($metadata['operation_class']);
        if ($applicationClass !== $namespace . '\\ExtensionApplicationAssembly' || $operationClass !== $namespace . '\\ExtensionOperationAssembly') {
            $this->invalid('metadata_identity');
        }
        $counts = [];
        $limits = ['implementation_count' => 64, 'decision_count' => 4096, 'application_provider_count' => 4096, 'operation_provider_count' => 4096];
        foreach ($limits as $key => $limit) {
            $value = $metadata[$key];
            if (!is_int($value) || $value < 0 || $value > $limit) {
                $this->invalid('metadata_count');
            }
            $counts[$key] = $value;
        }
        if ($counts['application_provider_count'] + $counts['operation_provider_count'] > $counts['decision_count']) {
            $this->invalid('metadata_count');
        }
        $this->validateLines($bytes);
        $this->validateTokens($bytes);

        return new GeneratedExtensionWiringArtifact($bytes, hash('sha256', $bytes), $declarationIdentity, $admissionIdentity, $planIdentity, $implementationIdentity, $namespace, $applicationClass, $operationClass, $counts['implementation_count'], $counts['decision_count'], $counts['application_provider_count'], $counts['operation_provider_count']);
    }

    /** @param array<array-key, mixed> $declarations */
    public function validateCurrent(array $declarations, AdmittedExtensionGraphInput $admission, ExtensionGrantPlan $plan, string $bytes): GeneratedExtensionWiringArtifact
    {
        $artifact = $this->validateStructure($bytes);
        try {
            $current = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        } catch (\Throwable $failure) {
            $this->invalid('current_input_invalid', $failure);
        }
        if (!hash_equals($current->canonicalBytes(), $bytes)) {
            $this->invalid('current_state_mismatch');
        }
        return $artifact;
    }

    private function validateLines(string $bytes): void
    {
        $blank = false;
        foreach (explode("\n", substr($bytes, 0, -1)) as $line) {
            if (str_contains($line, "\t") || rtrim($line, ' ') !== $line) {
                $this->invalid('canonical_whitespace');
            }
            if ('' === $line) {
                if ($blank) {
                    $this->invalid('canonical_whitespace');
                } $blank = true;
            } else {
                $blank = false;
            }
        }
    }

    private function validateTokens(string $bytes): void
    {
        foreach (['Reflection', 'class_exists', 'interface_exists', 'trait_exists', 'getenv(', 'putenv(', '$GLOBALS', '$_ENV', '$_SERVER', 'container', 'resolver', 'registry', 'service locator', 'call_user_func', 'file_put_contents', 'file_get_contents', 'fopen(', 'unlink(', 'rename(', 'copy(', 'mkdir(', 'rmdir(', 'exec(', 'system(', 'shell_exec(', 'proc_open(', 'curl_', 'PDO'] as $forbidden) {
            if (false !== stripos($bytes, $forbidden)) {
                $this->invalid('forbidden_construct');
            }
        }
        if (1 === preg_match('/\bnew\s+\$/i', $bytes)) {
            $this->invalid('forbidden_construct');
        }
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError $failure) {
            $this->invalid('php_syntax', $failure);
        }
        $classes = [];
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }
            if (in_array($token[0], [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_TRAIT, T_INTERFACE, T_ENUM], true)) {
                $this->invalid('forbidden_construct');
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
        if (['ExtensionApplicationAssembly', 'ExtensionOperationAssembly'] !== $classes) {
            $this->invalid('generated_classes');
        }
        foreach ([
            'final class ExtensionApplicationAssembly implements GeneratedExtensionApplicationBoundary',
            'final class ExtensionOperationAssembly implements GeneratedExtensionOperationBoundary',
            'public static function start(',
            'public static function startHosted(',
            'public function applicationExtensions()',
            'public function beginOperation(',
            'public function beginHostedOperation(',
            'public function operationExtensions()',
        ] as $required) {
            if (!str_contains($bytes, $required)) {
                $this->invalid('generated_classes');
            }
        }
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            $this->invalid('metadata_identity');
        } return $value;
    }
    private function text(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 512) {
            $this->invalid('metadata_identity');
        } return $value;
    }
    private function invalid(string $kind, ?\Throwable $previous = null): never
    {
        throw InvalidGeneratedExtensionWiringException::invalid($kind, $previous);
    }
}
