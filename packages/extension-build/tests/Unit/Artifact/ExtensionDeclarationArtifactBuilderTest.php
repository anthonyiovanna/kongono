<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Artifact;

use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifact;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Contribution\ContributionSchema;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ExtensionDeclarationArtifactBuilderTest extends TestCase
{
    public function testEmptyAndFullyPopulatedArtifactsHaveExactCanonicalShapeAndIdentities(): void
    {
        $builder = new ExtensionDeclarationArtifactBuilder();
        $empty = $builder->build([]);
        self::assertSame(0, $empty->declarationCount());

        $artifact = $builder->build([self::declaration('example/extension', true)]);
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'schema',
            'version',
            'declaration_set_identity',
            'declaration_set_sha256',
            'builder',
            'declaration_count',
            'declarations',
        ], array_keys($decoded));
        $declarations = $decoded['declarations'] ?? null;
        self::assertIsArray($declarations);
        $declaration = $declarations[0] ?? null;
        self::assertIsArray($declaration);
        self::assertSame([
            'contract_version',
            'identity',
            'extension_version',
            'category',
            'provenance',
            'lifecycle',
            'capability_requests',
            'contributions',
        ], array_keys($declaration));
        $provenance = $declaration['provenance'] ?? null;
        self::assertIsArray($provenance);
        self::assertSame(['source_identity', 'source_sha256'], array_keys($provenance));
        $capabilityRequests = $declaration['capability_requests'] ?? null;
        self::assertIsArray($capabilityRequests);
        $capabilityRequest = $capabilityRequests[0] ?? null;
        self::assertIsArray($capabilityRequest);
        self::assertSame(['identity', 'version', 'purpose', 'requirement'], array_keys($capabilityRequest));
        $contributions = $declaration['contributions'] ?? null;
        self::assertIsArray($contributions);
        $contribution = $contributions[0] ?? null;
        self::assertIsArray($contribution);
        self::assertSame(['identity', 'schema', 'schema_version', 'visibility', 'source_sha256'], array_keys($contribution));
        self::assertSame('kongono.extension-declaration-artifact', $decoded['schema']);
        self::assertSame(['package' => 'kongono/extension-build', 'contract_version' => 1], $decoded['builder']);
        self::assertSame(hash('sha256', $artifact->canonicalBytes()), $artifact->artifactSha256());
        $setBytes = json_encode(['declarations' => $declarations], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        self::assertSame(hash('sha256', $setBytes), $artifact->declarationSetSha256());
        self::assertSame($artifact->declarationSetSha256(), $artifact->declarationSetIdentity()->value());
        self::assertStringEndsWith("\n", $artifact->canonicalBytes());
        self::assertFalse(str_ends_with($artifact->canonicalBytes(), "\n\n"));
        self::assertStringNotContainsString("\r", $artifact->canonicalBytes());
    }

    public function testBuildIsRepeatableAndDetachedFromTheCallerArray(): void
    {
        $declarations = [self::declaration('example/a')];
        $first = (new ExtensionDeclarationArtifactBuilder())->build($declarations);
        $declarations[] = self::declaration('example/b');
        $second = (new ExtensionDeclarationArtifactBuilder())->build([$declarations[0]]);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertSame(1, $first->declarationCount());
    }

    public function testMaximumDeclarationCountIsAcceptedAndExcessIsRejected(): void
    {
        $maximum = [];
        for ($index = 0; $index < 256; ++$index) {
            $maximum[] = self::declaration(sprintf('example/e%03d', $index));
        }
        self::assertSame(256, (new ExtensionDeclarationArtifactBuilder())->build($maximum)->declarationCount());

        $maximum[] = self::declaration('example/z');
        $this->expectException(InvalidExtensionBuildException::class);
        (new ExtensionDeclarationArtifactBuilder())->build($maximum);
    }

    public function testNonListWrongTypeDuplicateAndEveryBadOrderShapeRejectRatherThanNormalize(): void
    {
        $cases = [
            ['named' => self::declaration('example/a')],
            ['wrong'],
            [self::declaration('example/a'), self::declaration('example/a')],
            [self::declaration('example/b'), self::declaration('example/a')],
            [self::declaration('example/a'), self::declaration('example/c'), self::declaration('example/b')],
        ];
        foreach ($cases as $case) {
            try {
                self::invokeBuild($case);
                self::fail('Invalid declaration order or shape was accepted.');
            } catch (InvalidExtensionBuildException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EXTENSION-1208', 'KGN-EXTENSION-1212']);
            }
        }
    }

    /** @param array<array-key, mixed> $values */
    private static function invokeBuild(array $values): ExtensionDeclarationArtifact
    {
        $artifact = (new ReflectionMethod(ExtensionDeclarationArtifactBuilder::class, 'build'))->invoke(new ExtensionDeclarationArtifactBuilder(), $values);
        self::assertInstanceOf(ExtensionDeclarationArtifact::class, $artifact);

        return $artifact;
    }

    private static function declaration(string $identity, bool $withDetails = false): ExtensionDeclaration
    {
        return new ExtensionDeclaration(
            ContractVersion::Version1,
            new ExtensionIdentity($identity),
            new ExtensionVersion('v1'),
            ExtensionCategory::External,
            new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('1', 64)), str_repeat('2', 64)),
            ExtensionLifecycle::Operation,
            $withDetails ? [new CapabilityRequest(new CapabilityIdentity('filesystem.read', 1), new CapabilityPurpose('read.input'), CapabilityRequirement::Required)] : [],
            $withDetails ? [new ContributionDeclaration(new ContributionIdentity('route.data'), new ContributionSchema('route', 1), ContributionVisibility::Application, str_repeat('3', 64))] : [],
        );
    }
}
