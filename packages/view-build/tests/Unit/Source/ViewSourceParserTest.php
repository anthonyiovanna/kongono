<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Source;

use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewSourceParserTest extends TestCase
{
    public function testParsesHashesDetachesAndCanonicalizesViewsFieldsAndAttributes(): void
    {
        $bytes = BuildProbe::sourceBytes();
        $source = (new ViewSourceParser())->parse($bytes);
        self::assertSame(hash('sha256', $bytes), $source->identity()->value());
        self::assertSame(['page.alpha', 'page.zeta'], array_map(static fn($view): string => $view->identity()->value(), $source->views()));
        self::assertSame(['count', 'enabled', 'items', 'nothing', 'title'], array_map(static fn($field): string => $field->name(), $source->views()[1]->contextSchema()->rootSchema()->fields()));
        self::assertSame(['class', 'title'], array_map(static fn($attribute): string => $attribute->name(), $source->views()[1]->body()[2]->attributes()));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSources(): iterable
    {
        $record = ['kind' => 'record', 'fields' => [
            ['name' => 'title', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 8]],
            ['name' => 'count', 'required' => true, 'schema' => ['kind' => 'integer']],
            ['name' => 'items', 'required' => true, 'schema' => ['kind' => 'list', 'max_items' => 2, 'item' => ['kind' => 'record', 'fields' => [
                ['name' => 'name', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 8]],
            ]]]],
        ]];
        yield 'missing terminal lf' => [rtrim(BuildProbe::sourceBytes(), "\n")];
        yield 'empty' => [''];
        yield 'bom' => ["\xEF\xBB\xBF" . BuildProbe::sourceBytes()];
        yield 'cr' => [str_replace("\n", "\r\n", BuildProbe::sourceBytes())];
        yield 'nul' => ["{\0}\n"];
        yield 'invalid utf8' => ["{\xFF}\n"];
        yield 'two terminal lfs' => [BuildProbe::sourceBytes() . "\n"];
        yield 'source byte bound' => [str_repeat(' ', 1_048_577)];
        yield 'duplicate member' => ['{"schema":"kongono.view-source-set","schema":"kongono.view-source-set","version":1,"views":[]}' . "\n"];
        yield 'float' => ['{"schema":"kongono.view-source-set","version":1.0,"views":[]}' . "\n"];
        yield 'unknown root member' => ['{"schema":"kongono.view-source-set","version":1,"views":[],"extra":true}' . "\n"];
        yield 'wrong scalar type' => [self::document([self::view('view.one', $record, [])], '1')];
        yield 'empty views' => [self::document([])];
        yield 'duplicate logical view' => [self::document([self::view('view.one', $record, []), self::view('view.one', $record, [])])];
        yield 'unknown node kind' => [self::document([self::view('view.one', $record, [['kind' => 'raw', 'text' => 'x']])])];
        yield 'denied browser element' => [self::document([self::view('view.one', $record, [['kind' => 'element', 'name' => 'script', 'attributes' => [], 'children' => []]])])];
        yield 'denied url attribute' => [self::document([self::view('view.one', $record, [['kind' => 'element', 'name' => 'div', 'attributes' => [['name' => 'href', 'value' => ['kind' => 'literal', 'text' => 'x']]], 'children' => []]])])];
        yield 'optional field traversal' => [self::document([self::view('view.one', ['kind' => 'record', 'fields' => [
            ['name' => 'title', 'required' => false, 'schema' => ['kind' => 'string', 'max_bytes' => 8]],
        ]], [['kind' => 'value', 'path' => '$.title']])])];
        yield 'value type mismatch' => [self::document([self::view('view.one', $record, [['kind' => 'value', 'path' => '$.count']])])];
        yield 'loop type mismatch' => [self::document([self::view('view.one', $record, [['kind' => 'each', 'path' => '$.title', 'as' => 'item', 'max_items' => 1, 'body' => []]])])];
        yield 'loop declared bound mismatch' => [self::document([self::view('view.one', $record, [['kind' => 'each', 'path' => '$.items', 'as' => 'item', 'max_items' => 3, 'body' => []]])])];
        yield 'unknown loop alias' => [self::document([self::view('view.one', $record, [['kind' => 'value', 'path' => '@missing.name']])])];

        $nestedListRecord = ['kind' => 'record', 'fields' => [
            ['name' => 'items', 'required' => true, 'schema' => ['kind' => 'list', 'max_items' => 2, 'item' => ['kind' => 'list', 'max_items' => 2, 'item' => ['kind' => 'string', 'max_bytes' => 8]]]],
        ]];
        yield 'loop alias shadowing' => [self::document([self::view('view.one', $nestedListRecord, [[
            'kind' => 'each', 'path' => '$.items', 'as' => 'item', 'max_items' => 2, 'body' => [[
                'kind' => 'each', 'path' => '@item', 'as' => 'item', 'max_items' => 2, 'body' => [],
            ]],
        ]])])];

        $schema = ['kind' => 'string', 'max_bytes' => 1];
        for ($depth = 0; $depth < 16; ++$depth) {
            $schema = ['kind' => 'list', 'max_items' => 1, 'item' => $schema];
        }
        yield 'schema depth bound' => [self::document([self::view('view.one', ['kind' => 'record', 'fields' => [
            ['name' => 'deep', 'required' => true, 'schema' => $schema],
        ]], [])])];

        $node = ['kind' => 'text', 'text' => 'x'];
        for ($depth = 0; $depth < 32; ++$depth) {
            $node = ['kind' => 'element', 'name' => 'div', 'attributes' => [], 'children' => [$node]];
        }
        yield 'node depth bound' => [self::document([self::view('view.one', $record, [$node])])];

        $views = [];
        for ($position = 0; $position < 129; ++$position) {
            $views[] = self::view('view-' . $position, ['kind' => 'record', 'fields' => []], [['kind' => 'text', 'text' => 'x']]);
        }
        yield 'view count bound' => [self::document($views)];

        $views = [];
        foreach ([2048, 2048, 1] as $view => $count) {
            $views[] = self::view('bulk-' . $view, ['kind' => 'record', 'fields' => []], array_fill(0, $count, ['kind' => 'text', 'text' => 'x']));
        }
        yield 'aggregate node count bound' => [self::document($views)];

        yield 'text byte bound' => [self::document([self::view('view.one', $record, [['kind' => 'text', 'text' => str_repeat('x', 65_537)]])])];
    }

    #[DataProvider('invalidSources')]
    public function testRejectsInvalidFramingDuplicateMembersNumbersAndShape(string $bytes): void
    {
        $this->expectException(InvalidViewSourceException::class);
        (new ViewSourceParser())->parse($bytes);
    }

    /** @param array<string, mixed> $value */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /** @param list<array<string, mixed>> $views */
    private static function document(array $views, mixed $version = 1): string
    {
        return self::encode(['schema' => 'kongono.view-source-set', 'version' => $version, 'views' => $views]);
    }

    /** @param array<string, mixed> $context
     * @param list<array<string, mixed>> $body
     * @return array<string, mixed>
     */
    private static function view(string $identity, array $context, array $body): array
    {
        return ['id' => $identity, 'context' => $context, 'body' => $body];
    }
}
