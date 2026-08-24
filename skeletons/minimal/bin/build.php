<?php

declare(strict_types=1);

use Kongono\Composition\Exception\ExceptionInterface;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));

try {
    $build = (new ApplicationBuildWorkflow())->build($root, $root . '/var/kongono/build');
    fwrite(STDOUT, sprintf(
        "Application build complete.\ncomposition_artifact_sha256=%s\ngenerated_wiring_sha256=%s\n",
        $build->artifactSha256(),
        $build->generatedWiringSha256(),
    ));
    exit(0);
} catch (ExceptionInterface $failure) {
    fwrite(STDERR, sprintf("Application build failed: %s.\n", $failure->diagnosticId()));
} catch (Throwable) {
    fwrite(STDERR, "Application build failed.\n");
}

exit(1);
