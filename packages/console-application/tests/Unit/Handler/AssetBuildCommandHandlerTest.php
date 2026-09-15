<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class AssetBuildCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::remove($this->root);
        }
    }

    public function testPlanAndApplyAreExplicitSafeAndIdempotent(): void
    {
        [$root, $source, $output] = $this->application();
        $inputs = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=' . $source];
        [$status, $plan] = self::execute(array_merge(['--json', 'asset:build', '--plan'], $inputs));
        [$humanPlanStatus, $humanPlan] = self::execute(array_merge(['asset:build', '--plan'], $inputs));
        self::assertSame(0, $status);
        self::assertSame(0, $humanPlanStatus);
        self::assertStringContainsString('"value":"plan"', $plan);
        self::assertSemanticParity($humanPlan, $plan);
        $entries = glob($output . '/*');
        self::assertSame([], false === $entries ? [] : $entries);

        [$status, $apply] = self::execute(array_merge(['--json', 'asset:build', '--apply'], $inputs));
        [$humanApplyStatus, $humanApply] = self::execute(array_merge(['asset:build', '--apply'], $inputs));
        self::assertSame(0, $status);
        self::assertSame(0, $humanApplyStatus);
        self::assertStringContainsString('kongono.asset-application-summary', $apply);
        self::assertStringNotContainsString($root, $apply);
        self::assertSemanticParity($humanApply, $apply);
        [$repeatStatus, $repeat] = self::execute(array_merge(['--json', 'asset:build', '--apply'], $inputs));
        self::assertSame(0, $repeatStatus);
        self::assertSame($apply, $repeat);

        [$invalidJsonStatus, $invalidJson] = self::execute(['--json', 'asset:build', '--plan']);
        [$invalidHumanStatus, $invalidHuman] = self::execute(['asset:build', '--plan']);
        self::assertSame(2, $invalidJsonStatus);
        self::assertSame(2, $invalidHumanStatus);
        self::assertStringContainsString('KGN-CONSOLE-', $invalidJson);
        self::assertStringContainsString('KGN-CONSOLE-', $invalidHuman);
    }

    /** @return array{string,string,string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-asset-build-command-' . bin2hex(random_bytes(8));
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
            'execution_intent' => 'Execution intent', 'graph_identity' => 'Graph identity', 'output_count' => 'Output count',
            'producer_kind' => 'Producer kind', 'publication_required' => 'Publication required', 'resolution_identity' => 'Resolution identity',
            'schema' => 'Schema', 'source_set_identity' => 'Source set identity', 'version' => 'Version',
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
