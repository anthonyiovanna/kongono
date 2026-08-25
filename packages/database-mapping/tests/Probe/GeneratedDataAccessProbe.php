<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Probe;

use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessGenerator;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessPublisher;

/** Creates one externally inspectable generated file in a caller-owned directory. */
final class GeneratedDataAccessProbe
{
    /** @return array{path: string, artifact_sha256: string, model_sha256: string, generated_sha256: string, implementation_sha256: string} */
    public static function create(string $repositoryRoot, string $outputDirectory): array
    {
        $source = 'packages/database-mapping/tests/Fixture/kongono.data-mapping.json';
        $artifact = (new MappingArtifactBuilder())->build($repositoryRoot, $source);
        $generated = (new GeneratedDataAccessGenerator())->generate($repositoryRoot, $source, $artifact->canonicalBytes());
        $path = (new GeneratedDataAccessPublisher())->publish($generated, $outputDirectory);

        return [
            'path' => $path,
            'artifact_sha256' => $artifact->artifactSha256(),
            'model_sha256' => $artifact->modelSha256(),
            'generated_sha256' => $generated->generatedFileSha256(),
            'implementation_sha256' => $generated->generatorImplementationSha256(),
        ];
    }

    private function __construct() {}
}
