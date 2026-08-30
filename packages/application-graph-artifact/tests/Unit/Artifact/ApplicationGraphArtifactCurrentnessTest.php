<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Artifact;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactValidator;
use Kongono\ApplicationGraphArtifact\Exception\StaleApplicationGraphArtifactException;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphArtifactCurrentnessTest extends TestCase
{
    public function testCurrentnessIsExactAndRelativeOnlyToSuppliedGraph(): void
    {
        $empty = (new ApplicationGraphBuilder())->build([], []);
        $bytes = (new ApplicationGraphArtifactBuilder())->build($empty)->canonicalBytes();
        self::assertSame($bytes, (new ApplicationGraphArtifactValidator())->validateCurrent($empty, $bytes)->canonicalBytes());
        try {
            (new ApplicationGraphArtifactValidator())->validateCurrent($this->oneNode(), $bytes);
            self::fail('Stale artifact accepted.');
        } catch (StaleApplicationGraphArtifactException $failure) {
            self::assertSame('graph_state_mismatch', $failure->diagnosticContext()->fields()[0]->value());
        }
    }

    private function oneNode(): AuthoritativeApplicationGraph
    {
        $provenance = new GraphProvenance(new ProducerIdentity(str_repeat('1', 64)), new SourceIdentity(str_repeat('2', 64)), ProducerContract::CompositionV2, DerivationVersion::Version1, str_repeat('3', 64));
        return (new ApplicationGraphBuilder())->build([new GraphNodeRecord(ContractVersion::Version1, new NodeIdentity(str_repeat('4', 64)), NodeType::CompositionContract, [new GraphFieldRecord(new FieldName('symbol'), 'Example\\Contract')], $provenance)], []);
    }
}
