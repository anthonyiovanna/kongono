<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Security;

use Kongono\AssetAdapterContract\Exception\InvalidAdapterBuildArtifactException;
use PHPUnit\Framework\TestCase;

final class AssetAdapterContractDisclosureTest extends TestCase
{
    public function testPresentationDoesNotDisclosePreviousMessageOrRawEvidence(): void
    {
        $secret = 'raw-source-secret';
        $failure = InvalidAdapterBuildArtifactException::invalid($secret, new \RuntimeException($secret), hash('sha256', $secret));
        self::assertStringNotContainsString($secret, $failure->getMessage());
        self::assertStringNotContainsString($secret, (string) $failure);
        self::assertSame($secret, $failure->getPrevious()?->getMessage());
    }
}
