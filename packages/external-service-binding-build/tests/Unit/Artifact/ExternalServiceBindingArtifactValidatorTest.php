<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Artifact;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactValidator;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Exception\StaleExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingArtifactValidatorTest extends TestCase
{
    public function testStructureReconstructsAndCurrentnessUsesOnlyExplicitSource(): void
    {
        $source = ExternalServiceBindingSourceFactory::create();
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build($source);
        $validator = new ExternalServiceBindingArtifactValidator();
        self::assertSame($artifact->canonicalBytes(), $validator->validateStructure($artifact->canonicalBytes())->canonicalBytes());
        self::assertSame($artifact->artifactSha256(), $validator->validateCurrent($source, $artifact->canonicalBytes())->artifactSha256());
        $this->expectException(StaleExternalServiceBindingArtifactException::class);
        $validator->validateCurrent(ExternalServiceBindingSourceFactory::create('changed.bindings'), $artifact->canonicalBytes());
    }

    public function testMalformedFramingShapeTypesKeysAndDigestsAreRejectedWithoutDisclosure(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $data = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $unknown = $data;
        $unknown['unknown'] = true;
        $missing = $data;
        unset($missing['source_sha256']);
        $wrongOrder = ['artifact_version' => 1] + $data;
        $wrongType = $data;
        $wrongType['artifact_version'] = '1';
        $badDigest = $data;
        $badDigest['source_sha256'] = str_repeat('0', 64);
        $cases = ['', "\xEF\xBB\xBF" . $artifact->canonicalBytes(), str_replace("\n", "\r\n", $artifact->canonicalBytes()), $artifact->canonicalBytes() . "\0", rtrim($artifact->canonicalBytes(), "\n"), $artifact->canonicalBytes() . "\n", '{broken}' . "\n"];
        foreach ([$unknown, $missing, $wrongOrder, $wrongType, $badDigest] as $case) {
            $cases[] = json_encode($case, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        }
        foreach ($cases as $bytes) {
            try {
                (new ExternalServiceBindingArtifactValidator())->validateStructure($bytes);
                self::fail('Expected invalid artifact rejection.');
            } catch (InvalidExternalServiceBindingArtifactException $failure) {
                self::assertStringNotContainsString('api.example.test', $failure->getMessage());
                self::assertStringNotContainsString('fixtures/alpha', (string) $failure);
            }
        }
    }
}
