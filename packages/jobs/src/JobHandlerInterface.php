<?php

declare(strict_types=1);

namespace Kongono\Jobs;

interface JobHandlerInterface
{
    public function handle(Job $job): JobOutcome;
}
