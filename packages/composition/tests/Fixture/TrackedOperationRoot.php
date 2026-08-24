<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final class TrackedOperationRoot
{
    public function __construct(
        private readonly LifecycleRecorder $recorder,
        private readonly TrackedApplicationRoot $application,
        private readonly TrackedOperationLeaf $firstOperationLeaf,
        private readonly TrackedOperationLeaf $secondOperationLeaf,
        private readonly TrackedTransient $firstTransient,
        private readonly TrackedTransient $secondTransient,
    ) {}

    /** @return array{TrackedApplicationRoot, TrackedOperationLeaf, TrackedOperationLeaf, TrackedTransient, TrackedTransient} */
    public function dependencies(): array
    {
        return [$this->application, $this->firstOperationLeaf, $this->secondOperationLeaf, $this->firstTransient, $this->secondTransient];
    }

    public function close(): void
    {
        $this->recorder->record('operation-root');
        $failure = $this->recorder->cleanupFailure('operation-root');
        if (null !== $failure) {
            throw $failure;
        }
    }
}
