<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Loading;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactValidator;
use Kongono\ExtensionComposition\Generated\ExtensionWiringValidator;
use Kongono\ExtensionInspection\Exception\ExceptionInterface;
use Kongono\ExtensionInspection\Exception\ExtensionInspectionLoadException;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspector;
use Kongono\ExtensionInspection\Internal\PathGuard;
use Throwable;

/** Reads exactly two explicit files and validates them as inert detached bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionInspectionLoader
{
    public function load(string $declarationArtifactPath, string $generatedWiringPath): ExtensionPlatformInspection
    {
        $declarationBytes = PathGuard::readDeclaration($declarationArtifactPath);
        try {
            $declaration = (new ExtensionDeclarationArtifactValidator())->validateStructure($declarationBytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw ExtensionInspectionLoadException::structural($failure);
        } finally {
            unset($declarationBytes);
        }
        $wiringBytes = PathGuard::readWiring($generatedWiringPath);
        try {
            $wiring = (new ExtensionWiringValidator())->validateStructure($wiringBytes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw ExtensionInspectionLoadException::structural($failure);
        } finally {
            unset($wiringBytes);
        }
        if (!$declaration->declarationSetIdentity()->equals($wiring->declarationSetIdentity())) {
            throw ExtensionInspectionLoadException::identityMismatch();
        }
        return (new ExtensionPlatformInspector())->inspect($declaration, $wiring);
    }
}
