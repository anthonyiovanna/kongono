<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Tests\Unit\Provenance;

use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Provenance\DerivationVersion;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Provenance\ProducerContract;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GraphProvenanceTest extends TestCase
{
    public function testProvenancePreservesExactlyFiveAuthorizedFields(): void
    {
        $producer = new ProducerIdentity(str_repeat('1', 64));
        $source = new SourceIdentity(str_repeat('2', 64));
        $digest = str_repeat('3', 64);
        $provenance = new GraphProvenance(
            $producer,
            $source,
            ProducerContract::CompositionV2,
            DerivationVersion::Version1,
            $digest,
        );

        self::assertSame($producer, $provenance->producerIdentity());
        self::assertSame($source, $provenance->sourceIdentity());
        self::assertSame(ProducerContract::CompositionV2, $provenance->producerContract());
        self::assertSame(DerivationVersion::Version1, $provenance->derivationVersion());
        self::assertSame($digest, $provenance->sourceSha256());

        $reflection = new ReflectionClass($provenance);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        $properties = array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            $reflection->getProperties(),
        );
        sort($properties);
        self::assertSame(
            ['derivationVersion', 'producerContract', 'producerIdentity', 'sourceIdentity', 'sourceSha256'],
            $properties,
        );
    }

    public function testMalformedSourceDigestFailsWithoutDisclosure(): void
    {
        $rejected = 'C:\\secret\\source.json';

        try {
            new GraphProvenance(
                new ProducerIdentity(str_repeat('1', 64)),
                new SourceIdentity(str_repeat('2', 64)),
                ProducerContract::CompositionV2,
                DerivationVersion::Version1,
                $rejected,
            );
            self::fail('Malformed provenance was accepted.');
        } catch (InvalidGraphContractException $exception) {
            self::assertSame('KGN-GRAPH-1104', $exception->diagnosticId());
            self::assertStringNotContainsString($rejected, $exception->getMessage());
            self::assertStringNotContainsString($rejected, (string) $exception);
        }
    }
}
