<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;
use Kongono\ConsoleBuild\Internal\CanonicalJson;
use Kongono\ConsoleBuild\Internal\ConsoleBuildFailureCategory;
use Kongono\ConsoleBuild\Internal\ConsoleBuildImplementation;
use Kongono\ConsoleBuild\Internal\ConsoleModelCompiler;
use Kongono\ConsoleBuild\Internal\PathGuard;

/** Builds canonical command evidence from one explicit current source. */
#[Api(ApiStatus::Experimental)]
final class ConsoleArtifactBuilder
{
    public const MAX_ARTIFACT_BYTES = 2_097_152;
    public const BUILDER_CONTRACT_VERSION = 1;

    public function build(string $applicationRoot, string $sourceRelativePath): ConsoleArtifact
    {
        $root = PathGuard::resolveApplicationRoot($applicationRoot);
        $snapshot = PathGuard::readCommandSource($root, $sourceRelativePath);
        PathGuard::validateSourceText($snapshot['bytes']);
        try {
            $source = CanonicalJson::decodeObject($snapshot['bytes'], 32);
        } catch (JsonException) {
            throw InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InvalidSource, ConsoleBuildFailureCategory::SourceJson);
        }

        $compiler = new ConsoleModelCompiler();
        $model = $compiler->compile($source);
        $modelData = $compiler->modelData($model);
        $modelBytes = CanonicalJson::encode($modelData);
        $modelSha256 = hash('sha256', $modelBytes);
        $normalizedSourceBytes = CanonicalJson::encode([
            'schema' => 'kongono.command-source',
            'version' => 1,
            'commands' => $modelData['commands'],
        ]);
        $sourceIdentity = [
            'path' => $snapshot['path'],
            'bytes' => strlen($normalizedSourceBytes),
            'sha256' => hash('sha256', $normalizedSourceBytes),
        ];
        $implementationSha256 = ConsoleBuildImplementation::builderSha256();
        $artifactBytes = CanonicalJson::encode([
            'schema' => 'kongono.command-artifact',
            'version' => 1,
            'model_schema' => 'kongono.command-model',
            'model_version' => 1,
            'source' => $sourceIdentity,
            'provenance' => [
                'console_builder' => [
                    'package' => 'kongono/console-build',
                    'contract_version' => self::BUILDER_CONTRACT_VERSION,
                    'implementation_sha256' => $implementationSha256,
                ],
                'command_source' => $sourceIdentity,
                'command_model' => ['bytes' => strlen($modelBytes), 'sha256' => $modelSha256],
            ],
            'model' => $modelData,
        ]);
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InvalidSource, ConsoleBuildFailureCategory::SourceBounds);
        }

        PathGuard::reverify($snapshot);

        return new ConsoleArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceIdentity['sha256'],
            $modelSha256,
            $implementationSha256,
            $model,
        );
    }
}
