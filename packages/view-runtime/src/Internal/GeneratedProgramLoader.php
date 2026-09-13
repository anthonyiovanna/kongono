<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Internal;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Generated\RenderSink;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Value\ViewContext;
use Kongono\ViewRuntime\Exception\ArtifactLoadException;
use Throwable;

/** Performs the single fixed authenticated generated-program load or exact reuse. */
#[Api(ApiStatus::Internal)]
final class GeneratedProgramLoader
{
    /** @return Closure(LogicalViewIdentity, ViewContext, RenderSink): void */
    public function load(string $path, string $class, string $sourceSha256, string $modelSha256, string $generatedSha256): Closure
    {
        $alreadyLoaded = class_exists($class, false);
        if (!$alreadyLoaded && (interface_exists($class, false) || trait_exists($class, false) || enum_exists($class, false))) {
            throw ArtifactLoadException::failed('class_collision', $generatedSha256);
        }
        if (!$alreadyLoaded) {
            try {
                $returned = require $path;
            } catch (Throwable $failure) {
                throw ArtifactLoadException::failed('require_failed', $generatedSha256, $failure);
            }
            if (!is_string($returned) || !hash_equals($class, $returned)) {
                throw ArtifactLoadException::failed('program_return_mismatch', $generatedSha256);
            }
        }
        if (!class_exists($class, false)) {
            throw ArtifactLoadException::failed('class_missing', $generatedSha256);
        }
        foreach (['sourceSha256', 'modelSha256', 'emit'] as $method) {
            if (!is_callable([$class, $method])) {
                throw ArtifactLoadException::failed('callable_surface_mismatch', $generatedSha256);
            }
        }
        try {
            $actualSource = $class::sourceSha256();
            $actualModel = $class::modelSha256();
        } catch (Throwable $failure) {
            throw ArtifactLoadException::failed('callable_surface_mismatch', $generatedSha256, $failure);
        }
        if (!is_string($actualSource) || !hash_equals($sourceSha256, $actualSource)) {
            throw ArtifactLoadException::failed('source_identity_mismatch', $generatedSha256);
        }
        if (!is_string($actualModel) || !hash_equals($modelSha256, $actualModel)) {
            throw ArtifactLoadException::failed('model_identity_mismatch', $generatedSha256);
        }

        return static function (LogicalViewIdentity $identity, ViewContext $context, RenderSink $sink) use ($class): void {
            $class::emit($identity, $context, $sink);
        };
    }
}
