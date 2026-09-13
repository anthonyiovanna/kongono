<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidCompiledViewArtifactException;
use Kongono\ViewBuild\Exception\StaleCompiledViewArtifactException;
use Kongono\ViewBuild\Generated\GeneratedPhpEmitter;
use Kongono\ViewBuild\Internal\BuildGuard;
use Kongono\ViewBuild\Internal\CanonicalJson;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use Kongono\ViewBuild\Source\ViewSourceSet;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use ParseError;
use Throwable;

/** Validates compiled evidence structurally and against one explicit current source. */
#[Api(ApiStatus::Experimental)]
final class CompiledViewArtifactValidator
{
    public function validateStructure(string $metadataBytes, string $generatedPhpBytes): CompiledViewArtifact
    {
        try {
            $metadata = CanonicalJson::decode($metadataBytes);
            if (!hash_equals(CanonicalJson::encode($metadata), $metadataBytes)) {
                throw InvalidCompiledViewArtifactException::invalid('metadata_framing');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            if ($failure instanceof InvalidCompiledViewArtifactException) {
                throw $failure;
            }
            throw InvalidCompiledViewArtifactException::invalid($failure instanceof JsonException ? 'metadata_json' : 'metadata_framing', $failure);
        }

        $this->keys($metadata, ['schema', 'artifact_version', 'contract_version', 'builder', 'source_sha256', 'model_sha256', 'generated_php', 'views'], 'metadata_shape');
        if ('kongono.compiled-view-artifact' !== ($metadata['schema'] ?? null) || 1 !== ($metadata['artifact_version'] ?? null) || 1 !== ($metadata['contract_version'] ?? null)) {
            throw InvalidCompiledViewArtifactException::invalid('schema_or_version');
        }
        $builder = $this->object($metadata['builder'] ?? null);
        $this->keys($builder, ['package', 'version'], 'builder_contract');
        if ('kongono/view-build' !== ($builder['package'] ?? null) || 1 !== ($builder['version'] ?? null)) {
            throw InvalidCompiledViewArtifactException::invalid('builder_contract');
        }
        $sourceSha256 = $this->digest($metadata['source_sha256'] ?? null);
        $modelSha256 = $this->digest($metadata['model_sha256'] ?? null);
        $generated = $this->object($metadata['generated_php'] ?? null);
        $this->keys($generated, ['class', 'sha256'], 'metadata_shape');
        $generatedClass = $generated['class'] ?? null;
        if (!is_string($generatedClass) || 1 !== preg_match('/\AKongono\\\\Generated\\\\View\\\\CompiledViewSet_[a-f0-9]{64}\z/D', $generatedClass)) {
            throw InvalidCompiledViewArtifactException::invalid('generated_class_relation');
        }
        $generatedSha256 = $this->digest($generated['sha256'] ?? null);

        try {
            $model = BuildGuard::modelFromData($metadata['views'] ?? null);
            $modelBytes = CanonicalJson::encode(['views' => BuildGuard::modelData($model)]);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidCompiledViewArtifactException::invalid('model_invalid', $failure);
        }
        if (strlen($modelBytes) > BuildGuard::MAX_MODEL_BYTES || !hash_equals(hash('sha256', $modelBytes), $modelSha256)) {
            throw InvalidCompiledViewArtifactException::invalid('model_digest_relation');
        }
        $expectedClass = 'Kongono\\Generated\\View\\CompiledViewSet_' . $modelSha256;
        if (!hash_equals($expectedClass, $generatedClass)) {
            throw InvalidCompiledViewArtifactException::invalid('generated_class_relation');
        }
        $this->generatedFraming($generatedPhpBytes);
        $this->generatedSyntax($generatedPhpBytes);
        if (!hash_equals(hash('sha256', $generatedPhpBytes), $generatedSha256)) {
            throw InvalidCompiledViewArtifactException::invalid('generated_digest_relation');
        }
        $sourceIdentity = new ViewSourceIdentity($sourceSha256);
        $expectedPhp = (new GeneratedPhpEmitter())->emit($model, $sourceIdentity, $modelSha256);
        if (!hash_equals($expectedPhp, $generatedPhpBytes)) {
            throw InvalidCompiledViewArtifactException::invalid('generated_regeneration');
        }

        return new CompiledViewArtifact(
            $metadataBytes,
            $generatedPhpBytes,
            new CompiledViewIdentity(hash('sha256', $metadataBytes)),
            $sourceIdentity,
            $modelSha256,
            $generatedSha256,
            $generatedClass,
            $model->viewCount(),
        );
    }

    public function validateCurrent(ViewSourceSet $currentSource, string $metadataBytes, string $generatedPhpBytes): CompiledViewArtifact
    {
        $artifact = $this->validateStructure($metadataBytes, $generatedPhpBytes);
        $current = (new CompiledViewArtifactBuilder())->build($currentSource);
        if (!$current->equals($artifact)
            || !hash_equals($current->metadataBytes(), $metadataBytes)
            || !hash_equals($current->generatedPhpBytes(), $generatedPhpBytes)) {
            throw StaleCompiledViewArtifactException::mismatch();
        }

        return $artifact;
    }

    /** @param array<string, mixed> $object
     * @param list<string> $keys
     */
    private function keys(array $object, array $keys, string $violation): void
    {
        if (array_keys($object) !== $keys) {
            throw InvalidCompiledViewArtifactException::invalid($violation);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidCompiledViewArtifactException::invalid('metadata_shape');
        }

        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                throw InvalidCompiledViewArtifactException::invalid('metadata_shape');
            }
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidCompiledViewArtifactException::invalid('digest_grammar');
        }

        return $value;
    }

    private function generatedFraming(string $bytes): void
    {
        if ('' === $bytes || strlen($bytes) > BuildGuard::MAX_GENERATED_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw InvalidCompiledViewArtifactException::invalid('generated_framing');
        }
    }

    private function generatedSyntax(string $bytes): void
    {
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError $failure) {
            throw InvalidCompiledViewArtifactException::invalid('generated_syntax', $failure);
        }
        $forbiddenTokens = [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_GOTO, T_HALT_COMPILER, T_OPEN_TAG_WITH_ECHO, T_ECHO, T_PRINT, T_EXIT, T_FN];
        $forbiddenNames = ['assert', 'call_user_func', 'call_user_func_array', 'chdir', 'curl_exec', 'exec', 'file', 'file_get_contents', 'file_put_contents', 'fopen', 'fsockopen', 'getenv', 'glob', 'header', 'include', 'include_once', 'ob_start', 'passthru', 'proc_open', 'putenv', 'readfile', 'require', 'require_once', 'scandir', 'session_start', 'shell_exec', 'socket_create', 'system'];
        $classCount = 0;
        $functionCount = 0;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                if ('`' === $token) {
                    throw InvalidCompiledViewArtifactException::invalid('generated_forbidden_surface');
                }
                continue;
            }
            if (in_array($token[0], $forbiddenTokens, true)) {
                throw InvalidCompiledViewArtifactException::invalid('generated_forbidden_surface');
            }
            if (T_CLASS === $token[0]) {
                ++$classCount;
            }
            if (T_FUNCTION === $token[0]) {
                ++$functionCount;
            }
            if (T_STRING === $token[0] && in_array(strtolower($token[1]), $forbiddenNames, true)) {
                throw InvalidCompiledViewArtifactException::invalid('generated_forbidden_surface');
            }
        }
        if (1 !== $classCount || 3 !== $functionCount) {
            throw InvalidCompiledViewArtifactException::invalid('generated_forbidden_surface');
        }
    }
}
