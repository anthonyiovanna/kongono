<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal;

use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Database\DatabaseConnector;
use Kongono\DatabaseApplication\GeneratedDataApplication;
use Kongono\PersistenceApplication\Workflow\PersistenceApplicationBuildWorkflow;

/** Checks and starts one generated persistence application from a host connector. */
final class Runtime
{
    private function __construct() {}

    public static function start(string $applicationRoot, DatabaseConnector $connector): GeneratedDataApplication
    {
        $current = (new PersistenceApplicationBuildWorkflow())->check(
            $applicationRoot,
            'kongono.data-mapping.json',
            'kongono.persistence.json',
            'kongono.persistence-repositories.json',
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
