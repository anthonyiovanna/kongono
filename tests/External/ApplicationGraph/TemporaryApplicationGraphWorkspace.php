<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ApplicationGraph;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/** Owns one isolated application-graph acceptance workspace and all of its residue. */
final class TemporaryApplicationGraphWorkspace
{
    private const string PREFIX = 'kongono phase11 graph-';

    private function __construct(
        private readonly string $root,
    ) {}

    public static function create(string $repositoryRoot): self
    {
        $repository = realpath($repositoryRoot);
        $temporary = realpath(sys_get_temp_dir());
        if (false === $repository || false === $temporary || self::linkLike($repository) || self::linkLike($temporary)) {
            throw new RuntimeException('temporary_authority_unavailable');
        }

        $repository = self::normalize($repository);
        $temporary = rtrim(self::normalize($temporary), '/');
        $root = $temporary . '/' . self::PREFIX . bin2hex(random_bytes(16));
        if (file_exists($root) || is_link($root) || !mkdir($root, 0700)) {
            throw new RuntimeException('temporary_create_failed');
        }
        $resolved = realpath($root);
        if (false === $resolved || self::normalize($resolved) !== $root || !str_contains(basename($root), ' ')) {
            throw new RuntimeException('temporary_identity_invalid');
        }
        if (str_starts_with(strtolower($repository . '/'), strtolower($root . '/')) || str_starts_with(strtolower($root . '/'), strtolower($repository . '/'))) {
            throw new RuntimeException('temporary_repository_overlap');
        }

        $workspace = new self($root);
        $workspace->populate();

        return $workspace;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function applicationRoot(): string
    {
        return $this->root . '/application';
    }

    public function outputDirectory(): string
    {
        return $this->root . '/output';
    }

    public function path(string $relative): string
    {
        if ('' === $relative || str_starts_with($relative, '/') || str_contains($relative, '\\') || str_contains('/' . $relative . '/', '/../')) {
            throw new RuntimeException('temporary_relative_path_invalid');
        }

        return $this->root . '/' . $relative;
    }

    public function compositionPath(): string
    {
        return $this->applicationRoot() . '/kongono.composition.json';
    }

    /** @return list<string> */
    public function fixtureFiles(): array
    {
        return [
            $this->applicationRoot() . '/src/ConfigInput.php',
            $this->applicationRoot() . '/src/Contract.php',
            $this->applicationRoot() . '/src/Child.php',
            $this->applicationRoot() . '/src/Phase11PrivateFixtureDoNotDiscloseRoot.php',
        ];
    }

    public function writeComposition(bool $reverseServices = false): void
    {
        $services = [
            [
                'class' => 'KongonoPhase11Fixture\\Child',
                'lifetime' => 'application',
                'arguments' => [],
                'cleanup' => null,
            ],
            [
                'class' => 'KongonoPhase11Fixture\\Phase11PrivateFixtureDoNotDiscloseRoot',
                'lifetime' => 'application',
                'arguments' => [
                    ['parameter' => 'input', 'input' => 'KongonoPhase11Fixture\\ConfigInput'],
                    ['parameter' => 'child', 'service' => 'KongonoPhase11Fixture\\Contract'],
                ],
                'cleanup' => null,
            ],
        ];
        if ($reverseServices) {
            $services = array_reverse($services);
        }
        $source = [
            'schema' => 'kongono.composition',
            'version' => 2,
            'inputs' => [['contract' => 'KongonoPhase11Fixture\\ConfigInput', 'lifetime' => 'application']],
            'services' => $services,
            'bindings' => [['contract' => 'KongonoPhase11Fixture\\Contract', 'service' => 'KongonoPhase11Fixture\\Child']],
            'roots' => [['kind' => 'application', 'contract' => 'KongonoPhase11Fixture\\Phase11PrivateFixtureDoNotDiscloseRoot']],
        ];
        $this->write($this->compositionPath(), json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
    }

    public function read(string $path): string
    {
        $this->requireContained($path);

        return self::readOrdinary($path);
    }

    public function write(string $path, string $bytes): void
    {
        $this->requireContained($path);
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0700, true)) {
            throw new RuntimeException('temporary_parent_create_failed');
        }
        if (self::linkLike($parent)) {
            throw new RuntimeException('temporary_parent_link');
        }
        if (strlen($bytes) !== file_put_contents($path, $bytes, LOCK_EX)) {
            throw new RuntimeException('temporary_write_failed');
        }
    }

    public function unlink(string $path): void
    {
        $this->requireContained($path);
        self::readOrdinary($path);
        if (!unlink($path)) {
            throw new RuntimeException('temporary_unlink_failed');
        }
    }

    /** @return list<string> */
    public function outputFiles(): array
    {
        $entries = scandir($this->outputDirectory());
        if (false === $entries) {
            throw new RuntimeException('temporary_output_scan_failed');
        }
        $files = [];
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            self::readOrdinary($this->outputDirectory() . '/' . $entry);
            $files[] = $entry;
        }
        sort($files, SORT_STRING);

        return $files;
    }

    public function treeIdentity(): string
    {
        return self::directoryIdentity($this->root);
    }

    public function cleanup(): void
    {
        $resolved = realpath($this->root);
        $temporary = realpath(sys_get_temp_dir());
        if (false === $resolved || false === $temporary) {
            throw new RuntimeException('temporary_cleanup_identity_failed');
        }
        $resolved = self::normalize($resolved);
        $temporary = rtrim(self::normalize($temporary), '/');
        if (dirname($resolved) !== $temporary || !str_starts_with(basename($resolved), self::PREFIX)) {
            throw new RuntimeException('temporary_cleanup_scope_invalid');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $path = self::normalize($entry->getPathname());
            if (self::linkLike($path)) {
                throw new RuntimeException('temporary_cleanup_link');
            }
            if ($entry->isDir()) {
                @chmod($path, 0700);
                self::removeWithRetry($path, true);
            } elseif ($entry->isFile()) {
                @chmod($path, 0600);
                self::removeWithRetry($path, false);
            } else {
                throw new RuntimeException('temporary_cleanup_special');
            }
        }
        @chmod($resolved, 0700);
        self::removeWithRetry($resolved, true);
    }

    private function populate(): void
    {
        if (!mkdir($this->applicationRoot() . '/src', 0700, true) || !mkdir($this->outputDirectory(), 0700, true)) {
            throw new RuntimeException('temporary_layout_create_failed');
        }
        $this->write($this->applicationRoot() . '/composer.json', <<<'JSON'
{"name":"kongono/phase11-acceptance-fixture","autoload":{"psr-4":{"KongonoPhase11Fixture\\":"src/"}}}
JSON
            . "\n");
        $this->write($this->applicationRoot() . '/composer.lock', "{\"packages\":[],\"packages-dev\":[]}\n");
        $this->write($this->applicationRoot() . '/src/ConfigInput.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace KongonoPhase11Fixture;

interface ConfigInput {}
PHP
            . "\n");
        $this->write($this->applicationRoot() . '/src/Contract.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace KongonoPhase11Fixture;

interface Contract {}
PHP
            . "\n");
        $this->write($this->applicationRoot() . '/src/Child.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace KongonoPhase11Fixture;

final readonly class Child implements Contract {}
PHP
            . "\n");
        $this->write($this->applicationRoot() . '/src/Phase11PrivateFixtureDoNotDiscloseRoot.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace KongonoPhase11Fixture;

final readonly class Phase11PrivateFixtureDoNotDiscloseRoot
{
    public function __construct(
        public ConfigInput $input,
        public Contract $child,
    ) {}
}
PHP
            . "\n");
        $this->writeComposition();
    }

    private function requireContained(string $path): void
    {
        if (!str_starts_with(self::normalize($path), $this->root . '/')) {
            throw new RuntimeException('temporary_path_escape');
        }
    }

    private static function directoryIdentity(string $root): string
    {
        $resolved = realpath($root);
        if (false === $resolved || self::linkLike($root) || !is_dir($resolved)) {
            throw new RuntimeException('identity_root_invalid');
        }
        $resolved = rtrim(self::normalize($resolved), '/');
        $records = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $path = self::normalize($entry->getPathname());
            if (self::linkLike($path)) {
                throw new RuntimeException('identity_link_rejected');
            }
            if (!$entry->isFile()) {
                continue;
            }
            $bytes = self::readOrdinary($path);
            $records[] = substr($path, strlen($resolved) + 1) . "\0" . strlen($bytes) . "\0" . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    private static function readOrdinary(string $path): string
    {
        $stat = @lstat($path);
        if (false === $stat || self::linkLike($path) || !is_file($path) || 0100000 !== ($stat['mode'] & 0170000)) {
            throw new RuntimeException('ordinary_file_required');
        }
        $bytes = file_get_contents($path);
        if (false === $bytes || strlen($bytes) !== $stat['size']) {
            throw new RuntimeException('ordinary_file_read_failed');
        }

        return $bytes;
    }

    private static function linkLike(string $path): bool
    {
        if (is_link($path)) {
            return true;
        }
        $target = @readlink($path);

        return false !== $target && strtolower(self::normalize($target)) !== strtolower(self::normalize($path));
    }

    private static function removeWithRetry(string $path, bool $directory): void
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            if (($directory ? @rmdir($path) : @unlink($path)) || !file_exists($path) && !is_link($path)) {
                return;
            }
            usleep(20_000);
        }
        throw new RuntimeException('temporary_cleanup_failed');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
