<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class AssetCheckCommandHandlerTest extends TestCase
{
    private ?string $root = null;
    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::remove($this->root);
        }
    }

    public function testCheckIsWriteFreeAndFailsClosedWhenMissing(): void
    {
        [$root, $source, $output] = $this->application();
        $inputs = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=' . $source];
        [$missing, $failure] = self::execute(array_merge(['--json', 'asset:check'], $inputs));
        [$humanMissing, $humanFailure] = self::execute(array_merge(['asset:check'], $inputs));
        self::assertSame(3, $missing);
        self::assertSame(3, $humanMissing);
        self::assertStringNotContainsString($root, $failure);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $failure);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $humanFailure);
        $entries = glob($output . '/*');
        self::assertSame([], false === $entries ? [] : $entries);
        self::assertSame(0, self::execute(array_merge(['--json', 'asset:build', '--apply'], $inputs))[0]);
        $before = self::hashes($output);
        [$status, $result] = self::execute(array_merge(['--json', 'asset:check'], $inputs));
        [$humanStatus, $humanResult] = self::execute(array_merge(['asset:check'], $inputs));
        self::assertSame(0, $status);
        self::assertSame(0, $humanStatus);
        self::assertStringContainsString('kongono.asset-application-summary', $result);
        self::assertSemanticParity($humanResult, $result);
        self::assertSame($before, self::hashes($output));
    }

    /** @return array{string,string,string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-asset-check-command-' . bin2hex(random_bytes(8));
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
            'artifact_basename' => 'Artifact basename', 'build_identity' => 'Build identity', 'current' => 'Current',
            'declaration_count' => 'Declaration count', 'dependency_count' => 'Dependency count', 'entry_point_count' => 'Entry point count',
            'graph_identity' => 'Graph identity', 'output_count' => 'Output count', 'producer_kind' => 'Producer kind',
            'publication_required' => 'Publication required', 'resolution_identity' => 'Resolution identity', 'schema' => 'Schema',
            'source_set_identity' => 'Source set identity', 'version' => 'Version',
        ];
        foreach ($fields as $field) {
            self::assertIsArray($field);
            $name = $field['name'] ?? null;
            self::assertIsString($name);
            self::assertArrayHasKey($name, $labels);
            $value = $field['value'] ?? null;
            if (!is_bool($value) && !is_int($value) && !is_string($value)) {
                self::fail('A presented field must carry a scalar value.');
            }
            $display = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            self::assertStringContainsString($labels[$name] . ': ' . $display, $human);
        }
    }
    /** @return array<string,string> */
    private static function hashes(string $root): array
    {
        $hashes = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new \RuntimeException('Invalid test inventory.');
            } if ($entry->isFile()) {
                $path = str_replace('\\', '/', $entry->getPathname());
                $hashes[substr($path, strlen($root) + 1)] = (string) hash_file('sha256', $path);
            }
        } ksort($hashes);
        return $hashes;
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
