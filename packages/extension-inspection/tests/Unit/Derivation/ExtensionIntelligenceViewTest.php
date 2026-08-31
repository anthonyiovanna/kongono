<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Derivation;

use Kongono\ExtensionInspection\Derivation\ExtensionIntelligenceDeriver;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;
use PHPUnit\Framework\TestCase;

final class ExtensionIntelligenceViewTest extends TestCase
{
    public function testSerializationContainsOnlyAggregateEvidence(): void
    {
        $view = (new ExtensionIntelligenceDeriver())->derive(Fixture::inspection(), DeveloperIntelligenceRole::Chie);
        self::assertSame('kongono.extension-intelligence-view', $view->toArray()['schema']);
        self::assertArrayNotHasKey('path', $view->toArray());
    }
}

final class Fixture
{
    public static function inspection(): \Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection
    {
        return new \Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection(str_repeat('1', 64), new \Kongono\ExtensionBuild\Identity\DeclarationSetIdentity(str_repeat('2', 64)), str_repeat('3', 64), str_repeat('4', 64), str_repeat('5', 64), \Kongono\ExtensionContract\ContractVersion::Version1, 0, 0, 0, 0, 0, ['core' => 0, 'trusted_in_process' => 0, 'sandboxed' => 0, 'external' => 0], ['application' => 0, 'operation' => 0], 0, 0, 0, 0, ['public' => 0, 'application' => 0, 'framework_internal' => 0]);
    }
}
