<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactLoadException;
use Kongono\ApplicationGraphArtifact\Internal\PathGuard;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;

/** Loads one explicitly named stable content-addressed artifact file. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphArtifactLoader
{
    public function load(string $artifactPath): ApplicationGraphArtifact
    {
        $snapshot = PathGuard::readArtifact($artifactPath);
        try {
            $artifact = (new ApplicationGraphArtifactValidator())->validateStructure($snapshot['bytes']);
        } catch (\Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }throw ApplicationGraphArtifactLoadException::failed('invalid_artifact', 'artifact', $failure);
        }
        $expected = 'kongono-application-graph-v1-' . $artifact->artifactSha256() . '.json';
        if (basename($snapshot['absolute']) !== $expected) {
            throw ApplicationGraphArtifactLoadException::failed('artifact_name_mismatch');
        }
        return $artifact;
    }

    public function loadCurrent(string $artifactPath, AuthoritativeApplicationGraph $currentGraph): ApplicationGraphArtifact
    {
        $artifact = $this->load($artifactPath);
        try {
            return (new ApplicationGraphArtifactValidator())->validateCurrent($currentGraph, $artifact->canonicalBytes());
        } catch (\Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }throw ApplicationGraphArtifactLoadException::failed('artifact_not_current', 'artifact', $failure);
        }
    }
}
