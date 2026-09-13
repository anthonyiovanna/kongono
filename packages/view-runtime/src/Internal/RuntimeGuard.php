<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Internal;

use Closure;
use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueKind;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewRuntime\Artifact\LoadedCompiledViewArtifact;
use Kongono\ViewRuntime\Exception\InvalidRuntimeArtifactException;
use ParseError;

/** Validates canonical compiled evidence and creates the closed loaded handle. */
#[Api(ApiStatus::Internal)]
final class RuntimeGuard
{
    private const array ELEMENTS = ['article', 'aside', 'blockquote', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'small', 'span', 'strong', 'ul'];
    private const array ATTRIBUTES = ['aria-describedby', 'aria-hidden', 'aria-label', 'aria-labelledby', 'class', 'data-testid', 'dir', 'id', 'lang', 'role', 'title'];

    /**
     * @return array{source_sha256: string, model_sha256: string, generated_sha256: string, generated_class: string, view_count: int, view_schemas: array<string, string>}
     */
    public static function metadata(string $bytes): array
    {
        if ('' === $bytes || strlen($bytes) > StableArtifactReader::MAX_FILE_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n") || 1 === preg_match('/[\x01-\x08\x0B-\x1F\x7F]/', $bytes)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_framing');
        }
        try {
            $decoded = json_decode(substr($bytes, 0, -1), true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException $failure) {
            throw InvalidRuntimeArtifactException::invalid('metadata_json', previous: $failure);
        }
        if (!is_array($decoded) || array_is_list($decoded) || self::containsUnsupported($decoded)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
        $decoded = self::object($decoded);
        try {
            $canonical = json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        } catch (JsonException $failure) {
            throw InvalidRuntimeArtifactException::invalid('metadata_json', previous: $failure);
        }
        if (!hash_equals($canonical, $bytes)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_framing');
        }
        self::keys($decoded, ['schema', 'artifact_version', 'contract_version', 'builder', 'source_sha256', 'model_sha256', 'generated_php', 'views']);
        if ('kongono.compiled-view-artifact' !== $decoded['schema'] || 1 !== $decoded['artifact_version'] || 1 !== $decoded['contract_version']) {
            throw InvalidRuntimeArtifactException::invalid('schema_or_version');
        }
        $builder = self::object($decoded['builder']);
        self::keys($builder, ['package', 'version']);
        if ('kongono/view-build' !== $builder['package'] || 1 !== $builder['version']) {
            throw InvalidRuntimeArtifactException::invalid('builder_contract');
        }
        $sourceSha256 = self::digest($decoded['source_sha256']);
        $modelSha256 = self::digest($decoded['model_sha256']);
        $generated = self::object($decoded['generated_php']);
        self::keys($generated, ['class', 'sha256']);
        $generatedClass = $generated['class'] ?? null;
        if (!is_string($generatedClass) || $generatedClass !== 'Kongono\\Generated\\View\\CompiledViewSet_' . $modelSha256) {
            throw InvalidRuntimeArtifactException::invalid('generated_class_relation');
        }
        $generatedSha256 = self::digest($generated['sha256']);
        $views = self::list($decoded['views']);
        if ([] === $views || count($views) > 128) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
        $viewSchemas = [];
        $previousIdentity = null;
        foreach ($views as $viewValue) {
            $view = self::object($viewValue);
            self::keys($view, ['id', 'context', 'body', 'operation_count', 'source_map']);
            $identity = self::identity($view['id']);
            if (null !== $previousIdentity && strcmp($previousIdentity, $identity) >= 0) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            $previousIdentity = $identity;
            $context = self::schema($view['context'], 1);
            if ('record' !== ($context['kind'] ?? null)) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            $operationCount = self::nodes($view['body'], 1);
            if (!is_int($view['operation_count']) || $view['operation_count'] !== $operationCount) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            self::sourceMap($view['source_map'], $identity, $operationCount);
            $viewSchemas[$identity] = self::canonical($context);
        }
        $modelBytes = self::canonical(['views' => $views]);
        if (strlen($modelBytes) > StableArtifactReader::MAX_FILE_BYTES || !hash_equals(hash('sha256', $modelBytes), $modelSha256)) {
            throw InvalidRuntimeArtifactException::invalid('model_digest_relation');
        }

        return [
            'source_sha256' => $sourceSha256,
            'model_sha256' => $modelSha256,
            'generated_sha256' => $generatedSha256,
            'generated_class' => $generatedClass,
            'view_count' => count($views),
            'view_schemas' => $viewSchemas,
        ];
    }

    public static function generated(string $bytes, string $class, string $sha256): void
    {
        if ('' === $bytes || strlen($bytes) > StableArtifactReader::MAX_FILE_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw InvalidRuntimeArtifactException::invalid('generated_framing');
        }
        if (!hash_equals(hash('sha256', $bytes), $sha256)) {
            throw InvalidRuntimeArtifactException::invalid('generated_digest_relation');
        }
        try {
            $tokens = token_get_all($bytes, TOKEN_PARSE);
        } catch (ParseError $failure) {
            throw InvalidRuntimeArtifactException::invalid('generated_syntax', previous: $failure);
        }
        $forbiddenTokens = [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_GOTO, T_HALT_COMPILER, T_OPEN_TAG_WITH_ECHO, T_ECHO, T_PRINT, T_EXIT, T_FN];
        $forbiddenNames = ['assert', 'call_user_func', 'call_user_func_array', 'chdir', 'curl_exec', 'exec', 'file', 'file_get_contents', 'file_put_contents', 'fopen', 'fsockopen', 'getenv', 'glob', 'header', 'include', 'include_once', 'ob_start', 'passthru', 'proc_open', 'putenv', 'readfile', 'require', 'require_once', 'scandir', 'session_start', 'shell_exec', 'socket_create', 'system'];
        $classes = 0;
        $functions = 0;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                if ('`' === $token) {
                    throw InvalidRuntimeArtifactException::invalid('generated_syntax');
                }
                continue;
            }
            if (in_array($token[0], $forbiddenTokens, true) || T_STRING === $token[0] && in_array(strtolower($token[1]), $forbiddenNames, true)) {
                throw InvalidRuntimeArtifactException::invalid('generated_syntax');
            }
            $classes += T_CLASS === $token[0] ? 1 : 0;
            $functions += T_FUNCTION === $token[0] ? 1 : 0;
        }
        $separator = strrpos($class, '\\');
        if (false === $separator) {
            throw InvalidRuntimeArtifactException::invalid('generated_class_relation');
        }
        $shortClass = substr($class, $separator + 1);
        if (1 !== $classes || 3 !== $functions || !str_contains($bytes, 'final class ' . $shortClass) || !str_ends_with($bytes, "return '" . str_replace('\\', '\\\\', $class) . "';\n")) {
            throw InvalidRuntimeArtifactException::invalid('generated_class_relation');
        }
    }

    public static function schemaBytes(ViewContextSchema $schema): string
    {
        return self::canonical(self::schemaData($schema->rootSchema()));
    }

    /**
     * @param array<string, string> $viewSchemas
     * @param Closure(\Kongono\ViewContract\Identity\LogicalViewIdentity, \Kongono\ViewContract\Value\ViewContext, \Kongono\ViewBuild\Generated\RenderSink): void $emit
     */
    public static function loaded(CompiledViewIdentity $identity, string $sourceSha256, string $modelSha256, string $generatedSha256, string $generatedClass, int $viewCount, array $viewSchemas, Closure $emit): LoadedCompiledViewArtifact
    {
        $factory = Closure::bind(
            static fn(CompiledViewIdentity $compiledIdentity, string $source, string $model, string $generated, string $class, int $count, array $schemas, Closure $program): LoadedCompiledViewArtifact => new LoadedCompiledViewArtifact($compiledIdentity, $source, $model, $generated, $class, $count, $schemas, $program),
            null,
            LoadedCompiledViewArtifact::class,
        );
        return $factory($identity, $sourceSha256, $modelSha256, $generatedSha256, $generatedClass, $viewCount, $viewSchemas, $emit);
    }

    /** @return array<string, mixed> */
    private static function schema(mixed $value, int $depth): array
    {
        if ($depth > 16) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
        $schema = self::object($value);
        $kind = $schema['kind'] ?? null;
        if (!is_string($kind)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
        if ('string' === $kind) {
            self::keys($schema, ['kind', 'max_bytes']);
            if (!is_int($schema['max_bytes']) || $schema['max_bytes'] < 0 || $schema['max_bytes'] > 65_536) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
        } elseif (in_array($kind, ['integer', 'boolean', 'null'], true)) {
            self::keys($schema, ['kind']);
        } elseif ('list' === $kind) {
            self::keys($schema, ['kind', 'max_items', 'item']);
            if (!is_int($schema['max_items']) || $schema['max_items'] < 0 || $schema['max_items'] > 256) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            $schema['item'] = self::schema($schema['item'], $depth + 1);
        } elseif ('record' === $kind) {
            self::keys($schema, ['kind', 'fields']);
            $fields = self::list($schema['fields']);
            if (count($fields) > 128) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            $validated = [];
            $previous = null;
            foreach ($fields as $fieldValue) {
                $field = self::object($fieldValue);
                self::keys($field, ['name', 'required', 'schema']);
                $name = self::fieldName($field['name']);
                if (null !== $previous && strcmp($previous, $name) >= 0 || !is_bool($field['required'])) {
                    throw InvalidRuntimeArtifactException::invalid('metadata_shape');
                }
                $previous = $name;
                $field['schema'] = self::schema($field['schema'], $depth + 1);
                $validated[] = $field;
            }
            $schema['fields'] = $validated;
        } else {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }

        return $schema;
    }

    private static function nodes(mixed $value, int $depth): int
    {
        if ($depth > 64) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
        $nodes = self::list($value);
        $operations = 0;
        foreach ($nodes as $nodeValue) {
            $node = self::object($nodeValue);
            $kind = $node['kind'] ?? null;
            if ('text' === $kind) {
                self::keys($node, ['kind', 'text']);
                self::text($node['text']);
                ++$operations;
            } elseif ('value' === $kind) {
                self::keys($node, ['kind', 'path']);
                self::reference($node['path']);
                ++$operations;
            } elseif ('element' === $kind) {
                self::keys($node, ['kind', 'name', 'attributes', 'children']);
                if (!is_string($node['name']) || !in_array($node['name'], self::ELEMENTS, true)) {
                    throw InvalidRuntimeArtifactException::invalid('metadata_shape');
                }
                $operations += 3;
                $attributes = self::list($node['attributes']);
                $previous = null;
                foreach ($attributes as $attributeValue) {
                    $attribute = self::object($attributeValue);
                    self::keys($attribute, ['name', 'value']);
                    $name = $attribute['name'] ?? null;
                    if (!is_string($name) || !in_array($name, self::ATTRIBUTES, true) || null !== $previous && strcmp($previous, $name) >= 0) {
                        throw InvalidRuntimeArtifactException::invalid('metadata_shape');
                    }
                    $previous = $name;
                    $attributeValueObject = self::object($attribute['value']);
                    if ('literal' === ($attributeValueObject['kind'] ?? null)) {
                        self::keys($attributeValueObject, ['kind', 'text']);
                        self::text($attributeValueObject['text']);
                        ++$operations;
                    } elseif ('value' === ($attributeValueObject['kind'] ?? null)) {
                        self::keys($attributeValueObject, ['kind', 'path']);
                        self::reference($attributeValueObject['path']);
                        $operations += 3;
                    } else {
                        throw InvalidRuntimeArtifactException::invalid('metadata_shape');
                    }
                }
                $operations += self::nodes($node['children'], $depth + 1);
            } elseif ('each' === $kind) {
                self::keys($node, ['kind', 'path', 'as', 'max_items', 'body']);
                self::reference($node['path']);
                self::fieldName($node['as']);
                if (!is_int($node['max_items']) || $node['max_items'] < 0 || $node['max_items'] > 256) {
                    throw InvalidRuntimeArtifactException::invalid('metadata_shape');
                }
                $operations += 1 + self::nodes($node['body'], $depth + 1);
            } else {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            if ($operations > 1_000_000) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
        }

        return $operations;
    }

    private static function sourceMap(mixed $value, string $identity, int $operationCount): void
    {
        $entries = self::list($value);
        if (count($entries) !== $operationCount) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
        foreach ($entries as $position => $entryValue) {
            $entry = self::object($entryValue);
            self::keys($entry, ['view_id', 'operation_index', 'source_pointer', 'operation_kind', 'output_context']);
            if ($entry['view_id'] !== $identity || $entry['operation_index'] !== $position || !is_string($entry['source_pointer']) || 1 !== preg_match('#\A/views/[0-9]+/body/[0-9]+(?:(?:/children|/body|/attributes)/[0-9]+)*\z#D', $entry['source_pointer'])) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            if (!is_string($entry['operation_kind']) || !in_array($entry['operation_kind'], ['literal', 'html_text', 'html_quoted_attribute', 'loop'], true)) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
            if (!in_array($entry['output_context'], [null, 'html_text', 'html_quoted_attribute'], true)) {
                throw InvalidRuntimeArtifactException::invalid('metadata_shape');
            }
        }
    }

    /** @return array<string, mixed> */
    private static function schemaData(PresentationValueSchema $schema): array
    {
        return match ($schema->kind()) {
            PresentationValueKind::String => ['kind' => 'string', 'max_bytes' => $schema->maxBytes()],
            PresentationValueKind::Integer => ['kind' => 'integer'],
            PresentationValueKind::Boolean => ['kind' => 'boolean'],
            PresentationValueKind::Null => ['kind' => 'null'],
            PresentationValueKind::List => ['kind' => 'list', 'max_items' => $schema->maxItems(), 'item' => self::schemaData($schema->itemSchema() ?? throw new \LogicException('List schema item is absent.'))],
            PresentationValueKind::Record => ['kind' => 'record', 'fields' => array_map(static fn(PresentationFieldSchema $field): array => ['name' => $field->name(), 'required' => $field->required(), 'schema' => self::schemaData($field->schema())], $schema->fields())],
        };
    }

    /** @param array<array-key, mixed> $value */
    private static function canonical(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        } catch (JsonException $failure) {
            throw InvalidRuntimeArtifactException::invalid('metadata_json', previous: $failure);
        }
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $expected
     */
    private static function keys(array $value, array $expected): void
    {
        if (array_keys($value) !== $expected) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private static function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }

        return $value;
    }

    private static function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidRuntimeArtifactException::invalid('digest_grammar');
        }

        return $value;
    }

    private static function identity(mixed $value): string
    {
        if (!is_string($value) || strlen($value) > 128 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }

        return $value;
    }

    private static function fieldName(mixed $value): string
    {
        if (!is_string($value) || strlen($value) > 64 || 1 !== preg_match('/\A[a-z][a-z0-9_]*\z/D', $value)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }

        return $value;
    }

    private static function reference(mixed $value): void
    {
        if (!is_string($value) || strlen($value) < 2 || strlen($value) > 256 || 1 !== preg_match('/\A(?:\$\.[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*|@[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*)\z/D', $value)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
    }

    private static function text(mixed $value): void
    {
        if (!is_string($value) || strlen($value) > 65_536 || str_starts_with($value, "\xEF\xBB\xBF") || str_contains($value, "\r") || str_contains($value, "\0") || 1 !== preg_match('//u', $value) || 1 === preg_match('/[\x01-\x08\x0B-\x1F\x7F]|[\x{0080}-\x{009F}]/u', $value)) {
            throw InvalidRuntimeArtifactException::invalid('metadata_shape');
        }
    }

    private static function containsUnsupported(mixed $value): bool
    {
        if (is_float($value) || is_object($value) || is_resource($value)) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $entry) {
                if (self::containsUnsupported($entry)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function __construct() {}
}
