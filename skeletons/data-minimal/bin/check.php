<?php

declare(strict_types=1);

use Kongono\DatabaseApplication\Exception\ExceptionInterface;
use Kongono\DatabaseApplication\Workflow\DataApplicationBuildWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));

try {
    $build = (new DataApplicationBuildWorkflow())->check($root, 'kongono.data-mapping.json', $root . '/var/kongono/build');
    $application = $build->applicationBuild();
    fwrite(STDOUT, sprintf(
        "Data application check complete.\nmapping_artifact_sha256=%s\ngenerated_data_access_sha256=%s\ncomposition_artifact_sha256=%s\ngenerated_wiring_sha256=%s\n",
        $build->mappingArtifactSha256(),
        $build->generatedDataAccessSha256(),
        $application->artifactSha256(),
        $application->generatedWiringSha256(),
    ));
    exit(0);
} catch (ExceptionInterface $failure) {
    fwrite(STDERR, sprintf("Data application check failed: %s.\n", $failure->diagnosticId()));
} catch (Throwable) {
    fwrite(STDERR, "Data application check failed.\n");
}

exit(1);
