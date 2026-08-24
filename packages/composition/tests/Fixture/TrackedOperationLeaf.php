<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class TrackedOperationLeaf
{
    public function __construct(private readonly LifecycleRecorder $recorder) {}

    public function close(): void
    {
        $this->recorder->record('operation-leaf');
        $failure = $this->recorder->cleanupFailure('operation-leaf');
        if (null !== $failure) {
            throw $failure;
        }
    }
}
