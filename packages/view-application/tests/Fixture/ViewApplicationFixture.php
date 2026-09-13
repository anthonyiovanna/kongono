<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Tests\Fixture;

use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;

final class ViewApplicationFixture
{
    public static function sourceBytes(): string
    {
        return json_encode([
            'schema' => 'kongono.view-source-set',
            'version' => 1,
            'views' => [[
                'id' => 'page.home',
                'context' => ['kind' => 'record', 'fields' => [[
                    'name' => 'message',
                    'required' => true,
                    'schema' => ['kind' => 'string', 'max_bytes' => 64],
                ]]],
                'body' => [
                    ['kind' => 'value', 'path' => '$.message'],
                    ['kind' => 'text', 'text' => "\n"],
                ],
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /** @return array{root:string,source:string,output:string,definition:ViewApplicationDefinition} */
    public static function create(): array
    {
        $root = sys_get_temp_dir() . '/kongono-view-application-' . bin2hex(random_bytes(8));
        if (!mkdir($root . '/views', 0700, true) || !mkdir($root . '/var/view', 0700, true)) {
            throw new \RuntimeException('Fixture directories could not be created.');
        }
        $root = self::normalize((string) realpath($root));
        $source = $root . '/views/home.view.json';
        $output = $root . '/var/view';
        if (strlen(self::sourceBytes()) !== file_put_contents($source, self::sourceBytes())) {
            throw new \RuntimeException('Fixture source could not be written.');
        }

        return [
            'root' => $root,
            'source' => $source,
            'output' => $output,
            'definition' => new ViewApplicationDefinition($root, $source, $output),
        ];
    }

    public static function request(CompiledViewIdentity $identity, string $message = 'Hello from Kongono.', string $view = 'page.home'): RenderRequest
    {
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('message', PresentationValueSchema::string(64), true),
        ]));

        return new RenderRequest(
            ContractVersion::Version1,
            new RenderOperationIdentity('view-application.test'),
            new LogicalViewIdentity($view),
            $identity,
            $schema,
            new ViewContext(['message' => new StringValue($message)]),
            new RenderLimits(128, 16),
        );
    }

    /** @param array{root:string,source:string,output:string,definition:ViewApplicationDefinition} $fixture */
    public static function cleanup(array $fixture): void
    {
        self::remove($fixture['root']);
    }

    private static function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $entries = scandir($path);
        if (false === $entries) {
            throw new \RuntimeException('Fixture directory could not be read.');
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $child = $path . '/' . $entry;
            if (is_dir($child) && !is_link($child)) {
                self::remove($child);
            } elseif (!unlink($child)) {
                throw new \RuntimeException('Fixture file could not be removed.');
            }
        }
        if (!rmdir($path)) {
            throw new \RuntimeException('Fixture directory could not be removed.');
        }
    }

    private static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return 1 === preg_match('/\A[A-Z]:\//D', $path) ? strtolower($path[0]) . substr($path, 1) : $path;
    }

    private function __construct() {}
}
