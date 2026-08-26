<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;

/** Computes the reviewed identity of the closed command builder implementation. */
#[Api(ApiStatus::Internal)]
final class ConsoleBuildImplementation
{
    /** @var list<string> */
    public const BUILDER_PATHS = [
        'src/Build/ConsoleArtifact.php',
        'src/Build/ConsoleArtifactBuilder.php',
        'src/Build/ConsoleArtifactValidator.php',
        'src/Diagnostic/ConsoleBuildDiagnostic.php',
        'src/Exception/ConsoleBuildException.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/InvalidConsoleArtifactException.php',
        'src/Exception/InvalidConsoleSourceException.php',
        'src/Exception/StaleConsoleArtifactException.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/ConsoleBuildFailureCategory.php',
        'src/Internal/ConsoleBuildImplementation.php',
        'src/Internal/ConsoleModelCompiler.php',
        'src/Internal/PathGuard.php',
        'src/Internal/StrictJsonDecoder.php',
        'src/Model/ConsoleModel.php',
    ];

    public static function builderSha256(): string
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $records = [];
        foreach (self::BUILDER_PATHS as $path) {
            $bytes = @file_get_contents($root . '/' . $path);
            if (false === $bytes) {
                throw InvalidConsoleSourceException::invalid(ConsoleBuildDiagnostic::InvalidSource, ConsoleBuildFailureCategory::SourcePath);
            }
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }

        return hash('sha256', implode("\n", $records));
    }

    private function __construct() {}
}
