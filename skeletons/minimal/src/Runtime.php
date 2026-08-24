<?php

declare(strict_types=1);

namespace App;

use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Kernel\Kernel;
use Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap;
use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\PhpCgi\Entry\CgiGeneratedFrontController;
use Kongono\Web\Runtime\GeneratedWebRuntimeApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap;

/** Checks, loads, starts, and delegates one generated CGI application request. */
final class Runtime
{
    private function __construct() {}

    public static function run(string $applicationRoot): void
    {
        $current = (new ApplicationBuildWorkflow())->check($applicationRoot, $applicationRoot . '/var/kongono/build');
        $wiring = (new GeneratedWiringLoader())->loadCurrent(
            $applicationRoot,
            $current->compositionArtifactBytes(),
            $current->generatedPhpPath(),
        );
        $kernel = new Kernel();
        $pipeline = Bootstrap::pipeline();
        $permission = CgiOperatorBootstrap::load(CgiOperatorProfile::Production)->bootstrapPermission();
        $bootstrap = new GeneratedWebRuntimeBootstrap($permission);
        $application = GeneratedWebRuntimeApplication::start($wiring, $kernel, $bootstrap, $pipeline);

        CgiGeneratedFrontController::run($application);
    }
}
