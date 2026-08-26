<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Console;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/** Owns the complete disposable application and every byte written by the harness. */
final class TemporaryApplication
{
    private const string PREFIX = 'kongono phase8h-';

    private const string NAMESPACE = 'Phase8HExternalFixture';

    private const string SENTINEL = 'Phase8HPrivateSentinel-DoNotDisclose';

    private const string REJECTED = 'Phase8HRawRejectedValue-DoNotDisclose';

    private function __construct(
        private readonly string $root,
        private readonly string $repositoryRoot,
        private readonly string $sourceBytes,
        private readonly string $variantSourceBytes,
    ) {}

    public static function create(string $repositoryRoot): self
    {
        $repository = realpath($repositoryRoot);
        $temporary = realpath(sys_get_temp_dir());
        if (false === $repository || false === $temporary || is_link($repository) || is_link($temporary)) {
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
        $left = strtolower($repository . '/');
        $right = strtolower($root . '/');
        if (str_starts_with($left, $right) || str_starts_with($right, $left)) {
            throw new RuntimeException('temporary_repository_overlap');
        }

        $source = self::composition('Root');
        $variant = self::composition('RootVariant');
        $application = new self($root, $repository, $source, $variant);
        $application->populate();

        return $application;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function outputDirectory(): string
    {
        return $this->root . '/var/build';
    }

    public function preload(): string
    {
        return $this->root . '/preload.php';
    }

    public function namespaceMarker(): string
    {
        return self::NAMESPACE;
    }

    public function sentinel(): string
    {
        return self::SENTINEL;
    }

    public function rejectedMarker(): string
    {
        return self::REJECTED;
    }

    public function useInvalidSource(): void
    {
        $this->write($this->sourcePath(), '{"phase8_rejected":"' . self::REJECTED . '"}' . "\n");
    }

    public function useOriginalSource(): void
    {
        $this->write($this->sourcePath(), $this->sourceBytes);
    }

    public function useVariantSource(): void
    {
        $this->write($this->sourcePath(), $this->variantSourceBytes);
    }

    public function createSentinel(string $basename): string
    {
        $path = $this->outputPath($basename);
        if (file_exists($path) || is_link($path)) {
            throw new RuntimeException('sentinel_target_not_absent');
        }
        $this->write($path, self::SENTINEL);

        return $path;
    }

    public function removeOwnedFile(string $path): void
    {
        $this->requireContained($path);
        $this->read($path);
        if (!unlink($path)) {
            throw new RuntimeException('owned_file_remove_failed');
        }
    }

    public function outputPath(string $basename): string
    {
        if (basename($basename) !== $basename || '' === $basename) {
            throw new RuntimeException('output_basename_invalid');
        }

        return $this->outputDirectory() . '/' . $basename;
    }

    public function readOwned(string $path): string
    {
        $this->requireContained($path);

        return $this->read($path);
    }

    /** @return list<string> */
    public function outputFiles(): array
    {
        $files = [];
        $entries = scandir($this->outputDirectory());
        if (false === $entries) {
            throw new RuntimeException('output_scan_failed');
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $this->outputDirectory() . '/' . $entry;
            if (is_link($path) || !is_file($path)) {
                throw new RuntimeException('output_not_ordinary');
            }
            $files[] = $entry;
        }
        sort($files, SORT_STRING);

        return $files;
    }

    public function treeIdentity(): string
    {
        $records = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isLink()) {
                throw new RuntimeException('temporary_link_detected');
            }
            if (!$entry->isFile()) {
                continue;
            }
            $relative = self::normalize(substr($entry->getPathname(), strlen($this->root) + 1));
            $stat = @stat($entry->getPathname());
            if (false === $stat) {
                throw new RuntimeException('temporary_stat_failed');
            }
            $bytes = $this->read($entry->getPathname());
            $records[] = implode("\0", [$relative, strlen($bytes), hash('sha256', $bytes), $stat['mtime'], $stat['ctime']]);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    public function outputIdentity(): string
    {
        $records = [];
        foreach ($this->outputFiles() as $file) {
            $path = $this->outputPath($file);
            $stat = @stat($path);
            if (false === $stat) {
                throw new RuntimeException('output_stat_failed');
            }
            $bytes = $this->read($path);
            $records[] = implode("\0", [$file, strlen($bytes), hash('sha256', $bytes), $stat['mtime'], $stat['ctime']]);
        }

        return hash('sha256', implode("\n", $records));
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
            if ($entry->isLink()) {
                throw new RuntimeException('temporary_cleanup_link');
            }
            $path = $entry->getPathname();
            if ($entry->isDir()) {
                @chmod($path, 0700);
                $this->removeWithRetry($path, true);
            } elseif ($entry->isFile()) {
                @chmod($path, 0600);
                $this->removeWithRetry($path, false);
            } else {
                throw new RuntimeException('temporary_cleanup_special');
            }
        }
        @chmod($resolved, 0700);
        $this->removeWithRetry($resolved, true);
    }

    private function populate(): void
    {
        $this->directory($this->root . '/src');
        $this->directory($this->outputDirectory());
        $this->write($this->root . '/src/Input.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace " . self::NAMESPACE . ";\n\nfinal readonly class Input {}\n");
        $this->write($this->root . '/src/Root.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace " . self::NAMESPACE . ";\n\nfinal readonly class Root { public function __construct(public Input \$input) {} }\n");
        $this->write($this->root . '/src/RootVariant.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace " . self::NAMESPACE . ";\n\nfinal readonly class RootVariant { public function __construct(public Input \$input) {} }\n");
        $this->write($this->root . '/composer.json', "{\"name\":\"example/phase8h-external\"}\n");
        $this->write($this->root . '/composer.lock', "{\"packages\":[]}\n");
        $this->write($this->sourcePath(), $this->sourceBytes);
        $autoload = $this->repositoryRoot . '/vendor/autoload.php';
        $quoted = var_export($autoload, true);
        $this->write(
            $this->preload(),
            "<?php\n\ndeclare(strict_types=1);\n\n\$GLOBALS['_composer_autoload_path'] = {$quoted};\nrequire {$quoted};\nrequire __DIR__ . '/src/Input.php';\nrequire __DIR__ . '/src/Root.php';\nrequire __DIR__ . '/src/RootVariant.php';\n",
        );
    }

    private static function composition(string $root): string
    {
        return '{"schema":"kongono.composition","version":1,"inputs":[{"contract":"' . self::NAMESPACE . '\\\\Input"}],"services":[{"class":"' . self::NAMESPACE . '\\\\' . $root . '","lifetime":"application","arguments":[{"parameter":"input","input":"' . self::NAMESPACE . '\\\\Input"}],"cleanup":null}],"bindings":[],"roots":[{"kind":"application","contract":"' . self::NAMESPACE . '\\\\' . $root . '"}]}' . "\n";
    }

    private function sourcePath(): string
    {
        return $this->root . '/kongono.composition.json';
    }

    private function directory(string $path): void
    {
        $this->requireContained($path);
        if (is_dir($path)) {
            return;
        }
        if (file_exists($path) || is_link($path) || !mkdir($path, 0700, true)) {
            throw new RuntimeException('temporary_directory_failed');
        }
    }

    private function write(string $path, string $bytes): void
    {
        $this->requireContained($path);
        $written = file_put_contents($path, $bytes, LOCK_EX);
        if (strlen($bytes) !== $written) {
            throw new RuntimeException('temporary_write_failed');
        }
    }

    private function read(string $path): string
    {
        $stat = @lstat($path);
        if (false === $stat || is_link($path) || !is_file($path) || 0100000 !== ($stat['mode'] & 0170000)) {
            throw new RuntimeException('ordinary_file_required');
        }
        $bytes = file_get_contents($path);
        if (false === $bytes || strlen($bytes) !== $stat['size']) {
            throw new RuntimeException('ordinary_file_read_failed');
        }

        return $bytes;
    }

    private function requireContained(string $path): void
    {
        $normalized = self::normalize($path);
        if (!str_starts_with($normalized, $this->root . '/')) {
            throw new RuntimeException('temporary_path_escape');
        }
    }

    private function removeWithRetry(string $path, bool $directory): void
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
