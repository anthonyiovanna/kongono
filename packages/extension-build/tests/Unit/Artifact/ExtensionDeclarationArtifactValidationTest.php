<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Artifact;

use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactValidator;
use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionDeclarationArtifactValidationTest extends TestCase
{
    public function testFreshCanonicalArtifactRoundTripsExactly(): void
    {
        $artifact = (new ExtensionDeclarationArtifactBuilder())->build([self::declaration()]);
        $validated = (new ExtensionDeclarationArtifactValidator())->validateStructure($artifact->canonicalBytes());

        self::assertSame($artifact->canonicalBytes(), $validated->canonicalBytes());
        self::assertSame($artifact->artifactSha256(), $validated->artifactSha256());
        self::assertTrue($artifact->declarationSetIdentity()->equals($validated->declarationSetIdentity()));
    }

    public function testMalformedEncodingJsonCanonicalityAndBoundsAreRejected(): void
    {
        $valid = (new ExtensionDeclarationArtifactBuilder())->build([])->canonicalBytes();
        $cases = [
            '',
            "\xEF\xBB\xBF" . $valid,
            str_replace("\n", "\r\n", $valid),
            rtrim($valid, "\n"),
            $valid . "\n",
            "{\"schema\":\xFF}\n",
            "{\"schema\":}\n",
            "{\"schema\":\"a\",\"schema\":\"b\"}\n",
            str_repeat('x', ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES + 1),
        ];
        foreach ($cases as $case) {
            try {
                (new ExtensionDeclarationArtifactValidator())->validateStructure($case);
                self::fail('Malformed artifact framing was accepted.');
            } catch (InvalidExtensionBuildException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EXTENSION-1209', 'KGN-EXTENSION-1212']);
            }
        }
    }

    public function testUnknownKeysWrongScalarsEnumsCountsDigestsAndDeclarationsAreRejected(): void
    {
        $artifact = (new ExtensionDeclarationArtifactBuilder())->build([self::declaration()]);
        /** @var array<string, mixed> $base */
        $base = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        $mutations = [];
        $changed = $base;
        $changed['unknown'] = true;
        $mutations[] = $changed;
        $changed = $base;
        $changed['schema'] = 'other';
        $mutations[] = $changed;
        $changed = $base;
        $changed['version'] = '1';
        $mutations[] = $changed;
        $changed = $base;
        $builder = $changed['builder'] ?? null;
        self::assertIsArray($builder);
        $builder['package'] = 'other';
        $changed['builder'] = $builder;
        $mutations[] = $changed;
        $changed = $base;
        $changed['declaration_count'] = 2;
        $mutations[] = $changed;
        $changed = $base;
        $changed['declaration_set_sha256'] = str_repeat('0', 64);
        $mutations[] = $changed;
        $changed = $base;
        $changed['declaration_set_identity'] = str_repeat('g', 64);
        $mutations[] = $changed;
        $changed = $base;
        $declarations = $changed['declarations'] ?? null;
        self::assertIsArray($declarations);
        $declaration = $declarations[0] ?? null;
        self::assertIsArray($declaration);
        $declaration['category'] = 'unknown';
        $declarations[0] = $declaration;
        $changed['declarations'] = $declarations;
        $mutations[] = $changed;
        $changed = $base;
        $declarations = $changed['declarations'] ?? null;
        self::assertIsArray($declarations);
        $declaration = $declarations[0] ?? null;
        self::assertIsArray($declaration);
        $declaration['identity'] = 'SECRET INVALID';
        $declarations[0] = $declaration;
        $changed['declarations'] = $declarations;
        $mutations[] = $changed;
        $changed = $base;
        $declarations = $changed['declarations'] ?? null;
        self::assertIsArray($declarations);
        $declaration = $declarations[0] ?? null;
        self::assertIsArray($declaration);
        $provenance = $declaration['provenance'] ?? null;
        self::assertIsArray($provenance);
        $provenance['extra'] = 'unknown';
        $declaration['provenance'] = $provenance;
        $declarations[0] = $declaration;
        $changed['declarations'] = $declarations;
        $mutations[] = $changed;

        foreach ($mutations as $mutation) {
            $bytes = self::encode($mutation);
            try {
                (new ExtensionDeclarationArtifactValidator())->validateStructure($bytes);
                self::fail('Malformed artifact structure was accepted.');
            } catch (InvalidExtensionBuildException $exception) {
                self::assertSame('KGN-EXTENSION-1209', $exception->diagnosticId());
                self::assertStringNotContainsString('SECRET INVALID', (string) $exception);
            }
        }
    }

    /** @param array<string, mixed> $value */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    private static function declaration(): ExtensionDeclaration
    {
        return new ExtensionDeclaration(
            ContractVersion::Version1,
            new ExtensionIdentity('example/extension'),
            new ExtensionVersion('v1'),
            ExtensionCategory::External,
            new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('1', 64)), str_repeat('2', 64)),
            ExtensionLifecycle::Operation,
            [],
            [],
        );
    }
}
