<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Exception;

use Kongono\AssetAdapterVite\Exception\ExceptionInterface;
use Kongono\AssetAdapterVite\Exception\ViteBuildExecutionException;
use PHPUnit\Framework\TestCase;

final class ViteAdapterExceptionTest extends TestCase
{
    public function testExceptionFamilyIsActionableAndClosed(): void
    {
        $failure = ViteBuildExecutionException::failed('process_nonzero');
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-ASSET-VITE-1928', $failure->diagnosticId());
    }
}
