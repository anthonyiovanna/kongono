<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Inspection;

use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection;
use PHPUnit\Framework\TestCase;

final class ExtensionPlatformInspectionTest extends TestCase
{
    public function testEmptyAggregateIsTotalAndHonest(): void
    {
        $inspection = new ExtensionPlatformInspection(str_repeat('1', 64), new DeclarationSetIdentity(str_repeat('2', 64)), str_repeat('3', 64), str_repeat('4', 64), str_repeat('5', 64), ContractVersion::Version1, 0, 0, 0, 0, 0, ['core' => 0, 'trusted_in_process' => 0, 'sandboxed' => 0, 'external' => 0], ['application' => 0, 'operation' => 0], 0, 0, 0, 0, ['public' => 0, 'application' => 0, 'framework_internal' => 0]);
        self::assertSame('not-established', $inspection->currentness());
        self::assertSame('not-sandboxed', $inspection->trustedCodeIsolation());
    }
}
