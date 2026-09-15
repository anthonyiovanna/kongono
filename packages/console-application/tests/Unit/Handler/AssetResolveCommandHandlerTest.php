<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class AssetResolveCommandHandlerTest extends TestCase
{
    private ?string $root = null;
    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::remove($this->root);
        }
    }

    public function testHumanAndJsonModesExposeTheSameResolvedRecord(): void
    {
        [$root, $source, $output] = $this->application();
        $base = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=' . $source];
        self::assertSame(0, self::execute(array_merge(['--json', 'asset:build', '--apply'], $base))[0]);
        $inputs = array_merge($base, ['--logical-identity=app.style', '--public-base-url=/assets/', '--cache-policy=public, max-age=31536000, immutable', '--integrity-policy=require_artifact_sha384', '--preload-policy=honor_artifact']);
        [$jsonStatus, $json] = self::execute(array_merge(['--json', 'asset:resolve'], $inputs));
        [$humanStatus, $human] = self::execute(array_merge(['asset:resolve'], $inputs));
        self::assertSame(0, $jsonStatus);
        self::assertSame(0, $humanStatus);
        foreach (['Logical identity', 'Public URL', 'Content identity', 'Cache-Control', 'Integrity'] as $label) {
            self::assertStringContainsString($label, $human);
        }
        foreach (['logical_identity', 'public_url', 'content_identity', 'cache_control', 'integrity'] as $name) {
            self::assertStringContainsString('"name":"' . $name . '"', $json);
        }
        self::assertSemanticParity($human, $json);
        self::assertStringNotContainsString($root, $json . $human);
        [$invalidJsonStatus, $invalidJson] = self::execute(array_merge(['--json', 'asset:resolve'], array_slice($inputs, 0, -1)));
        [$invalidHumanStatus, $invalidHuman] = self::execute(array_merge(['asset:resolve'], array_slice($inputs, 0, -1)));
        self::assertSame(2, $invalidJsonStatus);
        self::assertSame(2, $invalidHumanStatus);
        self::assertStringContainsString('KGN-CONSOLE-', $invalidJson);
        self::assertStringContainsString('KGN-CONSOLE-', $invalidHuman);
    }

    /** @return array{string,string,string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-asset-resolve-command-' . bin2hex(random_bytes(8));
        mkdir($root . '/var/assets', 0777, true);
        file_put_contents($root . '/app.css', "body{}\n");
        $source = $root . '/assets.json';
        file_put_contents($source, "{\"schema\":\"kongono.asset-application-source\",\"version\":1,\"contract_version\":1,\"declarations\":[{\"identity\":\"app.style\",\"kind\":\"style\",\"content_type\":\"text/css\",\"source_path\":\"app.css\"}],\"dependencies\":[],\"entry_points\":[\"app.style\"]}\n");
        $this->root = $root;
        return [$root, $source, $root . '/var/assets'];
    }
    /**
     * @param list<string> $arguments
     *
     * @return array{int,string}
     */
    private static function execute(array $arguments): array
    {
        $out = self::port();
        $err = self::port();
        $status = (new KongoApplicationFactory())->create()->run(new KongoProcessRequest($arguments), $out, $err);
        return [$status, $out->bytes . $err->bytes];
    }
    /** @return CommandOutputPort&object{bytes:string} */
    private static function port(): CommandOutputPort
    {
        return new class implements CommandOutputPort {
            public string $bytes = '';
            public function write(string $bytes): void
            {
                $this->bytes .= $bytes;
            }
        };
    }
    private static function assertSemanticParity(string $human, string $json): void
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $data = $decoded['data'] ?? null;
        self::assertIsArray($data);
        $fields = $data['fields'] ?? null;
        self::assertIsArray($fields);
        $labels = [
            'byte_count' => 'Byte count', 'cache_control' => 'Cache-Control', 'content_identity' => 'Content identity',
            'content_type' => 'Content type', 'dependency_count' => 'Dependency count', 'etag' => 'ETag',
            'integrity' => 'Integrity', 'kind' => 'Kind', 'logical_identity' => 'Logical identity', 'preload' => 'Preload',
            'public_url' => 'Public URL', 'schema' => 'Schema', 'version' => 'Version',
        ];
        foreach ($fields as $field) {
            self::assertIsArray($field);
            $name = $field['name'] ?? null;
            self::assertIsString($name);
            self::assertArrayHasKey($name, $labels);
            $value = $field['value'] ?? null;
            if (!is_int($value) && !is_string($value)) {
                self::fail('A resolved field must carry an integer or text value.');
            }
            self::assertStringContainsString($labels[$name] . ': ' . (string) $value, $human);
        }
    }
    private static function remove(string $root): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new \RuntimeException('Invalid test inventory.');
            } $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        } rmdir($root);
    }
}
