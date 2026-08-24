<?php

declare(strict_types=1);

namespace Kongono\Composition\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Build\CompositionArtifactValidator;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidGeneratedWiringException;
use Kongono\Composition\Internal\CanonicalJson;
use Kongono\Composition\Internal\GeneratedWiringEmitter;
use Kongono\Composition\Internal\GeneratedWiringImplementation;

/** Generates deterministic typed constructor wiring from one exact current artifact. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWiringGenerator
{
    public const MAX_GENERATED_BYTES = 4194304;

    public function generate(string $applicationRoot, string $compositionArtifactBytes): GeneratedWiringArtifact
    {
        $composition = (new CompositionArtifactValidator())->validateCurrent($applicationRoot, $compositionArtifactBytes);
        $decoded = CanonicalJson::decodeObject($composition->canonicalBytes(), 64);
        $model = $decoded['model'] ?? null;
        if (!is_array($model) || array_is_list($model)) {
            throw new InvalidGeneratedWiringException(CompositionDiagnostic::InvalidGeneratedWiring, 'composition_model_missing');
        }
        /** @var array<string, mixed> $model */
        $bytes = (new GeneratedWiringEmitter())->emit(
            $model,
            $composition->artifactSha256(),
            $composition->modelSha256(),
            GeneratedWiringImplementation::sha256(),
        );
        if (strlen($bytes) > self::MAX_GENERATED_BYTES) {
            throw new InvalidGeneratedWiringException(CompositionDiagnostic::InvalidGeneratedWiring, 'generated_bytes_limit');
        }

        return (new GeneratedWiringValidator())->validateStructure($bytes);
    }
}
