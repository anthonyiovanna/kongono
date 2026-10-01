<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Fixture;

use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;

final class RecordingHandler implements EventHandler
{
    /** @var list<ApplicationEvent> */
    public array $events = [];

    public function handle(ApplicationEvent $event): void
    {
        $this->events[] = $event;
    }
}
