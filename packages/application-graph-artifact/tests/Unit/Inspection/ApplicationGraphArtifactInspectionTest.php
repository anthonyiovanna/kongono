<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Inspection;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactValidator;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
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
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ApplicationGraphArtifactInspectionTest extends TestCase
{
    public function testEmptyGraphExposesExactIdentitiesVersionAndZeroVocabulary(): void
    {
        $artifact = (new ApplicationGraphArtifactBuilder())->build((new ApplicationGraphBuilder())->build([], []));
        $inspection = $artifact->inspection();

        self::assertSame($artifact->artifactSha256(), $inspection->artifactSha256());
        self::assertSame($artifact->graphIdentity(), $inspection->graphIdentity());
        self::assertSame(ContractVersion::Version1, $inspection->contractVersion());
        self::assertSame(0, $inspection->nodeCount());
        self::assertSame(0, $inspection->relationCount());
        foreach (NodeType::cases() as $type) {
            self::assertSame(0, $inspection->nodeTypeCount($type));
        }
        foreach (RelationType::cases() as $type) {
            self::assertSame(0, $inspection->relationTypeCount($type));
        }
    }

    public function testMixedGraphBuilderValidatorPermutationsAndInterleavingAreEquivalent(): void
    {
        [$nodes, $relations] = self::records();
        $graphBuilder = new ApplicationGraphBuilder();
        $first = (new ApplicationGraphArtifactBuilder())->build($graphBuilder->build($nodes, $relations));
        $empty = (new ApplicationGraphArtifactBuilder())->build($graphBuilder->build([], []));
        $reversed = (new ApplicationGraphArtifactBuilder())->build($graphBuilder->build(array_reverse($nodes), array_reverse($relations)));
        $validated = (new ApplicationGraphArtifactValidator())->validateStructure($first->canonicalBytes());
        $again = (new ApplicationGraphArtifactBuilder())->build($graphBuilder->build($nodes, $relations));

        self::assertSame(self::projection($first->inspection()), self::projection($validated->inspection()));
        self::assertSame(self::projection($first->inspection()), self::projection($reversed->inspection()));
        self::assertSame(self::projection($first->inspection()), self::projection($again->inspection()));
        self::assertSame(0, $empty->inspection()->nodeCount());
        self::assertSame([
            'nodes' => 5,
            'relations' => 4,
            'node_types' => [1, 2, 1, 1],
            'relation_types' => [1, 1, 1, 1],
        ], [
            'nodes' => $first->inspection()->nodeCount(),
            'relations' => $first->inspection()->relationCount(),
            'node_types' => array_map($first->inspection()->nodeTypeCount(...), NodeType::cases()),
            'relation_types' => array_map($first->inspection()->relationTypeCount(...), RelationType::cases()),
        ]);
    }

    public function testDirectConstructionRejectsImpossibleStateWithoutDisclosure(): void
    {
        $identity = new GraphIdentity(str_repeat('a', 64));
        $invalid = [
            ['sentinel-secret-digest', $identity, ContractVersion::Version1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [str_repeat('b', 64), $identity, ContractVersion::Version1, -1, 0, -1, 0, 0, 0, 0, 0, 0, 0],
            [str_repeat('b', 64), $identity, ContractVersion::Version1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [str_repeat('b', 64), $identity, ContractVersion::Version1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0],
        ];
        foreach ($invalid as $arguments) {
            try {
                new ApplicationGraphArtifactInspection(...$arguments);
                self::fail('Impossible inspection state must fail closed.');
            } catch (InvalidApplicationGraphArtifactException $failure) {
                self::assertStringNotContainsString('sentinel-secret', $failure->getMessage());
                self::assertStringNotContainsString('sentinel-secret', (string) $failure);
            }
        }
    }

    public function testSurfaceIsFinalReadonlyBoundedAndSummaryNeverReadsFields(): void
    {
        $reflection = new ReflectionClass(ApplicationGraphArtifactInspection::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        $methodNames = array_values(array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), static fn(ReflectionMethod $method): bool => '__construct' !== $method->getName()),
        ));
        sort($methodNames, SORT_STRING);
        self::assertSame([
            'artifactSha256',
            'contractVersion',
            'graphIdentity',
            'nodeCount',
            'nodeTypeCount',
            'relationCount',
            'relationTypeCount',
        ], $methodNames);
        foreach ($reflection->getProperties() as $property) {
            self::assertNotSame('Kongono\\ApplicationGraphBuild\\Graph\\AuthoritativeApplicationGraph', (string) $property->getType());
            self::assertSame([], $property->getAttributes(\AllowDynamicProperties::class));
        }
        foreach ([ApplicationGraphArtifactBuilder::class, ApplicationGraphArtifactValidator::class] as $type) {
            $method = new ReflectionMethod($type, 'inspection');
            $fileName = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            if (!is_string($fileName)) {
                self::fail('Inspection source filename must be available.');
            }
            if (!is_int($startLine) || !is_int($endLine) || $endLine < $startLine) {
                self::fail('Inspection source lines must form a valid non-empty range.');
            }
            $lines = file($fileName, FILE_IGNORE_NEW_LINES);
            self::assertIsArray($lines);
            $source = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
            self::assertStringNotContainsString('fields()', $source);
            self::assertStringNotContainsString('GraphField', $source);
        }
    }

    public function testSeparateProcessesProduceEquivalentInspection(): void
    {
        $autoload = str_replace('\\', '/', dirname(__DIR__, 5) . '/vendor/autoload.php');
        $script = 'require ' . var_export($autoload, true) . '; $a=(new Kongono\\ApplicationGraphArtifact\\Artifact\\ApplicationGraphArtifactBuilder())->build((new Kongono\\ApplicationGraphBuild\\Build\\ApplicationGraphBuilder())->build([], [])); echo json_encode([$a->inspection()->artifactSha256(),$a->inspection()->graphIdentity()->value(),$a->inspection()->nodeCount(),$a->inspection()->relationCount()], JSON_THROW_ON_ERROR);';
        $first = self::process([PHP_BINARY, '-r', $script]);
        $second = self::process([PHP_BINARY, '-r', $script]);
        self::assertSame(0, $first['exit']);
        self::assertSame('', $first['stderr']);
        self::assertSame($first, $second);
    }

    /** @return array{list<GraphNodeRecord>, list<GraphRelationRecord>} */
    private static function records(): array
    {
        return [[
            self::node('1', NodeType::CompositionInput, ['lifetime' => 'operation', 'symbol' => 'Input']),
            self::node('2', NodeType::CompositionService, ['class' => 'Service', 'lifetime' => 'operation']),
            self::node('3', NodeType::CompositionContract, ['symbol' => 'Contract']),
            self::node('4', NodeType::CompositionRoot, ['contract' => 'Contract', 'kind' => 'operation']),
            self::node('5', NodeType::CompositionService, ['class' => 'Dependency', 'lifetime' => 'application']),
        ], [
            self::relation('6', RelationType::DependsOn, '2', '5'),
            self::relation('7', RelationType::ConsumesInput, '2', '1'),
            self::relation('8', RelationType::ImplementsContract, '2', '3'),
            self::relation('9', RelationType::ExposesRoot, '4', '3'),
        ]];
    }

    /** @param array<string, string> $fields */
    private static function node(string $digit, NodeType $type, array $fields): GraphNodeRecord
    {
        $records = [];
        foreach ($fields as $name => $value) {
            $records[] = new GraphFieldRecord(new FieldName($name), $value);
        }

        return new GraphNodeRecord(ContractVersion::Version1, self::nodeIdentity($digit), $type, $records, self::provenance($digit));
    }

    private static function relation(string $digit, RelationType $type, string $source, string $target): GraphRelationRecord
    {
        return new GraphRelationRecord(ContractVersion::Version1, new RelationIdentity(str_repeat($digit, 64)), $type, self::nodeIdentity($source), self::nodeIdentity($target), [], self::provenance($digit));
    }

    private static function nodeIdentity(string $digit): NodeIdentity
    {
        return new NodeIdentity(str_repeat($digit, 64));
    }

    private static function provenance(string $digit): GraphProvenance
    {
        return new GraphProvenance(new ProducerIdentity(str_repeat('a', 64)), new SourceIdentity(str_repeat($digit, 64)), ProducerContract::CompositionV2, DerivationVersion::Version1, str_repeat('b', 64));
    }

    /** @return array<string, mixed> */
    private static function projection(ApplicationGraphArtifactInspection $inspection): array
    {
        return [
            'artifact' => $inspection->artifactSha256(),
            'graph' => $inspection->graphIdentity()->value(),
            'version' => $inspection->contractVersion()->value,
            'nodes' => $inspection->nodeCount(),
            'relations' => $inspection->relationCount(),
            'node_types' => array_map($inspection->nodeTypeCount(...), NodeType::cases()),
            'relation_types' => array_map($inspection->relationTypeCount(...), RelationType::cases()),
        ];
    }

    /**
     * @param list<string> $command
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private static function process(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        self::assertTrue(fclose($pipes[0]));
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        self::assertIsString($stdout);
        self::assertIsString($stderr);
        self::assertTrue(fclose($pipes[1]));
        self::assertTrue(fclose($pipes[2]));

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
