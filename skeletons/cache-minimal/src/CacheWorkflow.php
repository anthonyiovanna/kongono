<?php

declare(strict_types=1);

namespace CacheMinimalApp;

use Kongono\CacheApplication\Definition\CacheApplicationDefinition;
use Kongono\CacheApplication\Workflow\CacheApplicationWorkflow;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheApplication\Workflow\PlannedCacheApplicationBuild;

/** Application-owned composition of one explicit cache-profile workflow. */
final readonly class CacheWorkflow
{
    private CacheApplicationDefinition $definition;

    public function __construct(string $applicationRoot)
    {
        $this->definition = new CacheApplicationDefinition($applicationRoot, 'kongono.cache.json', $applicationRoot . '/var/cache');
    }

    public function plan(): PlannedCacheApplicationBuild
    {
        return (new CacheApplicationWorkflow())->plan($this->definition);
    }
    public function build(): CurrentCacheApplicationBuild
    {
        return (new CacheApplicationWorkflow())->build($this->definition);
    }
    public function check(): CurrentCacheApplicationBuild
    {
        return (new CacheApplicationWorkflow())->check($this->definition);
    }
}
