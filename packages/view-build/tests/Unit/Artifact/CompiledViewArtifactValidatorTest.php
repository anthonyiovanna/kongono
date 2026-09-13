<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Artifact;

use Kongono\ViewBuild\Artifact\CompiledViewArtifactValidator;
use Kongono\ViewBuild\Exception\InvalidCompiledViewArtifactException;
use Kongono\ViewBuild\Exception\StaleCompiledViewArtifactException;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class CompiledViewArtifactValidatorTest extends TestCase
{
    public function testStructureAndCurrentnessRegenerateWithoutLoadingPhp(): void
    {
        $artifact = BuildProbe::artifact();
        $validator = new CompiledViewArtifactValidator();
        self::assertTrue($artifact->equals($validator->validateStructure($artifact->metadataBytes(), $artifact->generatedPhpBytes())));
        $source = (new ViewSourceParser())->parse(BuildProbe::sourceBytes());
        self::assertTrue($artifact->equals($validator->validateCurrent($source, $artifact->metadataBytes(), $artifact->generatedPhpBytes())));
    }

    public function testRejectsDuplicateMetadataMember(): void
    {
        $artifact = BuildProbe::artifact();
        $mutated = preg_replace('/\A\{/', '{"schema":"kongono.compiled-view-artifact",', $artifact->metadataBytes());
        self::assertIsString($mutated);
        $this->expectException(InvalidCompiledViewArtifactException::class);
        (new CompiledViewArtifactValidator())->validateStructure($mutated, $artifact->generatedPhpBytes());
    }

    public function testRejectsGeneratedMutationEvenWhenMetadataHashIsAdjusted(): void
    {
        $artifact = BuildProbe::artifact();
        $php = str_replace('declare(strict_types=1);', 'declare(strict_types=0);', $artifact->generatedPhpBytes());
        $data = json_decode($artifact->metadataBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $generated = $data['generated_php'] ?? null;
        self::assertIsArray($generated);
        $generated['sha256'] = hash('sha256', $php);
        $data['generated_php'] = $generated;
        $metadata = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $this->expectException(InvalidCompiledViewArtifactException::class);
        (new CompiledViewArtifactValidator())->validateStructure($metadata, $php);
    }

    public function testRejectsDifferentExplicitCurrentSource(): void
    {
        $artifact = BuildProbe::artifact();
        $changed = str_replace('<safe & static>', '<changed>', BuildProbe::sourceBytes());
        $this->expectException(StaleCompiledViewArtifactException::class);
        (new CompiledViewArtifactValidator())->validateCurrent((new ViewSourceParser())->parse($changed), $artifact->metadataBytes(), $artifact->generatedPhpBytes());
    }

    public function testRejectsMutationsAtEveryMetadataLevel(): void
    {
        $artifact = BuildProbe::artifact();
        $metadata = $artifact->metadataBytes();
        $sourceDigest = $artifact->sourceIdentity()->value();
        $modelDigest = $artifact->modelSha256();
        $generatedDigest = $artifact->generatedPhpSha256();
        $mutations = [
            'schema' => ['"schema":"kongono.compiled-view-artifact"', '"schema":"other"'],
            'artifact version' => ['"artifact_version":1', '"artifact_version":2'],
            'contract version' => ['"contract_version":1', '"contract_version":2'],
            'builder package' => ['"package":"kongono/view-build"', '"package":"other/package"'],
            'builder version' => ['"builder":{"package":"kongono/view-build","version":1}', '"builder":{"package":"kongono/view-build","version":2}'],
            'source identity' => [$sourceDigest, self::changedDigest($sourceDigest)],
            'model identity' => [$modelDigest, self::changedDigest($modelDigest)],
            'generated class' => [
                json_encode($artifact->generatedClass(), JSON_THROW_ON_ERROR),
                json_encode($artifact->generatedClass() . 'x', JSON_THROW_ON_ERROR),
            ],
            'generated identity' => [$generatedDigest, self::changedDigest($generatedDigest)],
            'view identity' => ['"id":"page.alpha"', '"id":"page.changed"'],
            'context schema' => ['"max_bytes":32', '"max_bytes":33'],
            'operation' => ['"kind":"value","path":"$.message"', '"kind":"value","path":"$.missing"'],
            'source map' => ['"source_pointer":"/views/1/body/0"', '"source_pointer":"/views/1/body/1"'],
        ];

        foreach ($mutations as $name => [$search, $replacement]) {
            $mutated = str_replace($search, $replacement, $metadata);
            self::assertNotSame($metadata, $mutated, $name);
            try {
                (new CompiledViewArtifactValidator())->validateStructure($mutated, $artifact->generatedPhpBytes());
                self::fail('Expected rejection for ' . $name);
            } catch (InvalidCompiledViewArtifactException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testRejectsNonCanonicalSyntaxInvalidAndForbiddenGeneratedPhp(): void
    {
        $artifact = BuildProbe::artifact();
        $pretty = json_encode(json_decode($artifact->metadataBytes(), true, flags: JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n";
        foreach ([$pretty, "\xEF\xBB\xBF" . $artifact->metadataBytes(), $artifact->metadataBytes() . "\n"] as $metadata) {
            try {
                (new CompiledViewArtifactValidator())->validateStructure($metadata, $artifact->generatedPhpBytes());
                self::fail('Expected noncanonical metadata rejection.');
            } catch (InvalidCompiledViewArtifactException) {
                self::addToAssertionCount(1);
            }
        }

        foreach ([
            str_replace('final class ', 'final class { ', $artifact->generatedPhpBytes()),
            str_replace("declare(strict_types=1);\n", "declare(strict_types=1);\neval('');\n", $artifact->generatedPhpBytes()),
        ] as $generated) {
            $metadata = str_replace($artifact->generatedPhpSha256(), hash('sha256', $generated), $artifact->metadataBytes());
            try {
                (new CompiledViewArtifactValidator())->validateStructure($metadata, $generated);
                self::fail('Expected generated PHP rejection.');
            } catch (InvalidCompiledViewArtifactException) {
                self::addToAssertionCount(1);
            }
        }
    }

    private static function changedDigest(string $digest): string
    {
        return ('0' === $digest[0] ? '1' : '0') . substr($digest, 1);
    }
}
