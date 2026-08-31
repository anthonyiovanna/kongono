<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Derivation;

use Kongono\ExtensionInspection\Derivation\ExtensionIntelligenceDeriver;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExtensionIntelligenceDeriverTest extends TestCase
{
    #[DataProvider('roles')]
    public function testAllRolesReceiveTheSameAggregateEvidence(DeveloperIntelligenceRole $role): void
    {
        $view = (new ExtensionIntelligenceDeriver())->derive(Fixture::inspection(), $role);
        self::assertSame($role, $view->role());
        self::assertSame(0, $view->declarationCount());
    }

    /** @return iterable<string, array{DeveloperIntelligenceRole}> */
    public static function roles(): iterable
    {
        foreach (DeveloperIntelligenceRole::cases() as $role) {
            yield $role->value => [$role];
        }
    }
}
