<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Contribution;

use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionGraphContributionTest extends TestCase
{
    public function testCanonicalRecordsAreDetachedAndPreserved(): void
    {
        $record = $this->node('a');
        $bundle = new ExtensionGraphContribution(new ExtensionIdentity('acme/search'), new ExtensionVersion('v1'), new ContributionIdentity('graph'), [$record], []);
        self::assertSame([$record], $bundle->nodeRecords());
        $detached = $bundle->nodeRecords();
        array_pop($detached);
        self::assertCount(1, $bundle->nodeRecords());
    }

    public function testReorderedRecordsFail(): void
    {
        $this->expectException(InvalidExtensionAdmissionException::class);
        new ExtensionGraphContribution(new ExtensionIdentity('acme/search'), new ExtensionVersion('v1'), new ContributionIdentity('graph'), [$this->node('b'), $this->node('a')], []);
    }

    public function testNodeListsRejectNonListsWrongTypesDuplicatesAndOversize(): void
    {
        $node = $this->node('a');
        foreach ([['node' => $node], ['raw'], [$node, $node], array_fill(0, 257, $node)] as $records) {
            $this->expectBundleFailure($records, []);
        }
    }

    public function testRelationListsRejectNonListsWrongTypesDuplicatesReorderingAndOversize(): void
    {
        $alpha = $this->relation('a');
        $beta = $this->relation('b');
        foreach ([['relation' => $alpha], ['raw'], [$alpha, $alpha], [$beta, $alpha], array_fill(0, 1025, $alpha)] as $records) {
            $this->expectBundleFailure([], $records);
        }
    }

    /**
     * @param array<array-key, mixed> $nodes
     * @param array<array-key, mixed> $relations
     */
    private function expectBundleFailure(array $nodes, array $relations): void
    {
        try {
            new ExtensionGraphContribution(new ExtensionIdentity('acme/search'), new ExtensionVersion('v1'), new ContributionIdentity('graph'), $nodes, $relations);
            self::fail('Malformed contribution bundle was accepted.');
        } catch (InvalidExtensionAdmissionException $failure) {
            self::assertSame('KGN-EXTENSION-ADMISSION-1201', $failure->diagnosticId());
        }
    }

    private function node(string $seed): GraphNodeRecord
    {
        return new GraphNodeRecord(ContractVersion::Version1, new NodeIdentity(str_repeat($seed, 64)), NodeType::CompositionContract, [], new GraphProvenance(new ProducerIdentity(str_repeat('a', 64)), new SourceIdentity(str_repeat('b', 64)), ProducerContract::ExtensionV1, DerivationVersion::Version1, str_repeat('c', 64)));
    }

    private function relation(string $seed): GraphRelationRecord
    {
        return new GraphRelationRecord(
            ContractVersion::Version1,
            new RelationIdentity(str_repeat($seed, 64)),
            RelationType::DependsOn,
            new NodeIdentity(str_repeat('1', 64)),
            new NodeIdentity(str_repeat('2', 64)),
            [],
            new GraphProvenance(new ProducerIdentity(str_repeat('a', 64)), new SourceIdentity(str_repeat('b', 64)), ProducerContract::ExtensionV1, DerivationVersion::Version1, str_repeat('c', 64)),
        );
    }
}
