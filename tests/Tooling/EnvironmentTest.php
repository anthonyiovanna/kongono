<?php

declare(strict_types=1);

namespace Kongono\Tests\Tooling;

use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function testRuntimeMeetsKongonoMinimumPhpVersion(): void
    {
        self::assertGreaterThanOrEqual(80500, PHP_VERSION_ID);
    }
}
