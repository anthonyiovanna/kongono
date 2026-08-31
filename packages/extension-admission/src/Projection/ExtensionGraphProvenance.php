<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Derives and checks the sole attributable provenance accepted for extension Graph IR. */
#[Api(ApiStatus::Experimental)]
final class ExtensionGraphProvenance
{
    public static function create(
        ExtensionIdentity $extensionIdentity,
        ExtensionVersion $extensionVersion,
        ContributionDeclaration $contribution,
    ): GraphProvenance {
        $extension = $extensionIdentity->value();
        $version = $extensionVersion->value();
        $contributionIdentity = $contribution->identity()->value();
        $sourceSha256 = $contribution->sourceSha256();

        return new GraphProvenance(
            new ProducerIdentity(hash('sha256', "kongono.extension-graph-producer.v1\0{$extension}\0{$version}")),
            new SourceIdentity(hash('sha256', "kongono.extension-graph-source.v1\0{$extension}\0{$version}\0{$contributionIdentity}\0{$sourceSha256}")),
            ProducerContract::ExtensionV1,
            DerivationVersion::Version1,
            $sourceSha256,
        );
    }

    public static function matches(
        GraphProvenance $actual,
        ExtensionIdentity $extensionIdentity,
        ExtensionVersion $extensionVersion,
        ContributionDeclaration $contribution,
    ): bool {
        $expected = self::create($extensionIdentity, $extensionVersion, $contribution);

        return $actual->producerIdentity()->value() === $expected->producerIdentity()->value()
            && $actual->sourceIdentity()->value() === $expected->sourceIdentity()->value()
            && $actual->producerContract() === ProducerContract::ExtensionV1
            && hash_equals($actual->sourceSha256(), $contribution->sourceSha256());
    }
}
