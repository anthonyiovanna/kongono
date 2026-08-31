<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Computes the exact reviewed implementation identity bound into generated evidence. */
#[Api(ApiStatus::Internal)]
final class GeneratedExtensionWiringImplementation
{
    /** @var list<string> */
    public const PATHS = [
        'src/Artifact/GeneratedExtensionWiringArtifact.php',
        'src/Diagnostic/ExtensionCompositionDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/ExtensionCompositionException.php',
        'src/Exception/InvalidExtensionGrantPlanException.php',
        'src/Exception/InvalidGeneratedExtensionWiringException.php',
        'src/Exception/ExtensionWiringPublicationException.php',
        'src/Generated/ExtensionWiringGenerator.php',
        'src/Generated/ExtensionWiringPublisher.php',
        'src/Generated/ExtensionWiringValidator.php',
        'src/Grant/CapabilityDecision.php',
        'src/Grant/CapabilityDisposition.php',
        'src/Grant/CapabilityProviderLifetime.php',
        'src/Identity/GrantPlanIdentity.php',
        'src/Implementation/TrustedExtensionImplementation.php',
        'src/Plan/ExtensionGrantPlan.php',
        'src/Runtime/GeneratedExtensionApplicationBoundary.php',
        'src/Runtime/GeneratedExtensionOperationBoundary.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/ExtensionGrantPlanValidator.php',
        'src/Internal/GeneratedExtensionWiringEmitter.php',
        'src/Internal/GeneratedExtensionWiringImplementation.php',
        'src/Internal/PathGuard.php',
    ];

    public static function sha256(): string
    {
        $root = dirname(__DIR__, 2);
        $records = [];
        foreach (self::PATHS as $path) {
            $bytes = PathGuard::readImplementationFile($root, $path);
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
