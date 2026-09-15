<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Identity;

use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use PHPUnit\Framework\TestCase;

final class ToolchainIdentityTest extends TestCase
{
    public function testIdentityUsesExactEvidenceBytes(): void
    {
        self::assertSame(hash('sha256', 'evidence'), ToolchainIdentity::fromEvidence('evidence')->value());
    }
}
