<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Fixture;

use Kongono\ViewBuild\Artifact\CompiledViewArtifact;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactBuilder;
use Kongono\ViewBuild\Source\ViewSourceParser;

final class BuildProbe
{
    public static function sourceBytes(): string
    {
        return json_encode([
            'schema' => 'kongono.view-source-set',
            'version' => 1,
            'views' => [
                [
                    'id' => 'page.zeta',
                    'context' => ['kind' => 'record', 'fields' => [
                        ['name' => 'title', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 128]],
                        ['name' => 'count', 'required' => true, 'schema' => ['kind' => 'integer']],
                        ['name' => 'enabled', 'required' => true, 'schema' => ['kind' => 'boolean']],
                        ['name' => 'nothing', 'required' => true, 'schema' => ['kind' => 'null']],
                        ['name' => 'items', 'required' => true, 'schema' => ['kind' => 'list', 'max_items' => 2, 'item' => ['kind' => 'record', 'fields' => [
                            ['name' => 'name', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 64]],
                        ]]]],
                    ]],
                    'body' => [
                        ['kind' => 'text', 'text' => '<safe & static>'],
                        ['kind' => 'value', 'path' => '$.title'],
                        ['kind' => 'element', 'name' => 'div', 'attributes' => [
                            ['name' => 'title', 'value' => ['kind' => 'value', 'path' => '$.title']],
                            ['name' => 'class', 'value' => ['kind' => 'literal', 'text' => 'a"b&c']],
                        ], 'children' => [
                            ['kind' => 'each', 'path' => '$.items', 'as' => 'item', 'max_items' => 2, 'body' => [
                                ['kind' => 'element', 'name' => 'span', 'attributes' => [], 'children' => [
                                    ['kind' => 'value', 'path' => '@item.name'],
                                ]],
                            ]],
                        ]],
                    ],
                ],
                [
                    'id' => 'page.alpha',
                    'context' => ['kind' => 'record', 'fields' => [
                        ['name' => 'message', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 32]],
                    ]],
                    'body' => [['kind' => 'value', 'path' => '$.message']],
                ],
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    public static function artifact(): CompiledViewArtifact
    {
        return (new CompiledViewArtifactBuilder())->build((new ViewSourceParser())->parse(self::sourceBytes()));
    }
}

$script = $_SERVER['SCRIPT_FILENAME'] ?? null;
if (is_string($script) && realpath($script) === __FILE__) {
    require dirname(__DIR__, 4) . '/vendor/autoload.php';
    $artifact = BuildProbe::artifact();
    echo json_encode([
        $artifact->sourceIdentity()->value(),
        $artifact->modelSha256(),
        $artifact->generatedPhpSha256(),
        $artifact->compiledViewIdentity()->value(),
    ], JSON_THROW_ON_ERROR), "\n";
}
