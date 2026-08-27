<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifact;

/** Binds one running application to exact immutable checked policy evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentSecurityPolicy
{
    public function __construct(private SecurityPolicyArtifact $artifact, private string $artifactBasename) {}

    public function evidence(): SecurityPolicyArtifact
    {
        return $this->artifact;
    }
    public function artifactBasename(): string
    {
        return $this->artifactBasename;
    }
    public function artifactSha256(): string
    {
        return $this->artifact->artifactSha256();
    }
    public function sourceSha256(): string
    {
        return $this->artifact->sourceSha256();
    }
}
