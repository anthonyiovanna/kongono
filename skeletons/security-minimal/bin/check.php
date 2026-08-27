<?php

declare(strict_types=1);

use Kongono\Composition\Exception\ExceptionInterface as CompositionException;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\SecurityPolicyBuild\Exception\ExceptionInterface as PolicyException;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));
$output = $root . '/var/kongono/build';

try {
    $application = (new ApplicationBuildWorkflow())->check($root, $output);
    $policy = (new SecurityPolicyBuildWorkflow())->check($root, $output);
    fwrite(STDOUT, sprintf(
        "Security application check complete.\ncomposition_artifact_sha256=%s\ngenerated_wiring_sha256=%s\nsecurity_policy_artifact_sha256=%s\n",
        $application->artifactSha256(),
        $application->generatedWiringSha256(),
        $policy->artifactSha256(),
    ));
    exit(0);
} catch (CompositionException|PolicyException $failure) {
    fwrite(STDERR, sprintf("Security application check failed: %s.\n", $failure->diagnosticId()));
} catch (Throwable) {
    fwrite(STDERR, "Security application check failed.\n");
}

exit(1);
