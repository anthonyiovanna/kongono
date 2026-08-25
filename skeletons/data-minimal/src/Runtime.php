<?php

declare(strict_types=1);

namespace KongonoDataMinimal;

use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Database\DatabaseConnector;
use Kongono\DatabaseApplication\GeneratedDataApplication;
use Kongono\DatabaseApplication\Workflow\DataApplicationBuildWorkflow;

/** Checks, loads, and starts one generated data application from a host connector. */
final class Runtime
{
    private function __construct() {}

    public static function start(string $applicationRoot, DatabaseConnector $connector): GeneratedDataApplication
    {
        $current = (new DataApplicationBuildWorkflow())->check(
            $applicationRoot,
            'kongono.data-mapping.json',
            $applicationRoot . '/var/kongono/build',
        );
        $applicationBuild = $current->applicationBuild();
        $wiring = (new GeneratedWiringLoader())->loadCurrent(
            $applicationRoot,
            $applicationBuild->compositionArtifactBytes(),
            $applicationBuild->generatedPhpPath(),
        );

        return GeneratedDataApplication::start($wiring, $connector);
    }
}
