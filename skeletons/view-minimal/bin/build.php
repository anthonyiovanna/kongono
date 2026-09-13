<?php

declare(strict_types=1);

use Kongono\Composition\Exception\ExceptionInterface as CompositionException;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Exception\ExceptionInterface as ViewException;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));
$definition = new ViewApplicationDefinition($root, $root . '/views/home.view.json', $root . '/var/kongono/view');

try {
    $composition = (new ApplicationBuildWorkflow())->build($root, $root . '/var/kongono/build');
    $view = (new ViewApplicationBuildWorkflow())->build($definition);
    fwrite(STDOUT, sprintf(
        "View build complete.\ncompiled_sha256=%s\ncomposition_artifact_sha256=%s\ngenerated_wiring_sha256=%s\n",
        $view->compiledViewIdentity()->value(),
        $composition->artifactSha256(),
        $composition->generatedWiringSha256(),
    ));
    exit(0);
} catch (ViewException|CompositionException $failure) {
    fwrite(STDERR, sprintf("View build failed: %s.\n", $failure->diagnosticId()));
} catch (Throwable) {
    fwrite(STDERR, "View build failed.\n");
}

exit(1);
