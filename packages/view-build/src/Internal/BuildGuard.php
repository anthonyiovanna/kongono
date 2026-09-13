<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Grammar\ViewAttribute;
use Kongono\ViewBuild\Grammar\ViewAttributeValue;
use Kongono\ViewBuild\Grammar\ViewAttributeValueKind;
use Kongono\ViewBuild\Grammar\ViewNode;
use Kongono\ViewBuild\Grammar\ViewNodeKind;
use Kongono\ViewBuild\Model\CompiledViewDefinition;
use Kongono\ViewBuild\Model\CompiledViewModel;
use Kongono\ViewBuild\Model\SourceMapEntry;
use Kongono\ViewBuild\Source\SourcePointer;
use Kongono\ViewBuild\Source\ViewSource;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use Kongono\ViewBuild\Source\ViewSourceSet;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Output\OutputContext;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueKind;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Throwable;

/** Centralizes the exact closed source, model, reference, and projection rules. */
#[Api(ApiStatus::Internal)]
final class BuildGuard
{
    public const int MAX_SOURCE_BYTES = 1_048_576;
    public const int MAX_MODEL_BYTES = 2_097_152;
    public const int MAX_GENERATED_BYTES = 2_097_152;

    private const array ELEMENTS = ['article', 'aside', 'blockquote', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'small', 'span', 'strong', 'ul'];
    private const array ATTRIBUTES = ['aria-describedby', 'aria-hidden', 'aria-label', 'aria-labelledby', 'class', 'data-testid', 'dir', 'id', 'lang', 'role', 'title'];

    public static function source(string $bytes): ViewSourceSet
    {
        $length = strlen($bytes);
        if ($length < 1 || $length > self::MAX_SOURCE_BYTES) {
            throw InvalidViewSourceException::bound('source_bytes', self::MAX_SOURCE_BYTES, $length);
        }
        if (str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || 1 === preg_match('/[\x01-\x08\x0B-\x1F\x7F]|[\x{0080}-\x{009F}]/u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw InvalidViewSourceException::invalid('source_framing');
        }
        try {
            $root = StrictJsonDecoder::decodeObject(substr($bytes, 0, -1), 64);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidViewSourceException::invalid('source_json', $failure);
        }
        self::keys($root, ['schema', 'version', 'views'], false, 'source_shape');
        if ('kongono.view-source-set' !== ($root['schema'] ?? null) || 1 !== ($root['version'] ?? null)) {
            throw InvalidViewSourceException::invalid('source_shape');
        }
        $viewsData = self::list($root['views'] ?? null, 'source_shape');
        if (count($viewsData) < 1 || count($viewsData) > 128) {
            throw InvalidViewSourceException::bound('view_count', 128, count($viewsData));
        }
        $views = [];
        $seen = [];
        $counts = ['nodes' => 0, 'static' => 0];
        foreach ($viewsData as $position => $data) {
            $view = self::sourceView($data, $position, $counts);
            $identity = $view->identity()->value();
            if (isset($seen[$identity])) {
                throw InvalidViewSourceException::invalid('duplicate_view');
            }
            $seen[$identity] = true;
            $views[] = $view;
        }
        usort($views, static fn(ViewSource $left, ViewSource $right): int => strcmp($left->identity()->value(), $right->identity()->value()));

        return new ViewSourceSet(new ViewSourceIdentity(hash('sha256', $bytes)), $views);
    }

    public static function compile(ViewSourceSet $source): CompiledViewModel
    {
        if ($source->viewCount() < 1 || $source->viewCount() > 128) {
            throw InvalidViewSourceException::bound('view_count', 128, $source->viewCount());
        }
        $definitions = [];
        $seen = [];
        foreach ($source->views() as $view) {
            $identity = $view->identity()->value();
            if (isset($seen[$identity])) {
                throw InvalidViewSourceException::invalid('duplicate_view');
            }
            $seen[$identity] = true;
            $definitions[] = self::definition(
                $identity,
                self::schemaData($view->contextSchema()->rootSchema()),
                array_map(self::nodeData(...), $view->body()),
                $view->sourcePointer()->value(),
            );
        }
        usort($definitions, static fn(CompiledViewDefinition $left, CompiledViewDefinition $right): int => strcmp($left->identity()->value(), $right->identity()->value()));

        return new CompiledViewModel($definitions);
    }

    /** @return list<array<string, mixed>> */
    public static function modelData(CompiledViewModel $model): array
    {
        $views = [];
        foreach ($model->views() as $view) {
            $views[] = [
                'id' => $view->identity()->value(),
                'context' => self::schemaData($view->contextSchema()->rootSchema()),
                'body' => array_map(self::nodeData(...), $view->body()),
                'operation_count' => $view->operationCount(),
                'source_map' => self::sourceMapData($view->sourceMap()),
            ];
        }

        return $views;
    }

    public static function modelFromData(mixed $value): CompiledViewModel
    {
        $viewsData = self::list($value, 'source_shape');
        if (count($viewsData) < 1 || count($viewsData) > 128) {
            throw InvalidViewSourceException::bound('view_count', 128, count($viewsData));
        }
        $definitions = [];
        $previous = null;
        foreach ($viewsData as $data) {
            $view = self::object($data, 'source_shape');
            self::keys($view, ['id', 'context', 'body', 'operation_count', 'source_map'], true, 'source_shape');
            $identity = self::string($view['id'] ?? null, 'source_shape');
            if (null !== $previous && strcmp($previous, $identity) >= 0) {
                throw InvalidViewSourceException::invalid('duplicate_view');
            }
            $previous = $identity;
            $sourceMapData = self::list($view['source_map'] ?? null, 'source_shape');
            $first = self::object($sourceMapData[0] ?? null, 'source_shape');
            $pointer = self::string($first['source_pointer'] ?? null, 'source_shape');
            if (1 !== preg_match('#\A(/views/[0-9]+)/body(?:/|$)#D', $pointer, $match)) {
                throw InvalidViewSourceException::invalid('source_pointer_grammar');
            }
            $definition = self::definition($identity, $view['context'] ?? null, $view['body'] ?? null, $match[1]);
            if (!is_int($view['operation_count'] ?? null) || $definition->operationCount() !== $view['operation_count']) {
                throw InvalidViewSourceException::invalid('source_shape');
            }
            if (self::sourceMapData($definition->sourceMap()) !== $sourceMapData) {
                throw InvalidViewSourceException::invalid('source_shape');
            }
            $definitions[] = $definition;
        }

        return new CompiledViewModel($definitions);
    }

    /** @return array<string, mixed> */
    public static function schemaData(PresentationValueSchema $schema): array
    {
        return match ($schema->kind()) {
            PresentationValueKind::String => ['kind' => 'string', 'max_bytes' => $schema->maxBytes()],
            PresentationValueKind::Integer => ['kind' => 'integer'],
            PresentationValueKind::Boolean => ['kind' => 'boolean'],
            PresentationValueKind::Null => ['kind' => 'null'],
            PresentationValueKind::List => ['kind' => 'list', 'max_items' => $schema->maxItems(), 'item' => self::schemaData($schema->itemSchema() ?? throw InvalidViewSourceException::invalid('schema_shape'))],
            PresentationValueKind::Record => ['kind' => 'record', 'fields' => array_map(static fn(PresentationFieldSchema $field): array => ['name' => $field->name(), 'required' => $field->required(), 'schema' => self::schemaData($field->schema())], $schema->fields())],
        };
    }

    /** @return array<string, mixed> */
    public static function nodeData(ViewNode $node): array
    {
        return match ($node->kind()) {
            ViewNodeKind::Text => ['kind' => 'text', 'text' => $node->textValue()],
            ViewNodeKind::Value => ['kind' => 'value', 'path' => $node->path()],
            ViewNodeKind::Element => ['kind' => 'element', 'name' => $node->name(), 'attributes' => array_map(static fn(ViewAttribute $attribute): array => ['name' => $attribute->name(), 'value' => ViewAttributeValueKind::Literal === $attribute->value()->kind() ? ['kind' => 'literal', 'text' => $attribute->value()->text()] : ['kind' => 'value', 'path' => $attribute->value()->path()]], $node->attributes()), 'children' => array_map(self::nodeData(...), $node->children())],
            ViewNodeKind::Each => ['kind' => 'each', 'path' => $node->path(), 'as' => $node->alias(), 'max_items' => $node->maxItems(), 'body' => array_map(self::nodeData(...), $node->children())],
        };
    }

    public static function htmlText(string $text): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }

    public static function htmlAttribute(string $text): string
    {
        return str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&#039;'], $text);
    }

    /** @param array{nodes: int, static: int} $counts */
    private static function sourceView(mixed $value, int $position, array &$counts): ViewSource
    {
        $view = self::object($value, 'source_shape');
        self::keys($view, ['id', 'context', 'body'], false, 'source_shape');
        $identity = new LogicalViewIdentity(self::string($view['id'] ?? null, 'source_shape'));
        $schema = new ViewContextSchema(self::schema($view['context'] ?? null, 1));
        $pointer = '/views/' . $position;
        $body = self::nodes($view['body'] ?? null, $schema, $pointer . '/body', [], 1, 0, $counts, true);

        return new ViewSource($identity, $schema, $body, new SourcePointer($pointer));
    }

    private static function definition(string $identity, mixed $contextData, mixed $bodyData, string $viewPointer): CompiledViewDefinition
    {
        $logical = new LogicalViewIdentity($identity);
        $schema = new ViewContextSchema(self::schema($contextData, 1));
        $counts = ['nodes' => 0, 'static' => 0];
        $nodes = self::nodes($bodyData, $schema, $viewPointer . '/body', [], 1, 0, $counts, true);
        $entries = [];
        $index = 0;
        self::operations($nodes, $logical, $entries, $index);

        return new CompiledViewDefinition($logical, $schema, $nodes, $index, $entries);
    }

    private static function schema(mixed $value, int $depth): PresentationValueSchema
    {
        if ($depth > 16) {
            throw InvalidViewSourceException::bound('schema_depth', 16, depth: $depth);
        }
        $schema = self::object($value, 'schema_shape');
        $kind = self::string($schema['kind'] ?? null, 'schema_shape');
        try {
            return match ($kind) {
                'string' => self::stringSchema($schema),
                'integer' => self::scalarSchema($schema, PresentationValueSchema::integer()),
                'boolean' => self::scalarSchema($schema, PresentationValueSchema::boolean()),
                'null' => self::scalarSchema($schema, PresentationValueSchema::nullValue()),
                'list' => self::listSchema($schema, $depth),
                'record' => self::recordSchema($schema, $depth),
                default => throw InvalidViewSourceException::unsupported('schema_kind'),
            };
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidViewSourceException) {
                throw $failure;
            }
            throw InvalidViewSourceException::invalid('schema_shape', $failure);
        }
    }

    /** @param array<string, mixed> $schema */
    private static function stringSchema(array $schema): PresentationValueSchema
    {
        self::keys($schema, ['kind', 'max_bytes'], false, 'schema_shape');
        $maximum = self::integerValue($schema['max_bytes'] ?? null, 'schema_shape');
        if ($maximum < 0 || $maximum > 65_536) {
            throw InvalidViewSourceException::bound('string_bytes', 65_536, $maximum);
        }

        return PresentationValueSchema::string($maximum);
    }

    /** @param array<string, mixed> $schema */
    private static function scalarSchema(array $schema, PresentationValueSchema $value): PresentationValueSchema
    {
        self::keys($schema, ['kind'], false, 'schema_shape');

        return $value;
    }

    /** @param array<string, mixed> $schema */
    private static function listSchema(array $schema, int $depth): PresentationValueSchema
    {
        self::keys($schema, ['kind', 'max_items', 'item'], false, 'schema_shape');
        $maximum = self::integerValue($schema['max_items'] ?? null, 'schema_shape');
        if ($maximum < 0 || $maximum > 256) {
            throw InvalidViewSourceException::bound('list_items', 256, $maximum);
        }

        return PresentationValueSchema::list(self::schema($schema['item'] ?? null, $depth + 1), $maximum);
    }

    /** @param array<string, mixed> $schema */
    private static function recordSchema(array $schema, int $depth): PresentationValueSchema
    {
        self::keys($schema, ['kind', 'fields'], false, 'schema_shape');
        $fieldsData = self::list($schema['fields'] ?? null, 'schema_shape');
        if (count($fieldsData) > 128) {
            throw InvalidViewSourceException::bound('field_count', 128, count($fieldsData));
        }
        $fields = [];
        $seen = [];
        foreach ($fieldsData as $data) {
            $field = self::object($data, 'field_shape');
            self::keys($field, ['name', 'required', 'schema'], false, 'field_shape');
            $name = self::fieldName($field['name'] ?? null);
            if (isset($seen[$name])) {
                throw InvalidViewSourceException::invalid('duplicate_field');
            }
            $seen[$name] = true;
            if (!is_bool($field['required'] ?? null)) {
                throw InvalidViewSourceException::invalid('field_shape');
            }
            $fields[] = new PresentationFieldSchema($name, self::schema($field['schema'] ?? null, $depth + 1), $field['required']);
        }

        return PresentationValueSchema::record($fields);
    }

    /** @param array<string, PresentationValueSchema> $aliases
     * @param array{nodes: int, static: int} $counts
     * @return list<ViewNode>
     */
    private static function nodes(mixed $value, ViewContextSchema $context, string $pointer, array $aliases, int $depth, int $loopDepth, array &$counts, bool $rootBody = false): array
    {
        $data = self::list($value, 'node_shape');
        if (($rootBody && count($data) < 1) || count($data) > 2_048) {
            throw InvalidViewSourceException::bound('body_count', 2_048, count($data));
        }
        if ($depth > 32) {
            throw InvalidViewSourceException::bound('node_depth', 32, depth: $depth);
        }
        $nodes = [];
        foreach ($data as $position => $entry) {
            ++$counts['nodes'];
            if ($counts['nodes'] > 4_096) {
                throw InvalidViewSourceException::bound('node_count', 4_096, $counts['nodes']);
            }
            $nodePointer = $pointer . '/' . $position;
            $node = self::object($entry, 'node_shape');
            $kind = self::string($node['kind'] ?? null, 'node_shape');
            $nodes[] = match ($kind) {
                'text' => self::textNode($node, $nodePointer, $counts),
                'value' => self::valueNode($node, $nodePointer, $context, $aliases),
                'element' => self::elementNode($node, $nodePointer, $context, $aliases, $depth, $loopDepth, $counts),
                'each' => self::eachNode($node, $nodePointer, $context, $aliases, $depth, $loopDepth, $counts),
                default => throw InvalidViewSourceException::unsupported('node_kind'),
            };
        }

        return $nodes;
    }

    /** @param array<string, mixed> $node
     * @param array{nodes: int, static: int} $counts
     */
    private static function textNode(array $node, string $pointer, array &$counts): ViewNode
    {
        self::keys($node, ['kind', 'text'], false, 'node_shape');
        $text = self::text($node['text'] ?? null);
        self::staticBytes($counts, strlen($text));

        return ViewNode::text($text, new SourcePointer($pointer));
    }

    /** @param array<string, mixed> $node
     * @param array<string, PresentationValueSchema> $aliases
     */
    private static function valueNode(array $node, string $pointer, ViewContextSchema $context, array $aliases): ViewNode
    {
        self::keys($node, ['kind', 'path'], false, 'node_shape');
        $path = self::reference($node['path'] ?? null);
        if (PresentationValueKind::String !== self::resolve($path, $context, $aliases)->kind()) {
            throw InvalidViewSourceException::unsupported('reference_value_kind', 'value');
        }

        return ViewNode::value($path, new SourcePointer($pointer));
    }

    /** @param array<string, mixed> $node
     * @param array<string, PresentationValueSchema> $aliases
     * @param array{nodes: int, static: int} $counts
     */
    private static function elementNode(array $node, string $pointer, ViewContextSchema $context, array $aliases, int $depth, int $loopDepth, array &$counts): ViewNode
    {
        self::keys($node, ['kind', 'name', 'attributes', 'children'], false, 'node_shape');
        $name = self::string($node['name'] ?? null, 'node_shape');
        if (!in_array($name, self::ELEMENTS, true)) {
            throw InvalidViewSourceException::unsupported('element_name', 'element');
        }
        $attributesData = self::list($node['attributes'] ?? null, 'attribute_shape');
        if (count($attributesData) > 16) {
            throw InvalidViewSourceException::bound('attribute_count', 16, count($attributesData));
        }
        $attributes = [];
        $seen = [];
        foreach ($attributesData as $position => $data) {
            $attribute = self::attribute($data, $pointer . '/attributes/' . $position, $context, $aliases, $counts);
            if (isset($seen[$attribute->name()])) {
                throw InvalidViewSourceException::invalid('duplicate_attribute');
            }
            $seen[$attribute->name()] = true;
            $attributes[] = $attribute;
        }
        usort($attributes, static fn(ViewAttribute $left, ViewAttribute $right): int => strcmp($left->name(), $right->name()));
        $children = self::nodes($node['children'] ?? null, $context, $pointer . '/children', $aliases, $depth + 1, $loopDepth, $counts);

        return ViewNode::element($name, $attributes, $children, new SourcePointer($pointer));
    }

    /** @param array<string, mixed> $node
     * @param array<string, PresentationValueSchema> $aliases
     * @param array{nodes: int, static: int} $counts
     */
    private static function eachNode(array $node, string $pointer, ViewContextSchema $context, array $aliases, int $depth, int $loopDepth, array &$counts): ViewNode
    {
        self::keys($node, ['kind', 'path', 'as', 'max_items', 'body'], false, 'node_shape');
        if ($loopDepth + 1 > 8) {
            throw InvalidViewSourceException::bound('loop_depth', 8, depth: $loopDepth + 1);
        }
        $path = self::reference($node['path'] ?? null);
        $schema = self::resolve($path, $context, $aliases);
        if (PresentationValueKind::List !== $schema->kind() || null === $schema->itemSchema() || null === $schema->maxItems()) {
            throw InvalidViewSourceException::unsupported('loop_target', 'each');
        }
        $alias = self::fieldName($node['as'] ?? null);
        if (isset($aliases[$alias])) {
            throw InvalidViewSourceException::unsupported('loop_shadowing', 'each');
        }
        $maximum = self::integerValue($node['max_items'] ?? null, 'node_shape');
        if ($maximum < 0 || $maximum > 256 || $maximum > $schema->maxItems()) {
            throw InvalidViewSourceException::bound('list_items', min(256, $schema->maxItems()), $maximum);
        }
        $aliases[$alias] = $schema->itemSchema();
        $body = self::nodes($node['body'] ?? null, $context, $pointer . '/body', $aliases, $depth + 1, $loopDepth + 1, $counts);

        return ViewNode::each($path, $alias, $maximum, $body, new SourcePointer($pointer));
    }

    /** @param array<string, PresentationValueSchema> $aliases
     * @param array{nodes: int, static: int} $counts
     */
    private static function attribute(mixed $value, string $pointer, ViewContextSchema $context, array $aliases, array &$counts): ViewAttribute
    {
        $attribute = self::object($value, 'attribute_shape');
        self::keys($attribute, ['name', 'value'], false, 'attribute_shape');
        $name = self::string($attribute['name'] ?? null, 'attribute_shape');
        if (!in_array($name, self::ATTRIBUTES, true)) {
            throw InvalidViewSourceException::unsupported('attribute_name', 'element');
        }
        $valueData = self::object($attribute['value'] ?? null, 'attribute_shape');
        $kind = self::string($valueData['kind'] ?? null, 'attribute_shape');
        if ('literal' === $kind) {
            self::keys($valueData, ['kind', 'text'], false, 'attribute_shape');
            $text = self::text($valueData['text'] ?? null);
            self::staticBytes($counts, strlen($text));
            $parsed = ViewAttributeValue::literal($text);
        } elseif ('value' === $kind) {
            self::keys($valueData, ['kind', 'path'], false, 'attribute_shape');
            $path = self::reference($valueData['path'] ?? null);
            if (PresentationValueKind::String !== self::resolve($path, $context, $aliases)->kind()) {
                throw InvalidViewSourceException::unsupported('reference_value_kind', 'element');
            }
            $parsed = ViewAttributeValue::value($path);
        } else {
            throw InvalidViewSourceException::unsupported('attribute_value_kind', 'element');
        }

        return new ViewAttribute($name, $parsed, new SourcePointer($pointer));
    }

    /** @param array<string, PresentationValueSchema> $aliases */
    private static function resolve(string $reference, ViewContextSchema $context, array $aliases): PresentationValueSchema
    {
        if (str_starts_with($reference, '$.')) {
            $schema = $context->rootSchema();
            $segments = explode('.', substr($reference, 2));
        } elseif (1 === preg_match('/\A@([a-z][a-z0-9_]*)(?:\.(.+))?\z/D', $reference, $match)) {
            if (!isset($aliases[$match[1]])) {
                throw InvalidViewSourceException::unsupported('loop_alias');
            }
            $schema = $aliases[$match[1]];
            $segments = isset($match[2]) ? explode('.', $match[2]) : [];
        } else {
            throw InvalidViewSourceException::invalid('reference_shape');
        }
        foreach ($segments as $segment) {
            self::fieldName($segment);
            if (PresentationValueKind::Record !== $schema->kind()) {
                throw InvalidViewSourceException::unsupported('reference_target');
            }
            $found = null;
            foreach ($schema->fields() as $field) {
                if ($field->name() === $segment) {
                    $found = $field;
                    break;
                }
            }
            if (null === $found) {
                throw InvalidViewSourceException::unsupported('reference_target');
            }
            if (!$found->required()) {
                throw InvalidViewSourceException::unsupported('reference_required');
            }
            $schema = $found->schema();
        }

        return $schema;
    }

    /** @param list<ViewNode> $nodes
     * @param list<SourceMapEntry> $entries
     */
    private static function operations(array $nodes, LogicalViewIdentity $identity, array &$entries, int &$index): void
    {
        foreach ($nodes as $node) {
            match ($node->kind()) {
                ViewNodeKind::Text => self::operation($entries, $identity, $index, $node->sourcePointer(), 'literal', null),
                ViewNodeKind::Value => self::operation($entries, $identity, $index, $node->sourcePointer(), 'html_text', OutputContext::HtmlText),
                ViewNodeKind::Element => self::elementOperations($node, $identity, $entries, $index),
                ViewNodeKind::Each => self::eachOperations($node, $identity, $entries, $index),
            };
        }
    }

    /** @param list<SourceMapEntry> $entries */
    private static function elementOperations(ViewNode $node, LogicalViewIdentity $identity, array &$entries, int &$index): void
    {
        self::operation($entries, $identity, $index, $node->sourcePointer(), 'literal', null);
        foreach ($node->attributes() as $attribute) {
            if (ViewAttributeValueKind::Literal === $attribute->value()->kind()) {
                self::operation($entries, $identity, $index, $attribute->sourcePointer(), 'literal', null);
            } else {
                self::operation($entries, $identity, $index, $attribute->sourcePointer(), 'literal', null);
                self::operation($entries, $identity, $index, $attribute->sourcePointer(), 'html_quoted_attribute', OutputContext::HtmlQuotedAttribute);
                self::operation($entries, $identity, $index, $attribute->sourcePointer(), 'literal', null);
            }
        }
        self::operation($entries, $identity, $index, $node->sourcePointer(), 'literal', null);
        self::operations($node->children(), $identity, $entries, $index);
        self::operation($entries, $identity, $index, $node->sourcePointer(), 'literal', null);
    }

    /** @param list<SourceMapEntry> $entries */
    private static function eachOperations(ViewNode $node, LogicalViewIdentity $identity, array &$entries, int &$index): void
    {
        self::operation($entries, $identity, $index, $node->sourcePointer(), 'loop', null);
        self::operations($node->children(), $identity, $entries, $index);
    }

    /** @param list<SourceMapEntry> $entries */
    private static function operation(array &$entries, LogicalViewIdentity $identity, int &$index, SourcePointer $pointer, string $kind, ?OutputContext $context): void
    {
        $entries[] = new SourceMapEntry($identity, $index, $pointer, $kind, $context);
        ++$index;
    }

    /** @param list<SourceMapEntry> $entries
     * @return list<array<string, mixed>>
     */
    private static function sourceMapData(array $entries): array
    {
        return array_map(static fn(SourceMapEntry $entry): array => [
            'view_id' => $entry->viewIdentity()->value(),
            'operation_index' => $entry->operationIndex(),
            'source_pointer' => $entry->sourcePointer()->value(),
            'operation_kind' => $entry->operationKind(),
            'output_context' => $entry->outputContext()?->value,
        ], $entries);
    }

    /** @param array{nodes: int, static: int} $counts */
    private static function staticBytes(array &$counts, int $bytes): void
    {
        $counts['static'] += $bytes;
        if ($counts['static'] > 1_048_576) {
            throw InvalidViewSourceException::bound('static_bytes', 1_048_576, $counts['static']);
        }
    }

    private static function text(mixed $value): string
    {
        $text = self::string($value, 'node_shape');
        if (strlen($text) > 65_536) {
            throw InvalidViewSourceException::bound('string_bytes', 65_536, strlen($text));
        }
        if (str_starts_with($text, "\xEF\xBB\xBF") || str_contains($text, "\r") || str_contains($text, "\0") || 1 !== preg_match('//u', $text) || 1 === preg_match('/[\x01-\x08\x0B-\x1F\x7F]|[\x{0080}-\x{009F}]/u', $text)) {
            throw InvalidViewSourceException::invalid('node_shape');
        }

        return $text;
    }

    private static function reference(mixed $value): string
    {
        $reference = self::string($value, 'reference_shape');
        if (strlen($reference) < 2 || strlen($reference) > 256) {
            throw InvalidViewSourceException::bound('reference_bytes', 256, strlen($reference));
        }
        $segments = str_starts_with($reference, '$.') ? explode('.', substr($reference, 2)) : (1 === preg_match('/\A@[a-z][a-z0-9_]*(?:\.(.*))?\z/D', $reference, $match) ? (isset($match[1]) && '' !== $match[1] ? explode('.', $match[1]) : []) : null);
        if (null === $segments || count($segments) > 16) {
            throw null === $segments ? InvalidViewSourceException::invalid('reference_shape') : InvalidViewSourceException::bound('reference_segments', 16, count($segments));
        }
        foreach ($segments as $segment) {
            self::fieldName($segment);
        }

        return $reference;
    }

    private static function fieldName(mixed $value): string
    {
        $name = self::string($value, 'field_shape');
        if (strlen($name) < 1 || strlen($name) > 64 || 1 !== preg_match('/\A[a-z][a-z0-9_]*\z/D', $name)) {
            throw InvalidViewSourceException::invalid('field_shape');
        }

        return $name;
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $violation): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidViewSourceException::invalid($violation);
        }
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                throw InvalidViewSourceException::invalid($violation);
            }
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private static function list(mixed $value, string $violation): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidViewSourceException::invalid($violation);
        }

        return $value;
    }

    /** @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private static function keys(array $object, array $expected, bool $ordered, string $violation): void
    {
        $actual = array_keys($object);
        if (!$ordered) {
            sort($actual, SORT_STRING);
            sort($expected, SORT_STRING);
        }
        if ($actual !== $expected) {
            throw InvalidViewSourceException::invalid($violation);
        }
    }

    private static function string(mixed $value, string $violation): string
    {
        if (!is_string($value)) {
            throw InvalidViewSourceException::invalid($violation);
        }

        return $value;
    }

    private static function integerValue(mixed $value, string $violation): int
    {
        if (!is_int($value)) {
            throw InvalidViewSourceException::invalid($violation);
        }

        return $value;
    }

    private function __construct() {}
}
