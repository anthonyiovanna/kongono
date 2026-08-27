<?php

declare(strict_types=1);

namespace Kongono\Composition\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidCompositionSourceException;
use Kongono\Composition\Internal\CanonicalJson;
use Kongono\Composition\Internal\CompositionModelCompiler;
use Kongono\Composition\Internal\PathGuard;

/** Validates one explicit application source and builds its detached artifact. */
#[Api(ApiStatus::Experimental)]
final class CompositionArtifactBuilder
{
    public const MAX_ARTIFACT_BYTES = 4194304;
    public const BUILDER_CONTRACT_VERSION = 1;

    /** @var list<string> */
    private const IMPLEMENTATION_PATHS = [
        'src/Build/CompositionArtifact.php',
        'src/Build/CompositionArtifactBuilder.php',
        'src/Build/CompositionArtifactPublisher.php',
        'src/Build/CompositionArtifactValidator.php',
        'src/Diagnostic/CompositionDiagnostic.php',
        'src/Exception/CompositionException.php',
        'src/Exception/CompositionLifecycleException.php',
        'src/Exception/CompositionPublicationException.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/GeneratedWiringPublicationException.php',
        'src/Exception/InvalidGeneratedOperationInputsException.php',
        'src/Exception/InvalidCompositionArtifactException.php',
        'src/Exception/InvalidCompositionSourceException.php',
        'src/Exception/InvalidGeneratedWiringException.php',
        'src/Exception/RetiredCompositionBoundaryException.php',
        'src/Exception/StaleCompositionArtifactException.php',
        'src/Generated/GeneratedWiringArtifact.php',
        'src/Generated/GeneratedWiringGenerator.php',
        'src/Generated/GeneratedWiringPublisher.php',
        'src/Generated/GeneratedWiringValidator.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/CompositionModelCompiler.php',
        'src/Internal/GeneratedWiringEmitter.php',
        'src/Internal/GeneratedWiringImplementation.php',
        'src/Internal/PathGuard.php',
        'src/Internal/StrictJsonDecoder.php',
        'src/Runtime/GeneratedApplicationBoundary.php',
        'src/Runtime/GeneratedApplicationState.php',
        'src/Runtime/GeneratedOperationInputApplicationBoundary.php',
        'src/Runtime/GeneratedOperationInputs.php',
        'src/Runtime/GeneratedOperationBoundary.php',
        'src/Runtime/LifecycleFailureComposer.php',
    ];

    public function build(string $applicationRoot): CompositionArtifact
    {
        $root = PathGuard::resolveApplicationRoot($applicationRoot);
        $sourceSnapshot = PathGuard::readSource($root);
        PathGuard::validateSourceText($sourceSnapshot['bytes']);
        try {
            $source = CanonicalJson::decodeObject($sourceSnapshot['bytes'], 32);
        } catch (JsonException) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidSourceJson, 'invalid_json');
        }
        $keys = array_keys($source);
        sort($keys, SORT_STRING);
        if (['bindings', 'inputs', 'roots', 'schema', 'services', 'version'] !== $keys) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::UnsupportedSourceSchema, 'top_level_members');
        }
        if ('kongono.composition' !== $source['schema'] || !in_array($source['version'], [1, 2], true)) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::UnsupportedSourceSchema, 'schema_or_version');
        }

        $compiled = (new CompositionModelCompiler())->compile($root, $source);
        $modelBytes = CanonicalJson::encode($compiled['model']);
        $modelSha256 = hash('sha256', $modelBytes);

        $composerSnapshot = PathGuard::readProjectFile($root, 'composer.json');
        $lockSnapshot = PathGuard::readProjectFile($root, 'composer.lock');
        $sourceSetRecords = [];
        $sourceBytes = 0;
        foreach ($compiled['sources'] as $record) {
            $sourceSetRecords[] = implode("\t", [$record['path'], (string) $record['bytes'], $record['sha256']]);
            $sourceBytes += $record['bytes'];
        }
        $sourceSetSha256 = hash('sha256', implode("\n", $sourceSetRecords));
        $builderImplementationSha256 = $this->builderImplementationSha256();

        $data = [
            'schema' => 'kongono.composition-artifact',
            'version' => 1,
            'source' => ['path' => PathGuard::SOURCE_PATH, 'bytes' => $sourceSnapshot['size'], 'sha256' => $sourceSnapshot['sha256']],
            'project' => ['composer_json_sha256' => $composerSnapshot['sha256'], 'composer_lock_sha256' => $lockSnapshot['sha256']],
            'builder' => ['package' => 'kongono/composition', 'contract_version' => self::BUILDER_CONTRACT_VERSION, 'implementation_sha256' => $builderImplementationSha256],
            'symbol_state' => [
                'source_set_sha256' => $sourceSetSha256,
                'source_count' => count($compiled['sources']),
                'source_bytes' => $sourceBytes,
                'sources' => $compiled['sources'],
            ],
            'model_sha256' => $modelSha256,
            'model' => $compiled['model'],
        ];
        $artifactBytes = CanonicalJson::encode($data);
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::CompositionLimitExceeded, 'artifact_bytes');
        }

        PathGuard::reverify($sourceSnapshot);
        PathGuard::reverify($composerSnapshot);
        PathGuard::reverify($lockSnapshot);
        foreach ($compiled['snapshots'] as $snapshot) {
            PathGuard::reverify($snapshot);
        }

        return new CompositionArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSnapshot['sha256'],
            $modelSha256,
            $sourceSetSha256,
            $builderImplementationSha256,
        );
    }

    private function builderImplementationSha256(): string
    {
        $packageRoot = PathGuard::resolveApplicationRoot(dirname(__DIR__, 2));
        $records = [];
        foreach (self::IMPLEMENTATION_PATHS as $path) {
            $snapshot = PathGuard::readProjectFile($packageRoot, $path);
            $records[] = implode("\t", [$path, (string) $snapshot['size'], $snapshot['sha256']]);
        }

        return hash('sha256', implode("\n", $records));
    }
}
