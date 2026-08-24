<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Fixture;

final readonly class WorkflowRoot
{
    public function __construct(public WorkflowInput $input) {}
}
