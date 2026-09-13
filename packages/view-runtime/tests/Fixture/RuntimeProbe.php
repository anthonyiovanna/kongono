<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Fixture;

use Kongono\ViewBuild\Artifact\CompiledViewArtifact;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactBuilder;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactPublisher;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Render\CompiledViewRenderer;

final class RuntimeProbe
{
    public static function sourceBytes(): string
    {
        return json_encode([
            'schema' => 'kongono.view-source-set',
            'version' => 1,
            'views' => [[
                'id' => 'page.alpha',
                'context' => ['kind' => 'record', 'fields' => [
                    ['name' => 'message', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 256]],
                ]],
                'body' => [
                    ['kind' => 'text', 'text' => '<p>'],
                    ['kind' => 'value', 'path' => '$.message'],
                    ['kind' => 'text', 'text' => '</p>'],
                ],
            ], [
                'id' => 'page.list',
                'context' => ['kind' => 'record', 'fields' => [
                    ['name' => 'items', 'required' => true, 'schema' => ['kind' => 'list', 'max_items' => 3, 'item' => ['kind' => 'record', 'fields' => [
                        ['name' => 'name', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 64]],
                    ]]]],
                    ['name' => 'title', 'required' => true, 'schema' => ['kind' => 'string', 'max_bytes' => 128]],
                ]],
                'body' => [[
                    'kind' => 'element',
                    'name' => 'div',
                    'attributes' => [['name' => 'title', 'value' => ['kind' => 'value', 'path' => '$.title']]],
                    'children' => [[
                        'kind' => 'each',
                        'path' => '$.items',
                        'as' => 'item',
                        'max_items' => 3,
                        'body' => [['kind' => 'value', 'path' => '@item.name']],
                    ]],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    public static function artifact(): CompiledViewArtifact
    {
        return (new CompiledViewArtifactBuilder())->build((new ViewSourceParser())->parse(self::sourceBytes()));
    }

    /** @return array{root: string, directory: string, artifact: CompiledViewArtifact} */
    public static function publish(): array
    {
        return self::publishSource(self::sourceBytes());
    }

    /** @return array{root: string, directory: string, artifact: CompiledViewArtifact} */
    public static function publishSource(string $sourceBytes): array
    {
        $root = sys_get_temp_dir() . '/kongono-view-runtime-' . bin2hex(random_bytes(8));
        if (!mkdir($root, 0700)) {
            throw new \RuntimeException('Fixture root could not be created.');
        }
        $resolved = realpath($root);
        if (false === $resolved) {
            throw new \RuntimeException('Fixture root could not be resolved.');
        }
        $root = self::normalize($resolved);
        $artifact = (new CompiledViewArtifactBuilder())->build((new ViewSourceParser())->parse($sourceBytes));
        $directory = (new CompiledViewArtifactPublisher())->publish($artifact, $root);

        return ['root' => $root, 'directory' => $directory, 'artifact' => $artifact];
    }

    public static function alphaRequest(CompiledViewArtifact $artifact, string $message, int $maxBytes = 1024, int $maxSteps = 32, string $operation = 'render.alpha'): RenderRequest
    {
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('message', PresentationValueSchema::string(256), true),
        ]));

        return new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity($operation), new LogicalViewIdentity('page.alpha'), $artifact->compiledViewIdentity(), $schema, new ViewContext(['message' => new StringValue($message)]), new RenderLimits($maxBytes, $maxSteps));
    }

    /** @param list<string> $names */
    public static function listRequest(CompiledViewArtifact $artifact, string $title, array $names, int $maxBytes = 4096, int $maxSteps = 128, string $operation = 'render.list'): RenderRequest
    {
        $itemSchema = PresentationValueSchema::record([new PresentationFieldSchema('name', PresentationValueSchema::string(64), true)]);
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('items', PresentationValueSchema::list($itemSchema, 3), true),
            new PresentationFieldSchema('title', PresentationValueSchema::string(128), true),
        ]));
        $items = array_map(static fn(string $name): RecordValue => new RecordValue(['name' => new StringValue($name)]), $names);

        return new RenderRequest(ContractVersion::Version1, new RenderOperationIdentity($operation), new LogicalViewIdentity('page.list'), $artifact->compiledViewIdentity(), $schema, new ViewContext(['items' => new ListValue($items), 'title' => new StringValue($title)]), new RenderLimits($maxBytes, $maxSteps));
    }

    /** @param array{root: string, directory: string, artifact: CompiledViewArtifact} $fixture */
    public static function cleanup(array $fixture): void
    {
        foreach ([$fixture['directory'] . '/artifact.json', $fixture['directory'] . '/compiled.php'] as $path) {
            if (is_dir($path)) {
                if (!rmdir($path)) {
                    throw new \RuntimeException('Fixture directory entry could not be removed.');
                }
            } elseif (file_exists($path) && !unlink($path)) {
                throw new \RuntimeException('Fixture file could not be removed.');
            }
        }
        if (is_dir($fixture['directory']) && !rmdir($fixture['directory'])) {
            throw new \RuntimeException('Fixture directory could not be removed.');
        }
        if (is_dir($fixture['root']) && !rmdir($fixture['root'])) {
            throw new \RuntimeException('Fixture root could not be removed.');
        }
    }

    private static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return 1 === preg_match('/\A[A-Z]:\//D', $path) ? strtolower($path[0]) . substr($path, 1) : $path;
    }

    private function __construct() {}
}

$script = $_SERVER['SCRIPT_FILENAME'] ?? null;
if (is_string($script) && realpath($script) === __FILE__) {
    require dirname(__DIR__, 4) . '/vendor/autoload.php';
    $fixture = RuntimeProbe::publish();
    try {
        $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
        $result = (new CompiledViewRenderer())->render($loaded, RuntimeProbe::alphaRequest($fixture['artifact'], '<&>'));
        if (!$result instanceof RenderResult) {
            throw new \RuntimeException('Fixture render did not complete.');
        }
        echo json_encode(['type' => $result::class, 'output' => $result->output()->bytes()], JSON_THROW_ON_ERROR) . "\n";
    } finally {
        RuntimeProbe::cleanup($fixture);
    }
}
