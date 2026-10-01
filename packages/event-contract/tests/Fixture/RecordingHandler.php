<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Fixture;

use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;

final class RecordingHandler implements EventHandler
{
    public int $calls = 0;

    public function handle(ApplicationEvent $event): void
    {
        ++$this->calls;
    }
}
