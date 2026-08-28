<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifact;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifactBuilder;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifactPublisher;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifactValidator;
use Kongono\ObservabilityBuild\Diagnostic\ObservabilityBuildDiagnostic;
use Kongono\ObservabilityBuild\Exception\ObservabilityWorkflowException;
use Kongono\ObservabilityBuild\Internal\PathGuard;
use Throwable;

/** Inspects, builds, or write-freely checks one exact current configuration. */
#[Api(ApiStatus::Experimental)]
final class ObservabilityBuildWorkflow
{
    public function inspect(string $applicationRoot, string $outputDirectory): PlannedObservabilityConfiguration
    {
        [$root, , $artifact, $path] = $this->planState($applicationRoot, $outputDirectory);
        $present = false !== @lstat($path);
        if ($present) {
            $this->checked($root, $artifact, $path);
        }

        return new PlannedObservabilityConfiguration(
            basename($path),
            $artifact->artifactSha256(),
            $artifact->sourceSha256(),
            $artifact->profile(),
            !$present,
        );
    }

    public function build(string $applicationRoot, string $outputDirectory): CurrentObservabilityConfiguration
    {
        [$root, $output, $artifact, $path] = $this->planState($applicationRoot, $outputDirectory);
        try {
            $published = (new ObservabilityArtifactPublisher())->publish($artifact, $root, $output);
        } catch (Throwable $failure) {
            throw new ObservabilityWorkflowException(ObservabilityBuildDiagnostic::WorkflowFailed, $failure);
        }
        if ($published !== $path) {
            throw new ObservabilityWorkflowException(ObservabilityBuildDiagnostic::WorkflowFailed);
        }

        return $this->checked($root, $artifact, $path);
    }

    public function check(string $applicationRoot, string $outputDirectory): CurrentObservabilityConfiguration
    {
        [$root, , $artifact, $path] = $this->planState($applicationRoot, $outputDirectory);

        return $this->checked($root, $artifact, $path);
    }

    /** @return array{string,string,ObservabilityArtifact,string} */
    private function planState(string $applicationRoot, string $outputDirectory): array
    {
        try {
            $root = PathGuard::applicationRoot($applicationRoot);
            $output = PathGuard::outputDirectory($root, $outputDirectory);
            $artifact = (new ObservabilityArtifactBuilder())->build($root);
        } catch (Throwable $failure) {
            throw new ObservabilityWorkflowException(ObservabilityBuildDiagnostic::WorkflowFailed, $failure);
        }
        $path = $output . '/' . ObservabilityArtifactPublisher::basename($artifact);

        return [$root, $output, $artifact, $path];
    }

    private function checked(string $root, ObservabilityArtifact $expected, string $path): CurrentObservabilityConfiguration
    {
        try {
            $snapshot = PathGuard::artifact($root, $path, ObservabilityArtifactBuilder::MAX_ARTIFACT_BYTES);
            if (!hash_equals($expected->canonicalBytes(), $snapshot['bytes'])) {
                throw new ObservabilityWorkflowException(ObservabilityBuildDiagnostic::WorkflowFailed);
            }
            $artifact = (new ObservabilityArtifactValidator())->validateCurrent($root, $snapshot['bytes']);
            PathGuard::reverify($snapshot);
        } catch (ObservabilityWorkflowException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new ObservabilityWorkflowException(ObservabilityBuildDiagnostic::WorkflowFailed, $failure);
        }

        return new CurrentObservabilityConfiguration($artifact, basename($path));
    }
}
