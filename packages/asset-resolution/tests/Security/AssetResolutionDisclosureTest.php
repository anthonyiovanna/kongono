<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Security;

use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssetResolutionDisclosureTest extends TestCase
{
    public function testPreviousMessageIsNeverPresented(): void
    {
        $secret = 'C:\\private\\workspace?token=credential';
        $exception = InvalidAssetResolutionException::invalidArtifact($secret, previous: new RuntimeException($secret));
        self::assertSame('unsupported_violation', $exception->diagnosticContext()->fields()[0]->value());
        self::assertStringNotContainsString($secret, $exception->getMessage());
        self::assertStringNotContainsString($secret, (string) $exception);
        self::assertSame($secret, $exception->getPrevious()?->getMessage());
    }
}
