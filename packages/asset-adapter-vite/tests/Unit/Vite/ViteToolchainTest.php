<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Vite;

use Kongono\AssetAdapterVite\Exception\InvalidViteToolchainException;
use Kongono\AssetAdapterVite\Vite\ViteToolchain;
use PHPUnit\Framework\TestCase;

final class ViteToolchainTest extends TestCase
{
    public function testExplicitLaunchFilesAuthenticateWithoutPathLookup(): void
    {
        $path = str_replace('\\', '/', PHP_BINARY);
        $hash = hash_file('sha256', $path);
        self::assertIsString($hash);
        $toolchain = new ViteToolchain($path, $path, '8.5.9', '1.2.3', $hash, $hash);
        $toolchain->authenticate();
        self::assertSame($path, $toolchain->nodePath());
        $this->expectException(InvalidViteToolchainException::class);
        (new ViteToolchain($path, $path, '8.5.9', '1.2.3', str_repeat('a', 64), $hash))->authenticate();
    }
}
