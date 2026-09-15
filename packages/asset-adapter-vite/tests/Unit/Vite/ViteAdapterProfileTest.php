<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Vite;

use Kongono\AssetAdapterVite\Vite\ViteAdapterProfile;
use PHPUnit\Framework\TestCase;

final class ViteAdapterProfileTest extends TestCase
{
    public function testProfileIsClosed(): void
    {
        self::assertSame(['vite-client-es2022/1'], array_column(ViteAdapterProfile::cases(), 'value'));
    }
}
