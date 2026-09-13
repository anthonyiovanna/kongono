<?php

declare(strict_types=1);

namespace ViewMinimalApp;

use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Http\Message\Factory\ResponseFactory;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Kernel\Kernel;
use Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap;
use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\PhpCgi\Entry\CgiGeneratedFrontController;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\ViewApplicationFactory;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;
use Kongono\Web\Runtime\GeneratedWebRuntimeApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap;

/** Checks, loads, starts, and delegates one generated CGI application request. */
final class Runtime
{
    private function __construct() {}

    public static function run(string $applicationRoot): void
    {
        $viewDefinition = new ViewApplicationDefinition(
            $applicationRoot,
            $applicationRoot . '/views/home.view.json',
            $applicationRoot . '/var/kongono/view',
        );
        $currentView = (new ViewApplicationBuildWorkflow())->check($viewDefinition);
        $views = (new ViewApplicationFactory())->create($currentView, new ResponseFactory(), new StreamFactory());
        $current = (new ApplicationBuildWorkflow())->check($applicationRoot, $applicationRoot . '/var/kongono/build');
        $wiring = (new GeneratedWiringLoader())->loadCurrent(
            $applicationRoot,
            $current->compositionArtifactBytes(),
            $current->generatedPhpPath(),
        );
        $kernel = new Kernel();
        $pipeline = Bootstrap::pipeline($views, $currentView->compiledViewIdentity());
        $permission = CgiOperatorBootstrap::load(CgiOperatorProfile::Production)->bootstrapPermission();
        $bootstrap = new GeneratedWebRuntimeBootstrap($permission);
        $application = GeneratedWebRuntimeApplication::start($wiring, $kernel, $bootstrap, $pipeline);

        CgiGeneratedFrontController::run($application);
    }
}
