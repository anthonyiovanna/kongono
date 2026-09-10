<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Generated;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidGeneratedExternalServiceBindingsException;
use Kongono\ExternalServiceBindingBuild\Exception\StaleGeneratedExternalServiceBindingsException;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsGenerator;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsValidator;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class GeneratedExternalServiceBindingsValidatorTest extends TestCase
{
    public function testStructureReemitsWithoutExecutionAndCurrentnessUsesExplicitInputs(): void
    {
        $source = ExternalServiceBindingSourceFactory::create();
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build($source);
        $generated = (new GeneratedExternalServiceBindingsGenerator())->generate($artifact);
        $validator = new GeneratedExternalServiceBindingsValidator();
        self::assertSame($generated->canonicalBytes(), $validator->validateStructure($generated->canonicalBytes())->canonicalBytes());
        self::assertSame($generated->generatedPhpSha256(), $validator->validateCurrent($source, $artifact, $generated->canonicalBytes())->generatedPhpSha256());
        $this->expectException(StaleGeneratedExternalServiceBindingsException::class);
        $validator->validateCurrent(ExternalServiceBindingSourceFactory::create('changed.bindings'), $artifact, $generated->canonicalBytes());
    }

    public function testInjectionExtraTruncationFramingAndMetadataCorruptionAreRejected(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $bytes = (new GeneratedExternalServiceBindingsGenerator())->generate($artifact)->canonicalBytes();
        preg_match('/\/\/ (\{[^\n]+\})\n/', $bytes, $matches);
        $metadataJson = $matches[1] ?? null;
        self::assertIsString($metadataJson);
        $metadata = json_decode($metadataJson, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($metadata);
        $metadata['source_sha256'] = str_repeat('0', 64);
        $corrupt = str_replace($metadataJson, json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $bytes);
        $cases = [$bytes . '<?php echo 1;', substr($bytes, 0, -20), "\xEF\xBB\xBF" . $bytes, str_replace("\n", "\r\n", $bytes), $bytes . "\0", $corrupt];
        foreach ($cases as $case) {
            try {
                (new GeneratedExternalServiceBindingsValidator())->validateStructure($case);
                self::fail('Expected generated PHP rejection.');
            } catch (InvalidGeneratedExternalServiceBindingsException $failure) {
                self::assertStringNotContainsString('fixtures/alpha', (string) $failure);
            }
        }
    }
}
