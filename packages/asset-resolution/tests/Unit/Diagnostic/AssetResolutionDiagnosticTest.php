<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Diagnostic;

use Kongono\AssetResolution\Diagnostic\AssetResolutionDiagnostic;
use PHPUnit\Framework\TestCase;

final class AssetResolutionDiagnosticTest extends TestCase
{
    public function testNineDiagnosticIdsAreUniqueAndContiguous(): void
    {
        $ids = array_map(static fn(AssetResolutionDiagnostic $case): string => $case->value, AssetResolutionDiagnostic::cases());
        self::assertSame(array_map(static fn(int $id): string => 'KGN-ASSET-RESOLUTION-' . $id, range(1930, 1938)), $ids);
        self::assertSame($ids, array_values(array_unique($ids)));
    }
}
