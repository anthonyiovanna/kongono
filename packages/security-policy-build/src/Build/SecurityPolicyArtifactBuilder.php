<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityPolicyBuild\Internal\CanonicalJson;
use Kongono\SecurityPolicyBuild\Internal\PathGuard;

/** Builds deterministic canonical evidence from one exact policy identity source. */
#[Api(ApiStatus::Experimental)]
final class SecurityPolicyArtifactBuilder
{
    public const MAX_ARTIFACT_BYTES = 8192;
    public const CONTRACT_VERSION = 1;

    /** @var list<string> */
    private const IMPLEMENTATION_PATHS = [
        'src/Build/SecurityPolicyArtifact.php',
        'src/Build/SecurityPolicyArtifactBuilder.php',
        'src/Build/SecurityPolicyArtifactPublisher.php',
        'src/Build/SecurityPolicyArtifactValidator.php',
        'src/Build/SecurityPolicySourceReader.php',
        'src/Diagnostic/SecurityPolicyBuildDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/InvalidSecurityPolicyArtifactException.php',
        'src/Exception/InvalidSecurityPolicySourceException.php',
        'src/Exception/SecurityPolicyBuildException.php',
        'src/Exception/SecurityPolicyPublicationException.php',
        'src/Exception/SecurityPolicyWorkflowException.php',
        'src/Exception/StaleSecurityPolicyArtifactException.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/PathGuard.php',
        'src/Internal/StrictJsonDecoder.php',
        'src/Model/SecurityPolicyModel.php',
        'src/Workflow/CurrentSecurityPolicy.php',
        'src/Workflow/PlannedSecurityPolicy.php',
        'src/Workflow/SecurityPolicyBuildWorkflow.php',
    ];

    public function build(string $applicationRoot): SecurityPolicyArtifact
    {
        $source = (new SecurityPolicySourceReader())->read($applicationRoot);
        $reference = $source['model']->reference();
        $model = [
            'name' => $reference->name()->value(),
            'version' => $reference->version()->value(),
            'provenance' => $reference->provenance()->value(),
        ];
        $modelSha256 = hash('sha256', CanonicalJson::encode($model));
        $implementationSha256 = $this->implementationSha256();
        $bytes = CanonicalJson::encode([
            'schema' => 'kongono.security-policy-artifact',
            'version' => 1,
            'policy' => $model,
            'source' => ['path' => PathGuard::SOURCE_PATH, 'sha256' => $source['sha256']],
            'model_sha256' => $modelSha256,
            'builder' => [
                'package' => 'kongono/security-policy-build',
                'contract_version' => self::CONTRACT_VERSION,
                'implementation_sha256' => $implementationSha256,
            ],
        ]);
        if (strlen($bytes) > self::MAX_ARTIFACT_BYTES) {
            throw new \LogicException('Security policy artifact exceeded its fixed bound.');
        }
        PathGuard::reverify($source);

        return new SecurityPolicyArtifact(
            $bytes,
            hash('sha256', $bytes),
            $source['sha256'],
            $modelSha256,
            $implementationSha256,
            $reference,
        );
    }

    private function implementationSha256(): string
    {
        $root = PathGuard::applicationRoot(dirname(__DIR__, 2));
        $records = [];
        foreach (self::IMPLEMENTATION_PATHS as $path) {
            $absolute = $root . '/' . $path;
            $bytes = file_get_contents($absolute);
            if (false === $bytes || !is_file($absolute) || is_link($absolute)) {
                throw new \LogicException('Security policy build implementation is incomplete.');
            }
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }
}
