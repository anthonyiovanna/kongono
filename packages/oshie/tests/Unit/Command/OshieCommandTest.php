<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit\Command;

use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Oshie\Command\OshieCommand;
use PHPUnit\Framework\TestCase;

final class OshieCommandTest extends TestCase
{
    public function testAllSuccessfulAndIncorrectStatesUseOnlyStdoutAndExactExits(): void
    {
        [$directory, $path] = $this->artifactFile((new FrameworkKnowledgeArtifactGenerator())->generate($this->root()));
        try {
            $cases = [
                [['catalog', $this->root(), $path], 0, 'available'],
                [['lesson', $this->root(), $path, 'role-separation'], 0, 'taught'],
                [['exercise', $this->root(), $path, 'role-verifier'], 0, 'practice'],
                [['check', $this->root(), $path, 'role-verifier', 'hantei'], 0, 'correct'],
                [['check', $this->root(), $path, 'role-verifier', 'chie'], 1, 'incorrect'],
            ];
            foreach ($cases as [$arguments, $expectedExit, $status]) {
                [$stdout, $stderr] = $this->streams();
                $exit = (new OshieCommand())->run($arguments, $stdout, $stderr);
                $output = $this->contents($stdout);
                self::assertSame($expectedExit, $exit);
                self::assertSame('', $this->contents($stderr));
                self::assertSame($status, $this->decode($output)['status']);
                self::assertStringEndsWith("\n", $output);
                self::assertStringNotContainsString("\r", $output);
            }
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testUnsupportedUnavailableAndInvalidUsageSelectExactStreams(): void
    {
        [$directory, $path] = $this->artifactFile((new FrameworkKnowledgeArtifactGenerator())->generate($this->root()));
        try {
            [$stdout, $stderr] = $this->streams();
            self::assertSame(65, (new OshieCommand())->run(['lesson', $this->root(), $path, 'unknown-lesson'], $stdout, $stderr));
            self::assertSame('unsupported', $this->decode($this->contents($stdout))['status']);
            self::assertSame('', $this->contents($stderr));

            [$stdout, $stderr] = $this->streams();
            $missing = $directory . '/kongono-framework-knowledge-v1-' . str_repeat('0', 64) . '.json';
            self::assertSame(66, (new OshieCommand())->run(['catalog', $this->root(), $missing], $stdout, $stderr));
            self::assertSame('unavailable', $this->decode($this->contents($stdout))['status']);
            self::assertStringNotContainsString($missing, $this->contents($stdout));

            [$stdout, $stderr] = $this->streams();
            $rejected = 'Secret Answer';
            self::assertSame(64, (new OshieCommand())->run(['check', $this->root(), $path, 'role-verifier', $rejected], $stdout, $stderr));
            self::assertSame('', $this->contents($stdout));
            self::assertStringContainsString('KGN-OSHIE-1001', $this->contents($stderr));
            self::assertStringNotContainsString($rejected, $this->contents($stderr));
        } finally {
            $this->removeTree($directory);
        }
    }

    /** @return array{string, string} */
    private function artifactFile(FrameworkKnowledgeArtifact $artifact): array
    {
        $directory = str_replace('\\', '/', sys_get_temp_dir() . '/kongono-oshie-command-' . bin2hex(random_bytes(8)));
        mkdir($directory, 0777, true);
        $path = $directory . '/kongono-framework-knowledge-v1-' . $artifact->artifactSha256() . '.json';
        file_put_contents($path, $artifact->canonicalBytes());
        return [$directory, $path];
    }

    /** @return array{resource, resource} */
    private function streams(): array
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);
        return [$stdout, $stderr];
    }

    /** @param resource $stream */ private function contents($stream): string
    {
        rewind($stream);
        return (string) stream_get_contents($stream);
    }
    /** @return array<string, mixed> */
    private function decode(string $bytes): array
    {
        $value = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($value);
        self::assertFalse(array_is_list($value));
        $object = [];
        foreach ($value as $key => $item) {
            self::assertIsString($key);
            $object[$key] = $item;
        }
        return $object;
    }
    private function root(): string
    {
        return dirname(__DIR__, 5);
    }
    private function removeTree(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }
        rmdir($root);
    }
}
