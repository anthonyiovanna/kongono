<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Exception\CompositionException;
use Kongono\Composition\Exception\GeneratedWiringLoadException;
use Kongono\Composition\Generated\GeneratedWiringValidator;
use Kongono\Composition\Internal\PathGuard;
use ReflectionClass;

/** Admits and loads one caller-selected, exact current generated assembly. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWiringLoader
{
    public function loadCurrent(
        string $applicationRoot,
        string $compositionArtifactBytes,
        string $generatedPhpFile,
    ): LoadedGeneratedWiring {
        try {
            $root = PathGuard::resolveApplicationRoot($applicationRoot);
            $snapshot = PathGuard::readGeneratedFile($root, $generatedPhpFile);
            $artifact = (new GeneratedWiringValidator())->validateCurrent(
                $root,
                $compositionArtifactBytes,
                $snapshot['bytes'],
            );
        } catch (CompositionException $failure) {
            throw new GeneratedWiringLoadException('generated_file_invalid', $failure);
        }

        $expectedBasename = 'kongono-wiring-v1-' . $artifact->wiringSha256() . '.php';
        if (basename(str_replace('\\', '/', $snapshot['absolute'])) !== $expectedBasename) {
            throw new GeneratedWiringLoadException('generated_file_name');
        }

        $applicationClass = $artifact->applicationClass();
        $operationClass = $artifact->operationClass();
        if (class_exists($applicationClass, false) || class_exists($operationClass, false)) {
            throw new GeneratedWiringLoadException('class_collision');
        }

        try {
            PathGuard::reverify($snapshot);
        } catch (CompositionException $failure) {
            throw new GeneratedWiringLoadException('generated_file_invalid', $failure);
        }

        require $snapshot['absolute'];

        if (!class_exists($applicationClass, false) || !class_exists($operationClass, false)) {
            throw new GeneratedWiringLoadException('generated_shape');
        }

        /** @var class-string<GeneratedApplicationBoundary> $applicationClass */
        /** @var class-string<GeneratedOperationBoundary> $operationClass */

        $application = new ReflectionClass($applicationClass);
        $operation = new ReflectionClass($operationClass);
        if (
            !$application->isFinal()
            || !$application->implementsInterface(GeneratedApplicationBoundary::class)
            || !$operation->isFinal()
            || !$operation->implementsInterface(GeneratedOperationBoundary::class)
        ) {
            throw new GeneratedWiringLoadException('generated_shape');
        }

        $start = $application->getMethod('start');
        if (!$start->isPublic() || !$start->isStatic()) {
            throw new GeneratedWiringLoadException('generated_shape');
        }

        return new LoadedGeneratedWiring(
            $artifact->wiringSha256(),
            $applicationClass,
            $operationClass,
            $start,
        );
    }
}
