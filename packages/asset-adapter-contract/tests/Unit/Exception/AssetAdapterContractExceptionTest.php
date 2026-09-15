<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Exception;

use Kongono\AssetAdapterContract\Exception\ExceptionInterface;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use PHPUnit\Framework\TestCase;

final class AssetAdapterContractExceptionTest extends TestCase
{
    public function testExceptionIsActionableAndClosed(): void
    {
        $failure = InvalidAssetAdapterContractException::request('unknown-secret');
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-ASSET-ADAPTER-1921', $failure->diagnosticId());
        self::assertStringNotContainsString('unknown-secret', (string) $failure);
    }
}
