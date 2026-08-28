<?php

declare(strict_types=1);

use Kongono\ObservabilityBuild\Exception\ExceptionInterface as ObservabilityBuildException;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));
$output = $root . '/var/kongono/build';

try {
    $current = (new ObservabilityBuildWorkflow())->check($root, $output);
    fwrite(STDOUT, sprintf(
        "Observability application check complete.\nobservability_artifact_sha256=%s\nsource_sha256=%s\nprofile=%s\n",
        $current->artifactSha256(),
        $current->sourceSha256(),
        $current->profile(),
    ));
    exit(0);
} catch (ObservabilityBuildException $failure) {
    fwrite(STDERR, sprintf("Observability application check failed: %s.\n", $failure->diagnosticId()));
} catch (Throwable) {
    fwrite(STDERR, "Observability application check failed.\n");
}

exit(1);
