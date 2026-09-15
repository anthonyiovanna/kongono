<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Fixture;

use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterContract\Process\BuildProcessResult;
use Kongono\AssetAdapterContract\Process\BuildProcessRunner;

final class FakeBuildProcessRunner implements BuildProcessRunner
{
    public int $calls = 0;
    public ?BuildProcessCommand $lastCommand = null;
    /** @param array<string,string> $files */
    public function __construct(private readonly string $manifest = '{}', private readonly array $files = [], private readonly ?BuildProcessResult $result = null) {}
    public function run(BuildProcessCommand $command): BuildProcessResult
    {
        $this->calls++;
        $this->lastCommand = $command;
        if (null !== $this->result) {
            return$this->result;
        }$output = $command->workingDirectory() . '/output';
        foreach (['kongono-vite-manifest.json' => $this->manifest,...$this->files] as $relative => $bytes) {
            $path = $output . '/' . $relative;
            $parent = dirname($path);
            if (!is_dir($parent)) {
                mkdir($parent, 0700, true);
            }file_put_contents($path, $bytes);
        }return new BuildProcessResult(true, 0, '', '', false, false, false, false, true, true, 'none');
    }
}
