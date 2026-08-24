<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

use RuntimeException;

final class FailingApplicationRoot
{
    public function __construct(LifecycleRecorder $recorder, private readonly TrackedApplicationLeaf $leaf)
    {
        throw $recorder->constructionFailure() ?? new RuntimeException('construction failed');
    }

    public function leaf(): TrackedApplicationLeaf
    {
        return $this->leaf;
    }
}
