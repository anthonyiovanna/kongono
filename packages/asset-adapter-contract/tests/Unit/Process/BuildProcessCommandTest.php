<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Process;

use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use PHPUnit\Framework\TestCase;

final class BuildProcessCommandTest extends TestCase
{
    public function testCommandIsArgumentVectorWithCanonicalEnvironment(): void
    {
        $command = new BuildProcessCommand(['php','-v'], 'C:/work', ['Z' => '1','A' => '2'], 1, 2, 3);
        self::assertSame(['php','-v'], $command->arguments());
        self::assertSame(['A' => '2','Z' => '1'], $command->environment());
        $this->expectException(InvalidAssetAdapterContractException::class);
        new BuildProcessCommand(['php' . "\0"], 'C:/work', [], 1, 0, 0);
    }
}
