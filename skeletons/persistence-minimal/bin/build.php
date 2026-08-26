<?php

declare(strict_types=1);

use Kongono\PersistenceApplication\Exception\ExceptionInterface;
use Kongono\PersistenceApplication\Workflow\PersistenceApplicationBuildWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));

try {
    $build = (new PersistenceApplicationBuildWorkflow())->build(
        $root,
        'kongono.data-mapping.json',
        'kongono.persistence.json',
        'kongono.persistence-repositories.json',
        $root . '/var/kongono/build',
    );
    $data = $build->dataAccessBuild();
    $application = $build->applicationBuild();
    fwrite(STDOUT, sprintf(
        "Persistence application build complete.\nmapping_artifact_sha256=%s\ngenerated_data_access_sha256=%s\npersistence_artifact_sha256=%s\ngenerated_state_sha256=%s\ngenerated_aggregate_repository_sha256=%s\ncomposition_artifact_sha256=%s\ngenerated_wiring_sha256=%s\n",
        $data->mappingArtifactSha256(),
        $data->generatedDataAccessSha256(),
        $build->persistenceArtifactSha256(),
        $build->generatedStateSha256(),
        $build->generatedRepositorySha256(),
        $application->artifactSha256(),
        $application->generatedWiringSha256(),
    ));
    exit(0);
} catch (ExceptionInterface $failure) {
    fwrite(STDERR, sprintf("Persistence application build failed: %s.\n", $failure->diagnosticId()));
} catch (Throwable) {
    fwrite(STDERR, "Persistence application build failed.\n");
}

exit(1);
