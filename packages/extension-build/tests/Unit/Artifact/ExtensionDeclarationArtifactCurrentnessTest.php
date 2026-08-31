<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Artifact;

use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactValidator;
use Kongono\ExtensionBuild\Exception\StaleExtensionDeclarationArtifactException;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionDeclarationArtifactCurrentnessTest extends TestCase
{
    public function testCurrentnessUsesOnlyTheExactCallerSuppliedList(): void
    {
        $current = [self::declaration('example/a')];
        $bytes = (new ExtensionDeclarationArtifactBuilder())->build($current)->canonicalBytes();
        $validated = (new ExtensionDeclarationArtifactValidator())->validateCurrent($current, $bytes);

        self::assertSame($bytes, $validated->canonicalBytes());
        self::assertSame(1, $validated->declarationCount());
    }

    public function testStaleCallerRelativeInputRejectsWithoutDisclosingEitherIdentity(): void
    {
        $bytes = (new ExtensionDeclarationArtifactBuilder())->build([self::declaration('example/a')])->canonicalBytes();
        try {
            (new ExtensionDeclarationArtifactValidator())->validateCurrent([self::declaration('private/current')], $bytes);
            self::fail('Stale declaration artifact was accepted.');
        } catch (StaleExtensionDeclarationArtifactException $exception) {
            self::assertSame('KGN-EXTENSION-1210', $exception->diagnosticId());
            self::assertStringNotContainsString('example/a', (string) $exception);
            self::assertStringNotContainsString('private/current', (string) $exception);
        }
    }

    private static function declaration(string $identity): ExtensionDeclaration
    {
        return new ExtensionDeclaration(
            ContractVersion::Version1,
            new ExtensionIdentity($identity),
            new ExtensionVersion('v1'),
            ExtensionCategory::External,
            new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('1', 64)), str_repeat('2', 64)),
            ExtensionLifecycle::Operation,
            [],
            [],
        );
    }
}
