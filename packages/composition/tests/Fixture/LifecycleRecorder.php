<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

use Throwable;

final class LifecycleRecorder
{
    /** @var list<string> */
    private array $events = [];
    /** @var array<string, Throwable> */
    private array $failures = [];
    private ?Throwable $constructionFailure = null;

    public function record(string $event): void
    {
        $this->events[] = $event;
    }

    /** @return list<string> */
    public function events(): array
    {
        return [...$this->events];
    }

    public function eventLog(): string
    {
        return implode("\n", $this->events);
    }

    public function failCleanup(string $event, Throwable $failure): void
    {
        $this->failures[$event] = $failure;
    }

    public function cleanupFailure(string $event): ?Throwable
    {
        return $this->failures[$event] ?? null;
    }

    public function failConstruction(Throwable $failure): void
    {
        $this->constructionFailure = $failure;
    }

    public function constructionFailure(): ?Throwable
    {
        return $this->constructionFailure;
    }
}
