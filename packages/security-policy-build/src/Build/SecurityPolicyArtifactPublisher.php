<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityPolicyBuild\Diagnostic\SecurityPolicyBuildDiagnostic;
use Kongono\SecurityPolicyBuild\Exception\SecurityPolicyPublicationException;
use Kongono\SecurityPolicyBuild\Internal\PathGuard;

/** Publishes exact canonical bytes atomically from external temporary state. */
#[Api(ApiStatus::Experimental)]
final class SecurityPolicyArtifactPublisher
{
    public function publish(SecurityPolicyArtifact $artifact, string $applicationRoot, string $outputDirectory): string
    {
        $validated = (new SecurityPolicyArtifactValidator())->validateStructure($artifact->canonicalBytes());
        if (!hash_equals($validated->artifactSha256(), $artifact->artifactSha256())) {
            throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
        }
        $root = PathGuard::applicationRoot($applicationRoot);
        $output = PathGuard::outputDirectory($root, $outputDirectory);
        $target = $output . '/' . self::basename($artifact);
        if (false !== @lstat($target)) {
            $this->acceptExisting($target, $artifact->canonicalBytes());

            return $target;
        }

        $temporary = tempnam(sys_get_temp_dir(), '.kongono-security-policy-');
        if (false === $temporary) {
            throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
        }
        try {
            if (is_link($temporary) || !is_file($temporary)) {
                throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
            }
            $handle = @fopen($temporary, 'wb');
            if (false === $handle) {
                throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
            }
            try {
                $offset = 0;
                $bytes = $artifact->canonicalBytes();
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
                    throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
                }
            } finally {
                fclose($handle);
            }
            if (!@link($temporary, $target)) {
                $this->acceptExisting($target, $bytes);
            }
        } finally {
            if (file_exists($temporary) && !@unlink($temporary)) {
                throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
            }
        }

        return $target;
    }

    public static function basename(SecurityPolicyArtifact $artifact): string
    {
        return 'kongono-security-policy-v1-' . $artifact->artifactSha256() . '.json';
    }

    private function acceptExisting(string $path, string $expectedBytes): void
    {
        $resolved = realpath($path);
        $bytes = @file_get_contents($path);
        if (
            false === $resolved
            || str_replace('\\', '/', $resolved) !== str_replace('\\', '/', $path)
            || is_link($path)
            || !is_file($path)
            || false === $bytes
            || !hash_equals($expectedBytes, $bytes)
        ) {
            throw new SecurityPolicyPublicationException(SecurityPolicyBuildDiagnostic::PublicationFailed);
        }
        (new SecurityPolicyArtifactValidator())->validateStructure($bytes);
    }
}
