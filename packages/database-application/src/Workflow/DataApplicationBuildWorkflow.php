<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\DatabaseApplication\Exception\DataApplicationWorkflowException;
use Throwable;

/** Builds or non-mutatingly checks mapping output before accepted composition output. */
#[Api(ApiStatus::Experimental)]
final class DataApplicationBuildWorkflow
{
    public function build(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $outputDirectory,
    ): CurrentDataApplicationBuild {
        return $this->run($applicationRoot, $mappingSourceRelativePath, $outputDirectory, true);
    }

    public function check(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $outputDirectory,
    ): CurrentDataApplicationBuild {
        return $this->run($applicationRoot, $mappingSourceRelativePath, $outputDirectory, false);
    }

    private function run(
        string $applicationRoot,
        string $mappingSourceRelativePath,
        string $outputDirectory,
        bool $build,
    ): CurrentDataApplicationBuild {
        $dataAccessWorkflow = new DataAccessBuildWorkflow();
        $dataAccess = $build
            ? $dataAccessWorkflow->build($applicationRoot, $mappingSourceRelativePath, $outputDirectory)
            : $dataAccessWorkflow->check($applicationRoot, $mappingSourceRelativePath, $outputDirectory);

        try {
            $application = $build
                ? (new ApplicationBuildWorkflow())->build($applicationRoot, $outputDirectory)
                : (new ApplicationBuildWorkflow())->check($applicationRoot, $outputDirectory);
        } catch (Throwable $failure) {
            throw new DataApplicationWorkflowException('composition_workflow', $failure);
        }

        $dataAccessWorkflow->reverify($dataAccess);

        return new CurrentDataApplicationBuild(
            $dataAccess->mappingArtifactPath(),
            $dataAccess->mappingArtifactBytes(),
            $dataAccess->mappingArtifactSha256(),
            $dataAccess->generatedDataAccessPath(),
            $dataAccess->generatedDataAccessSha256(),
            $dataAccess->loadedDataAccess(),
            $application,
        );
    }
}
