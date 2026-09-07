<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Artifact;

use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactValidator;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Exception\StaleInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Tests\Support\InvocationPlanSourceFactory;
use PHPUnit\Framework\TestCase;

final class InvocationPlanArtifactValidatorTest extends TestCase
{
    public function testStructureAndExactCurrentSourceValidate(): void
    {
        $source = InvocationPlanSourceFactory::create();
        $artifact = (new InvocationPlanArtifactBuilder())->build($source);
        $validator = new InvocationPlanArtifactValidator();
        $structured = $validator->validateStructure($artifact->canonicalBytes());
        self::assertSame($artifact->canonicalBytes(), $structured->canonicalBytes());
        self::assertSame($artifact->artifactSha256(), $structured->artifactSha256());
        self::assertSame($artifact->sourceSha256(), $structured->sourceSha256());
        self::assertSame($artifact->requestSha256(), $structured->requestSha256());
        self::assertSame($artifact->canonicalBytes(), $validator->validateCurrent($source, $artifact->canonicalBytes())->canonicalBytes());
    }

    public function testDifferentExplicitSourceIsStaleWithoutAmbientLookup(): void
    {
        $artifact = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create());
        $this->expectException(StaleInvocationPlanArtifactException::class);
        (new InvocationPlanArtifactValidator())->validateCurrent(InvocationPlanSourceFactory::create('changed message'), $artifact->canonicalBytes());
    }

    public function testMalformedReorderedExtraFloatVersionDigestAndCompatibilityFailClosed(): void
    {
        $valid = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create())->canonicalBytes();
        $data = json_decode($valid, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $cases = ['', $valid . "\n", str_replace('"artifact_version":1', '"artifact_version":2', $valid), str_replace('"max_attempts":1', '"max_attempts":1.0', $valid), str_replace('"source_sha256":"', '"source_sha256":"x', $valid)];
        $extra = $data;
        $extra['extra'] = true;
        $cases[] = self::encode($extra);
        $reordered = ['artifact_version' => 1] + $data;
        unset($reordered['artifact_version']);
        $reordered = ['artifact_version' => 1] + $reordered;
        $cases[] = self::encode($reordered);
        $incompatible = $data;
        self::assertIsArray($incompatible['plan']);
        self::assertIsArray($incompatible['plan']['profile']);
        $incompatible['plan']['profile']['capabilities'] = ['streaming'];
        $cases[] = self::encode($incompatible);
        foreach ($cases as $bytes) {
            try {
                (new InvocationPlanArtifactValidator())->validateStructure($bytes);
                self::fail('Expected invalid artifact rejection.');
            } catch (InvalidInvocationPlanArtifactException $failure) {
                self::assertSame('KGN-MODEL-BUILD-1414', $failure->diagnosticId());
            }
        }
    }

    /** @param array<mixed> $value */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
