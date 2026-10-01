<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Fixture;

use Kongono\EventContract\ApplicationEvent;

enum EventEnum implements ApplicationEvent
{
    case Example;
}
