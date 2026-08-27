<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifact;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifactBuilder;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifactPublisher;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifactValidator;
use Kongono\SecurityPolicyBuild\Diagnostic\SecurityPolicyBuildDiagnostic;
use Kongono\SecurityPolicyBuild\Exception\SecurityPolicyWorkflowException;
use Kongono\SecurityPolicyBuild\Internal\PathGuard;
use Throwable;

/** Inspects, builds, or write-freely checks one exact current policy artifact. */
#[Api(ApiStatus::Experimental)]
final class SecurityPolicyBuildWorkflow
{
    public function inspect(string $applicationRoot, string $outputDirectory): PlannedSecurityPolicy
    {
        [$root, $output, $artifact, $path] = $this->planState($applicationRoot, $outputDirectory);
        $present = file_exists($path) || is_link($path);
        if ($present) {
            $this->checked($root, $artifact, $path);
        }

        return new PlannedSecurityPolicy(
            basename($path),
            $artifact->artifactSha256(),
            $artifact->sourceSha256(),
            !$present,
        );
    }

    public function build(string $applicationRoot, string $outputDirectory): CurrentSecurityPolicy
    {
        [$root, $output, $artifact, $path] = $this->planState($applicationRoot, $outputDirectory);
        try {
            $published = (new SecurityPolicyArtifactPublisher())->publish($artifact, $root, $output);
        } catch (Throwable $failure) {
            throw new SecurityPolicyWorkflowException(SecurityPolicyBuildDiagnostic::WorkflowFailed, $failure);
        }
        if ($published !== $path) {
            throw new SecurityPolicyWorkflowException(SecurityPolicyBuildDiagnostic::WorkflowFailed);
        }

        return $this->checked($root, $artifact, $path);
    }

    public function check(string $applicationRoot, string $outputDirectory): CurrentSecurityPolicy
    {
        [$root, , $artifact, $path] = $this->planState($applicationRoot, $outputDirectory);

        return $this->checked($root, $artifact, $path);
    }

    /** @return array{string,string,SecurityPolicyArtifact,string} */
    private function planState(string $applicationRoot, string $outputDirectory): array
    {
        try {
            $root = PathGuard::applicationRoot($applicationRoot);
            $output = PathGuard::outputDirectory($root, $outputDirectory);
            $artifact = (new SecurityPolicyArtifactBuilder())->build($root);
        } catch (Throwable $failure) {
            throw new SecurityPolicyWorkflowException(SecurityPolicyBuildDiagnostic::WorkflowFailed, $failure);
        }
        $path = $output . '/' . SecurityPolicyArtifactPublisher::basename($artifact);

        return [$root, $output, $artifact, $path];
    }

    private function checked(string $root, SecurityPolicyArtifact $expected, string $path): CurrentSecurityPolicy
    {
        try {
            $snapshot = PathGuard::artifact($root, $path, SecurityPolicyArtifactBuilder::MAX_ARTIFACT_BYTES);
            if (!hash_equals($expected->canonicalBytes(), $snapshot['bytes'])) {
                throw new SecurityPolicyWorkflowException(SecurityPolicyBuildDiagnostic::WorkflowFailed);
            }
            $artifact = (new SecurityPolicyArtifactValidator())->validateCurrent($root, $snapshot['bytes']);
            PathGuard::reverify($snapshot);
        } catch (SecurityPolicyWorkflowException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new SecurityPolicyWorkflowException(SecurityPolicyBuildDiagnostic::WorkflowFailed, $failure);
        }

        return new CurrentSecurityPolicy($artifact, basename($path));
    }
}
