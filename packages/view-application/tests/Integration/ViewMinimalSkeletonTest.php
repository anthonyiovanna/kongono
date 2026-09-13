<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ViewMinimalSkeletonTest extends TestCase
{
    private ?string $temporary = null;

    protected function tearDown(): void
    {
        if (null !== $this->temporary) {
            self::removeTree($this->temporary);
        }
    }

    public function testFreshCopyInstallBuildRebuildCheckRunAndFailureSafety(): void
    {
        $repository = str_replace('\\', '/', dirname(__DIR__, 4));
        $temporary = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-view-minimal-' . bin2hex(random_bytes(8));
        $this->temporary = $temporary;
        $application = $temporary . '/skeletons/view-minimal';
        self::copyTree($repository . '/skeletons/view-minimal', $application);
        self::localizeLockedPackages($repository, $application, $temporary . '/packages');

        $environment = self::environment($temporary);
        self::successful([...self::composer(), 'install', '--no-interaction', '--no-ansi', '--no-plugins', '--no-scripts'], $application, $environment);
        $owned = self::ownedIdentity($application);

        $first = self::successful([...self::composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        self::assertStringContainsString('View build complete.', $first['stdout']);
        $afterFirst = self::generatedIdentity($application);
        $second = self::successful([...self::composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        self::assertStringContainsString('View build complete.', $second['stdout']);
        self::assertSame($afterFirst, self::generatedIdentity($application));
        self::successful([...self::composer(), 'view:check', '--no-interaction', '--no-ansi'], $application, $environment);
        $run = self::successful([...self::composer(), 'view:run', '--no-interaction', '--no-ansi'], $application, $environment);
        self::assertStringContainsString("Hello from Kongono.\n", $run['stdout']);
        $notFound = self::successful([PHP_BINARY, '-r', <<<'PHP'
$root = $argv[1];
require $root . '/vendor/autoload.php';
require $root . '/src/Http/NotFoundHandler.php';
$request = (new Kongono\Http\Message\Factory\ServerRequestFactory())->createServerRequest('GET', '/missing');
$response = (new ViewMinimalApp\Http\NotFoundHandler())->handle($request);
if (404 !== $response->getStatusCode() || 'text/plain; charset=utf-8' !== $response->getHeaderLine('Content-Type') || "Not Found.\n" !== (string) $response->getBody()) {
    exit(1);
}
fwrite(STDOUT, (string) $response->getBody());
PHP, $application], $application, $environment);
        self::assertSame("Not Found.\n", $notFound['stdout']);
        self::assertSame($owned, self::ownedIdentity($application));

        $compiled = self::singleGenerated($application . '/var/kongono/view', 'compiled.php');
        self::assertSame(9, file_put_contents($compiled, 'conflict!'));
        $conflict = self::process([...self::composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        self::assertNotSame(0, $conflict['exit']);
        self::assertSame('conflict!', file_get_contents($compiled));
        self::assertStringNotContainsString($application, $conflict['stdout'] . $conflict['stderr']);

        $source = $application . '/views/home.view.json';
        $sourceBytes = (string) file_get_contents($source);
        $sentinel = "private-skeleton-source\r\n";
        self::assertSame(strlen($sentinel), file_put_contents($source, $sentinel));
        $rejected = self::process([...self::composer(), 'view:build', '--no-interaction', '--no-ansi'], $application, $environment);
        self::assertNotSame(0, $rejected['exit']);
        self::assertStringNotContainsString('private-skeleton-source', $rejected['stdout'] . $rejected['stderr']);
        self::assertStringNotContainsString($application, $rejected['stdout'] . $rejected['stderr']);
        self::assertSame(strlen($sourceBytes), file_put_contents($source, $sourceBytes));
        self::assertSame($owned, self::ownedIdentity($application));
    }

    private static function localizeLockedPackages(string $repository, string $application, string $packages): void
    {
        $lockPath = $application . '/composer.lock';
        $lock = json_decode((string) file_get_contents($lockPath), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($lock);
        $locked = $lock['packages'] ?? null;
        self::assertIsArray($locked);
        foreach ($locked as $index => $package) {
            self::assertIsArray($package);
            $name = $package['name'] ?? null;
            self::assertIsString($name);
            if (str_starts_with($name, 'kongono/')) {
                $basename = substr($name, strlen('kongono/'));
                $source = $repository . '/packages/' . $basename;
            } elseif (str_starts_with($name, 'psr/')) {
                $basename = 'psr-' . substr($name, strlen('psr/'));
                $source = $repository . '/vendor/psr/' . substr($name, strlen('psr/'));
            } else {
                self::fail('Skeleton lock contains an unauthorized package.');
            }
            self::copyTree($source, $packages . '/' . $basename);
            self::assertIsArray($package['dist'] ?? null);
            $package['dist'] = ['type' => 'path', 'url' => '../../packages/' . $basename, 'reference' => $package['dist']['reference'] ?? null, 'shasum' => null];
            $package['transport-options'] = ['symlink' => false, 'relative' => true];
            unset($package['source']);
            $locked[$index] = $package;
        }
        $lock['packages'] = $locked;
        $bytes = json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        self::assertSame(strlen($bytes), file_put_contents($lockPath, $bytes));
    }

    /** @return list<string> */
    private static function composer(): array
    {
        $path = getenv('PATH');
        foreach (is_string($path) ? explode(PATH_SEPARATOR, $path) : [] as $directory) {
            $candidate = rtrim($directory, '\\/') . '/composer.phar';
            $resolved = realpath($candidate);
            if (false !== $resolved && is_file($resolved) && !is_link($candidate)) {
                return [PHP_BINARY, str_replace('\\', '/', $resolved)];
            }
        }
        throw new RuntimeException('Composer binary is unavailable.');
    }

    /** @return array<string,string> */
    private static function environment(string $temporary): array
    {
        $environment = [
            'COMPOSER_CACHE_DIR' => $temporary . '/composer-cache',
            'COMPOSER_DISABLE_NETWORK' => '1',
            'COMPOSER_HOME' => $temporary . '/composer-home',
            'COMPOSER_NO_INTERACTION' => '1',
        ];
        foreach (['SystemRoot', 'WINDIR', 'COMSPEC', 'TEMP', 'TMP', 'TMPDIR'] as $name) {
            $value = getenv($name);
            if (is_string($value) && '' !== $value) {
                $environment[$name] = $value;
            }
        }

        return $environment;
    }

    /**
     * @param list<string>          $command
     * @param array<string, string> $environment
     *
     * @return array{exit:int,stdout:string,stderr:string}
     */
    private static function successful(array $command, string $directory, array $environment): array
    {
        $result = self::process($command, $directory, $environment);
        self::assertSame(0, $result['exit'], $result['stdout'] . $result['stderr']);

        return $result;
    }

    /**
     * @param list<string>          $command
     * @param array<string, string> $environment
     *
     * @return array{exit:int,stdout:string,stderr:string}
     */
    private static function process(array $command, string $directory, array $environment): array
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $directory, $environment, ['bypass_shell' => true]);
        if (!is_resource($process) || !isset($pipes[0], $pipes[1], $pipes[2])) {
            throw new RuntimeException('Process could not be created.');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1], 1_048_577);
        $stderr = stream_get_contents($pipes[2], 1_048_577);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if (!is_string($stdout) || !is_string($stderr) || strlen($stdout) > 1_048_576 || strlen($stderr) > 1_048_576) {
            throw new RuntimeException('Process output was invalid.');
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /** @return array<string,string> */
    private static function ownedIdentity(string $application): array
    {
        $ownership = json_decode((string) file_get_contents($application . '/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($ownership);
        $paths = $ownership['template_paths'] ?? null;
        self::assertIsArray($paths);
        $identity = [];
        foreach ($paths as $path) {
            self::assertIsString($path);
            $sha256 = hash_file('sha256', $application . '/' . $path);
            self::assertIsString($sha256);
            $identity[$path] = $sha256;
        }

        return $identity;
    }

    /** @return array<string,string> */
    private static function generatedIdentity(string $application): array
    {
        $identity = [];
        foreach ([$application . '/var/kongono/build', $application . '/var/kongono/view'] as $root) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
                if (!$entry instanceof SplFileInfo || !$entry->isFile() || '.gitignore' === $entry->getFilename()) {
                    continue;
                }
                $path = str_replace('\\', '/', $entry->getPathname());
                $sha256 = hash_file('sha256', $path);
                self::assertIsString($sha256);
                $identity[substr($path, strlen($application) + 1)] = $sha256;
            }
        }
        ksort($identity, SORT_STRING);

        return $identity;
    }

    private static function singleGenerated(string $root, string $basename): string
    {
        $matches = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && $basename === $entry->getFilename()) {
                $matches[] = str_replace('\\', '/', $entry->getPathname());
            }
        }
        self::assertCount(1, $matches);

        return $matches[0];
    }

    private static function copyTree(string $source, string $destination): void
    {
        if (!mkdir($destination, 0700, true) && !is_dir($destination)) {
            throw new RuntimeException('Copy destination could not be created.');
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink()) {
                throw new RuntimeException('Copy source entry is invalid.');
            }
            $relative = substr(str_replace('\\', '/', $entry->getPathname()), strlen($source) + 1);
            $target = $destination . '/' . $relative;
            if ($entry->isDir()) {
                if (!mkdir($target, 0700, true) && !is_dir($target)) {
                    throw new RuntimeException('Copy directory could not be created.');
                }
            } elseif (!copy($entry->getPathname(), $target)) {
                throw new RuntimeException('Copy file could not be created.');
            }
        }
    }

    private static function removeTree(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('Cleanup entry is invalid.');
            }
            $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($root);
    }
}
