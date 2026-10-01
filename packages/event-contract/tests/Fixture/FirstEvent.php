<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Fixture;

use Kongono\EventContract\ApplicationEvent;

class FirstEvent implements ApplicationEvent
{
    use EventTrait;
}
