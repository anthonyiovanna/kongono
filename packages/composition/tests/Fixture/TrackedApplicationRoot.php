<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class TrackedApplicationRoot
{
    public function __construct(
        private readonly LifecycleRecorder $recorder,
        private readonly TrackedApplicationLeaf $leaf,
    ) {}

    public function leaf(): TrackedApplicationLeaf
    {
        return $this->leaf;
    }

    public function close(): void
    {
        $this->recorder->record('application-root');
        $failure = $this->recorder->cleanupFailure('application-root');
        if (null !== $failure) {
            throw $failure;
        }
    }
}
