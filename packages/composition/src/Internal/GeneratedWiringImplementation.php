<?php

declare(strict_types=1);

namespace Kongono\Composition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Computes the reviewed closed implementation identity for generated wiring. */
#[Api(ApiStatus::Internal)]
final class GeneratedWiringImplementation
{
    /** @var list<string> */
    public const PATHS = [
        'src/Diagnostic/CompositionDiagnostic.php',
        'src/Exception/CompositionException.php',
        'src/Exception/CompositionLifecycleException.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/GeneratedWiringPublicationException.php',
        'src/Exception/InvalidGeneratedOperationInputsException.php',
        'src/Exception/InvalidGeneratedWiringException.php',
        'src/Exception/RetiredCompositionBoundaryException.php',
        'src/Generated/GeneratedWiringArtifact.php',
        'src/Generated/GeneratedWiringGenerator.php',
        'src/Generated/GeneratedWiringPublisher.php',
        'src/Generated/GeneratedWiringValidator.php',
        'src/Internal/GeneratedWiringEmitter.php',
        'src/Internal/GeneratedWiringImplementation.php',
        'src/Internal/PathGuard.php',
        'src/Runtime/GeneratedApplicationBoundary.php',
        'src/Runtime/GeneratedApplicationState.php',
        'src/Runtime/GeneratedOperationInputApplicationBoundary.php',
        'src/Runtime/GeneratedOperationInputs.php',
        'src/Runtime/GeneratedOperationBoundary.php',
        'src/Runtime/LifecycleFailureComposer.php',
    ];

    public static function sha256(): string
    {
        $packageRoot = PathGuard::resolveApplicationRoot(dirname(__DIR__, 2));
        $records = [];
        foreach (self::PATHS as $path) {
            $snapshot = PathGuard::readProjectFile($packageRoot, $path);
            $records[] = implode("\t", [$path, (string) $snapshot['size'], $snapshot['sha256']]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
