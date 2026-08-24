<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

use RuntimeException;

final class FailingOperationRoot
{
    public function __construct(LifecycleRecorder $recorder, private readonly TrackedOperationLeaf $leaf)
    {
        throw $recorder->constructionFailure() ?? new RuntimeException('construction failed');
    }

    public function leaf(): TrackedOperationLeaf
    {
        return $this->leaf;
    }
}
