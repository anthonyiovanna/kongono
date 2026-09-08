<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Artifact;

use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Exception\StaleEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Tests\Support\EgressPolicySourceFactory;
use PHPUnit\Framework\TestCase;

final class EgressPolicyArtifactValidatorTest extends TestCase
{
    public function testStructureCurrentnessAndDeterministicRebuildAreExact(): void
    {
        $source = EgressPolicySourceFactory::create();
        $artifact = (new EgressPolicyArtifactBuilder())->build($source);
        $validator = new EgressPolicyArtifactValidator();
        $structured = $validator->validateStructure($artifact->canonicalBytes());
        self::assertSame($artifact->canonicalBytes(), $structured->canonicalBytes());
        self::assertSame($artifact->artifactSha256(), $structured->artifactSha256());
        self::assertSame($artifact->sourceSha256(), $structured->sourceSha256());
        self::assertSame($artifact->canonicalBytes(), $validator->validateCurrent($source, $artifact->canonicalBytes())->canonicalBytes());
    }

    public function testDifferentExplicitSourceIsStaleRatherThanStructurallyInvalid(): void
    {
        $artifact = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create());
        (new EgressPolicyArtifactValidator())->validateStructure($artifact->canonicalBytes());
        $this->expectException(StaleEgressPolicyArtifactException::class);
        (new EgressPolicyArtifactValidator())->validateCurrent(EgressPolicySourceFactory::create(identity: 'application.changed'), $artifact->canonicalBytes());
    }

    public function testMalformedFramingShapeTypesValuesDigestsAndEncodingsFailClosed(): void
    {
        $valid = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create())->canonicalBytes();
        $data = json_decode($valid, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $unknown = $data;
        $unknown['unknown'] = true;
        $missing = $data;
        unset($missing['builder']);
        $wrongType = $data;
        $wrongType['artifact_version'] = '1';
        $wrongValue = $data;
        $wrongValue['artifact_version'] = 2;
        $invalidDigest = $data;
        $invalidDigest['source_sha256'] = str_repeat('A', 64);
        $reordered = ['artifact_version' => 1] + $data;
        unset($reordered['artifact_version']);
        $reordered = ['artifact_version' => 1] + $reordered;
        $cases = [
            '', substr($valid, 0, -1), $valid . "\n", "\xEF\xBB\xBF" . $valid, str_replace("\n", "\r\n", $valid),
            substr($valid, 0, 1) . "\0" . substr($valid, 1), "\xFF\n", str_repeat('x', 65_537),
            self::encode($unknown), self::encode($missing), self::encode($wrongType), self::encode($wrongValue),
            self::encode($invalidDigest), self::encode($reordered), json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n",
            str_replace('{"schema":', '{"schema":"duplicate","schema":', $valid),
        ];
        foreach ($cases as $bytes) {
            try {
                (new EgressPolicyArtifactValidator())->validateStructure($bytes);
                self::fail('Expected invalid artifact rejection.');
            } catch (InvalidEgressPolicyArtifactException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** @param array<mixed> $value */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
