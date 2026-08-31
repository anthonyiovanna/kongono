<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Projection;

use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ExtensionAdmission\Projection\ExtensionGraphProvenance;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Contribution\ContributionSchema;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionGraphProvenanceTest extends TestCase
{
    public function testExactProducerAndSourceDigestsAreDeterministic(): void
    {
        $identity = new ExtensionIdentity('acme/search');
        $version = new ExtensionVersion('v1');
        $declaration = new ContributionDeclaration(new ContributionIdentity('graph'), new ContributionSchema('kongono.application-graph-ir', 1), ContributionVisibility::Application, str_repeat('a', 64));
        $provenance = ExtensionGraphProvenance::create($identity, $version, $declaration);
        self::assertSame('kgn:graph:v1:producer:' . hash('sha256', "kongono.extension-graph-producer.v1\0acme/search\0v1"), $provenance->producerIdentity()->value());
        self::assertSame('kgn:graph:v1:source:' . hash('sha256', "kongono.extension-graph-source.v1\0acme/search\0v1\0graph\0" . str_repeat('a', 64)), $provenance->sourceIdentity()->value());
        self::assertSame(ProducerContract::ExtensionV1, $provenance->producerContract());
        self::assertSame(DerivationVersion::Version1, $provenance->derivationVersion());
        self::assertSame(str_repeat('a', 64), $provenance->sourceSha256());
        self::assertTrue(ExtensionGraphProvenance::matches($provenance, $identity, $version, $declaration));
    }

    public function testEveryRepresentableProvenanceMismatchDimensionFails(): void
    {
        $identity = new ExtensionIdentity('acme/search');
        $version = new ExtensionVersion('v1');
        $declaration = new ContributionDeclaration(new ContributionIdentity('graph'), new ContributionSchema('kongono.application-graph-ir', 1), ContributionVisibility::Application, str_repeat('a', 64));
        $expected = ExtensionGraphProvenance::create($identity, $version, $declaration);
        $mismatches = [
            new GraphProvenance(new ProducerIdentity(str_repeat('b', 64)), $expected->sourceIdentity(), ProducerContract::ExtensionV1, DerivationVersion::Version1, $expected->sourceSha256()),
            new GraphProvenance($expected->producerIdentity(), new SourceIdentity(str_repeat('b', 64)), ProducerContract::ExtensionV1, DerivationVersion::Version1, $expected->sourceSha256()),
            new GraphProvenance($expected->producerIdentity(), $expected->sourceIdentity(), ProducerContract::CompositionV2, DerivationVersion::Version1, $expected->sourceSha256()),
            new GraphProvenance($expected->producerIdentity(), $expected->sourceIdentity(), ProducerContract::ExtensionV1, DerivationVersion::Version1, str_repeat('b', 64)),
        ];

        foreach ($mismatches as $mismatch) {
            self::assertFalse(ExtensionGraphProvenance::matches($mismatch, $identity, $version, $declaration));
        }
    }
}
