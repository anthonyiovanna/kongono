<?php

declare(strict_types=1);

namespace StorageMinimalApp;

use Kongono\StorageApplication\Definition\StorageApplicationDefinition;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageApplication\Workflow\PlannedStorageApplicationBuild;
use Kongono\StorageApplication\Workflow\StorageApplicationWorkflow;

/** Application-owned composition of one explicit profile workflow. */
final readonly class StorageWorkflow
{
    private StorageApplicationDefinition $definition;

    public function __construct(string $applicationRoot, string $sourceFile, string $outputDirectory)
    {
        $this->definition = new StorageApplicationDefinition($applicationRoot, $sourceFile, $outputDirectory);
    }

    public function plan(): PlannedStorageApplicationBuild
    {
        return (new StorageApplicationWorkflow())->plan($this->definition);
    }

    public function build(): CurrentStorageApplicationBuild
    {
        return (new StorageApplicationWorkflow())->build($this->definition);
    }

    public function check(): CurrentStorageApplicationBuild
    {
        return (new StorageApplicationWorkflow())->check($this->definition);
    }
}
