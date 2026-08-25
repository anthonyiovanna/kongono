<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

final class AccessorLog
{
    /** @var array<string, int> */
    private static array $counts = [];

    public static function reset(): void
    {
        self::$counts = [];
    }

    public static function record(string $accessor): void
    {
        self::$counts[$accessor] = (self::$counts[$accessor] ?? 0) + 1;
    }

    /** @return array<string, int> */
    public static function counts(): array
    {
        return self::$counts;
    }

    private function __construct() {}
}
