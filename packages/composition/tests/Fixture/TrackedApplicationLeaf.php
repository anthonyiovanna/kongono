<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class TrackedApplicationLeaf
{
    public function __construct(private readonly LifecycleRecorder $recorder) {}

    public function close(): void
    {
        $this->recorder->record('application-leaf');
        $failure = $this->recorder->cleanupFailure('application-leaf');
        if (null !== $failure) {
            throw $failure;
        }
    }
}
