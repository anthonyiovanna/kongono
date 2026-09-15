<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Exception;

use Kongono\AssetContract\Exception\ExceptionInterface;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class AssetContractExceptionTest extends TestCase
{
    public function testHierarchyPreviousThrowableAndSafeFallbackAreNondisclosing(): void
    {
        $previous = new RuntimeException('private-causal-secret');
        $failure = InvalidAssetContractException::invalidIdentity('private-identity-secret', $previous);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertInstanceOf(Throwable::class, $failure);
        self::assertInstanceOf(RuntimeException::class, $failure);
        self::assertSame($previous, $failure->getPrevious());
        self::assertSame('KGN-ASSET-CONTRACT-1901', $failure->diagnosticId());
        self::assertSame('unsupported_violation', $failure->diagnosticContext()->fields()[0]->value());
        foreach (['private-identity-secret', 'private-causal-secret', 'C:\\private\\asset.js', 'https://private.example/asset'] as $private) {
            self::assertStringNotContainsString($private, $failure->getMessage());
            self::assertStringNotContainsString($private, (string) $failure);
            foreach ($failure->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($private, (string) $field->value());
            }
        }
    }

    public function testClosedContextTokensAndRepeatedFailuresAreDeterministic(): void
    {
        $first = InvalidAssetContractException::invalidDeliveryMetadata('module_preload_kind_relation', assetKind: 'style', algorithm: 'sha256', preloadHint: 'module_preload');
        $second = InvalidAssetContractException::invalidDeliveryMetadata('module_preload_kind_relation', assetKind: 'style', algorithm: 'sha256', preloadHint: 'module_preload');
        self::assertSame($first->getMessage(), $second->getMessage());
        self::assertSame((string) $first, (string) $second);
        self::assertNotSame($first, $second);
    }
}
