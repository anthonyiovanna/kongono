<?php

declare(strict_types=1);

namespace Kongono\Tests\Fixture\Phase8G;

final readonly class WorkflowRoot
{
    public function __construct(public WorkflowInput $input) {}
}
