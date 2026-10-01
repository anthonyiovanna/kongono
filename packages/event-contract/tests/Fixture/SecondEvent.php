<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Fixture;

use Kongono\EventContract\ApplicationEvent;

final class SecondEvent implements ApplicationEvent
{
    public static int $constructorCalls = 0;

    private function __construct()
    {
        ++self::$constructorCalls;
    }
}
