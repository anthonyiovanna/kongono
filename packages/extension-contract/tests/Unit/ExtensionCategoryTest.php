<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit;

use Kongono\ExtensionContract\ExtensionCategory;
use PHPUnit\Framework\TestCase;

final class ExtensionCategoryTest extends TestCase
{
    public function testArchitecturalCategoriesAreExactAndInert(): void
    {
        self::assertSame([
            ['Core', 'core'],
            ['TrustedInProcess', 'trusted_in_process'],
            ['Sandboxed', 'sandboxed'],
            ['External', 'external'],
        ], array_map(static fn(ExtensionCategory $case): array => [$case->name, $case->value], ExtensionCategory::cases()));
    }
}
