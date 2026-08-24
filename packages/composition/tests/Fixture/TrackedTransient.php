<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class TrackedTransient
{
    public function __construct(private readonly LifecycleRecorder $recorder) {}

    public function close(): void
    {
        $this->recorder->record('transient-' . spl_object_id($this));
        $failure = $this->recorder->cleanupFailure('transient');
        if (null !== $failure) {
            throw $failure;
        }
    }
}
