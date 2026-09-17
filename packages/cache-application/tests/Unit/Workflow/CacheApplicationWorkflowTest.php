<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Tests\Unit\Workflow;

use Kongono\CacheApplication\Definition\CacheApplicationDefinition;
use Kongono\CacheApplication\Exception\CacheApplicationWorkflowException;
use Kongono\CacheApplication\Exception\InvalidCacheApplicationException;
use Kongono\CacheApplication\Workflow\CacheApplicationWorkflow;
use PHPUnit\Framework\TestCase;

final class CacheApplicationWorkflowTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::remove($this->root);
        }
    }

    public function testPlanIsWriteFreeAndBuildCheckAreExactCurrentAndIdempotent(): void
    {
        $definition = $this->application();
        $workflow = new CacheApplicationWorkflow();
        $plan = $workflow->plan($definition);
        self::assertTrue($plan->publicationRequired());
        self::assertSame([], self::entries($definition->outputDirectory()));

        $current = $workflow->build($definition);
        self::assertSame($plan->artifactIdentity(), $current->artifact()->artifactIdentity()->value());
        self::assertFalse($workflow->plan($definition)->publicationRequired());
        self::assertTrue($current->artifact()->equals($workflow->check($definition)->artifact()));
        self::assertTrue($current->artifact()->equals($workflow->build($definition)->artifact()));

        $path = $definition->outputDirectory() . '/objects/sha256/' . substr($plan->artifactIdentity(), 0, 2) . '/' . $plan->artifactIdentity() . '/cache-profile.json';
        file_put_contents($path, "{}\n");
        $this->expectException(CacheApplicationWorkflowException::class);
        $workflow->check($definition);
    }

    public function testAmbiguousSourcePathsFailBeforeRead(): void
    {
        $definition = $this->application();
        $this->expectException(InvalidCacheApplicationException::class);
        (new CacheApplicationWorkflow())->plan(new CacheApplicationDefinition($definition->applicationRoot(), '../cache.json', $definition->outputDirectory()));
    }

    private function application(): CacheApplicationDefinition
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-cache-application-' . bin2hex(random_bytes(8));
        mkdir($root . '/var/cache', 0777, true);
        file_put_contents($root . '/cache.json', "{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[{\"name\":\"application\",\"contract_version\":1,\"namespace\":\"application\",\"codec\":{\"name\":\"application-json\",\"version\":1},\"lifetime_microseconds\":1000000,\"max_value_bytes\":1024}]}\n");
        $this->root = $root;
        return new CacheApplicationDefinition($root, 'cache.json', $root . '/var/cache');
    }

    /** @return list<string> */
    private static function entries(string $directory): array
    {
        $entries = scandir($directory);
        return false === $entries ? [] : array_values(array_diff($entries, ['.', '..']));
    }

    private static function remove(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new \RuntimeException('Invalid test inventory.');
            }
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($root);
    }
}
