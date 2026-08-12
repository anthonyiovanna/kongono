<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Fixture;

use RuntimeException;

final class ThrowingService implements ThrowingServiceInterface
{
    public static int $attempts = 0;

    public function __construct()
    {
        ++self::$attempts;

        throw new RuntimeException('consumer constructor failure');
    }
}
