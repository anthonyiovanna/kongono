<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Tests\Unit\Admission;

use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionAdmission\Exception\UnresolvedExtensionConflictException;
use Kongono\ExtensionAdmission\Projection\ExtensionGraphProvenance;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
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

final class ExtensionAdmissionBuilderTest extends TestCase
{
    public function testEmptyAdmissionIsValidDeterministicAndAmbientFree(): void
    {
        $identity = (new ExtensionDeclarationArtifactBuilder())->build([])->declarationSetIdentity();
        $plan = new ExtensionAdmissionPlan($identity, [], []);
        $first = (new ExtensionAdmissionBuilder())->build([], $plan, []);
        $directory = getcwd();
        self::assertIsString($directory);
        try {
            chdir(sys_get_temp_dir());
            putenv('KONGONO_PHASE12D_SENTINEL=private');
            $second = (new ExtensionAdmissionBuilder())->build([], $plan, []);
        } finally {
            chdir($directory);
            putenv('KONGONO_PHASE12D_SENTINEL');
        }
        self::assertSame([], $first->nodeRecords());
        self::assertTrue($first->admissionIdentity()->equals($second->admissionIdentity()));
    }

    public function testStaleDeclarationSetFails(): void
    {
        $this->expectException(InvalidExtensionAdmissionException::class);
        (new ExtensionAdmissionBuilder())->build([], new ExtensionAdmissionPlan(new DeclarationSetIdentity(str_repeat('a', 64)), [], []), []);
    }

    public function testOneAttributableContributionProjectsFreshCanonicalIr(): void
    {
        $declaration = $this->declaration('acme/search', 'graph');
        $plan = $this->plan([$declaration]);
        $contribution = $declaration->contributions()[0];
        $provenance = ExtensionGraphProvenance::create($declaration->identity(), $declaration->version(), $contribution);
        $node = new GraphNodeRecord(GraphContractVersion::Version1, new NodeIdentity(str_repeat('1', 64)), NodeType::CompositionContract, [new GraphFieldRecord(new FieldName('symbol'), 'Acme\\Contract')], $provenance);
        $bundle = new ExtensionGraphContribution($declaration->identity(), $declaration->version(), $contribution->identity(), [$node], []);
        $result = (new ExtensionAdmissionBuilder())->build([$declaration], $plan, [$bundle]);
        self::assertStringEndsWith(str_repeat('1', 64), $result->nodeRecords()[0]->identity()->value());
        self::assertNotSame($node, $result->nodeRecords()[0]);
    }

    public function testConflictsRequireExactlyOneClaimantWinner(): void
    {
        $alpha = $this->declaration('acme/alpha', 'graph');
        $beta = $this->declaration('acme/beta', 'graph');
        $declarations = [$alpha, $beta];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $selections = [new ExtensionSelection($alpha->identity(), $alpha->version()), new ExtensionSelection($beta->identity(), $beta->version())];
        $bundles = [$this->emptyBundle($alpha), $this->emptyBundle($beta)];

        try {
            (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, $selections, []), $bundles);
            self::fail('Missing resolution was accepted.');
        } catch (UnresolvedExtensionConflictException $failure) {
            self::assertSame('KGN-EXTENSION-ADMISSION-1201', $failure->diagnosticId());
        }

        $resolution = new ContributionConflictResolution(new ContributionIdentity('graph'), $beta->identity());
        $result = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, $selections, [$resolution]), $bundles);
        self::assertCount(1, $result->appliedConflictResolutions());
        self::assertSame([], $result->nodeRecords());
    }

    public function testSelectionsBundlesAndSchemasFailClosed(): void
    {
        $declaration = $this->declaration('acme/search', 'graph');
        $identity = (new ExtensionDeclarationArtifactBuilder())->build([$declaration])->declarationSetIdentity();
        $builder = new ExtensionAdmissionBuilder();

        $this->assertInvalid(static fn() => $builder->build([$declaration], new ExtensionAdmissionPlan($identity, [new ExtensionSelection($declaration->identity(), new ExtensionVersion('v2'))], []), []));
        $this->assertInvalid(static fn() => $builder->build([$declaration], new ExtensionAdmissionPlan($identity, [new ExtensionSelection(new ExtensionIdentity('acme/unknown'), new ExtensionVersion('v1'))], []), []));
        $this->assertInvalid(static fn() => $builder->build([$declaration], new ExtensionAdmissionPlan($identity, [new ExtensionSelection($declaration->identity(), $declaration->version())], []), []));
        $this->assertInvalid(fn() => $builder->build([$declaration], new ExtensionAdmissionPlan($identity, [], []), [$this->emptyBundle($declaration)]));

        $unsupported = $this->declaration('acme/search', 'graph', 'acme.other-schema');
        $this->assertInvalid(fn() => $builder->build([$unsupported], $this->plan([$unsupported]), [$this->emptyBundle($unsupported)]));
        $wrongVersion = new ExtensionGraphContribution($declaration->identity(), new ExtensionVersion('v2'), $declaration->contributions()[0]->identity(), [], []);
        $this->assertInvalid(fn() => $builder->build([$declaration], $this->plan([$declaration]), [$wrongVersion]));
    }

    public function testTwoAndThreeClaimantsRequireOneExactSelectedWinner(): void
    {
        $declarations = [
            $this->declaration('acme/alpha', 'graph'),
            $this->declaration('acme/beta', 'graph'),
            $this->declaration('acme/gamma', 'graph'),
        ];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $selections = array_map(static fn(ExtensionDeclaration $declaration): ExtensionSelection => new ExtensionSelection($declaration->identity(), $declaration->version()), $declarations);
        $bundles = array_map($this->emptyBundle(...), $declarations);
        $resolution = new ContributionConflictResolution(new ContributionIdentity('graph'), $declarations[1]->identity());
        $result = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, $selections, [$resolution]), $bundles);

        self::assertCount(3, $result->admittedExtensions());
        self::assertSame($declarations[1]->identity(), $result->appliedConflictResolutions()[0]->winningExtensionIdentity());
    }

    public function testUnnecessaryUnknownAndNonclaimantResolutionsFail(): void
    {
        $alpha = $this->declaration('acme/alpha', 'graph');
        $beta = $this->declaration('acme/beta', 'graph');
        $singleIdentity = (new ExtensionDeclarationArtifactBuilder())->build([$alpha])->declarationSetIdentity();
        $singleSelection = [new ExtensionSelection($alpha->identity(), $alpha->version())];
        $builder = new ExtensionAdmissionBuilder();

        $this->assertConflict(fn() => $builder->build([$alpha], new ExtensionAdmissionPlan($singleIdentity, $singleSelection, [new ContributionConflictResolution(new ContributionIdentity('graph'), $alpha->identity())]), [$this->emptyBundle($alpha)]));
        $this->assertConflict(fn() => $builder->build([$alpha], new ExtensionAdmissionPlan($singleIdentity, $singleSelection, [new ContributionConflictResolution(new ContributionIdentity('unknown'), $alpha->identity())]), [$this->emptyBundle($alpha)]));

        $declarations = [$alpha, $beta];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $selections = [new ExtensionSelection($alpha->identity(), $alpha->version()), new ExtensionSelection($beta->identity(), $beta->version())];
        $resolution = new ContributionConflictResolution(new ContributionIdentity('graph'), new ExtensionIdentity('acme/outsider'));
        $this->assertConflict(fn() => $builder->build($declarations, new ExtensionAdmissionPlan($identity, $selections, [$resolution]), [$this->emptyBundle($alpha), $this->emptyBundle($beta)]));
    }

    public function testLosingBundlesAreValidatedBeforeTheyAreOmitted(): void
    {
        $alpha = $this->declaration('acme/alpha', 'graph');
        $beta = $this->declaration('acme/beta', 'graph');
        $badNode = new GraphNodeRecord(
            GraphContractVersion::Version1,
            new NodeIdentity(str_repeat('1', 64)),
            NodeType::CompositionContract,
            [new GraphFieldRecord(new FieldName('symbol'), 'Private\\Sentinel')],
            ExtensionGraphProvenance::create($alpha->identity(), $alpha->version(), $alpha->contributions()[0]),
        );
        $declarations = [$alpha, $beta];
        $resolution = new ContributionConflictResolution(new ContributionIdentity('graph'), $alpha->identity());
        $plan = new ExtensionAdmissionPlan(
            (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity(),
            [new ExtensionSelection($alpha->identity(), $alpha->version()), new ExtensionSelection($beta->identity(), $beta->version())],
            [$resolution],
        );

        $this->assertInvalid(fn() => (new ExtensionAdmissionBuilder())->build($declarations, $plan, [$this->emptyBundle($alpha), new ExtensionGraphContribution($beta->identity(), $beta->version(), $beta->contributions()[0]->identity(), [$badNode], [])]), 'Private\\Sentinel');
    }

    public function testWinningCrossBundleNodeRelationIdentityAndTupleConflictsFail(): void
    {
        $alpha = $this->declaration('acme/alpha', 'graph-a');
        $beta = $this->declaration('acme/beta', 'graph-b');
        $declarations = [$alpha, $beta];
        $plan = $this->plan($declarations);
        $builder = new ExtensionAdmissionBuilder();

        $sameAlphaNode = $this->contractNode($alpha, '1');
        $sameBetaNode = $this->contractNode($beta, '1');
        $this->assertInvalid(fn() => $builder->build($declarations, $plan, [$this->bundle($alpha, [$sameAlphaNode], []), $this->bundle($beta, [$sameBetaNode], [])]));

        $contract = $this->contractNode($alpha, '1');
        $service = $this->serviceNode($alpha, '2');
        $alphaRelation = $this->relation($alpha, '4', '2', '1');
        $betaSameIdentity = $this->relation($beta, '4', '2', '1');
        $this->assertInvalid(fn() => $builder->build($declarations, $plan, [$this->bundle($alpha, [$contract, $service], [$alphaRelation]), $this->bundle($beta, [], [$betaSameIdentity])]));

        $betaSameTuple = $this->relation($beta, '5', '2', '1');
        $this->assertInvalid(fn() => $builder->build($declarations, $plan, [$this->bundle($alpha, [$contract, $service], [$alphaRelation]), $this->bundle($beta, [], [$betaSameTuple])]));
    }

    public function testGraphAdmissionFailurePreservesCauseWithoutDisclosingFields(): void
    {
        $declaration = $this->declaration('acme/search', 'graph');
        $provenance = ExtensionGraphProvenance::create($declaration->identity(), $declaration->version(), $declaration->contributions()[0]);
        $node = new GraphNodeRecord(GraphContractVersion::Version1, new NodeIdentity(str_repeat('1', 64)), NodeType::CompositionContract, [new GraphFieldRecord(new FieldName('unknown'), 'private-field-sentinel')], $provenance);

        try {
            (new ExtensionAdmissionBuilder())->build([$declaration], $this->plan([$declaration]), [$this->bundle($declaration, [$node], [])]);
            self::fail('Invalid Graph schema was accepted.');
        } catch (InvalidExtensionAdmissionException $failure) {
            self::assertInstanceOf(InvalidGraphBuildException::class, $failure->getPrevious());
            self::assertStringNotContainsString('private-field-sentinel', (string) $failure);
            self::assertStringNotContainsString('private-field-sentinel', $failure->getMessage());
        }
    }

    /** @param list<ExtensionDeclaration> $declarations */
    private function plan(array $declarations): ExtensionAdmissionPlan
    {
        return new ExtensionAdmissionPlan(
            (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity(),
            array_map(static fn(ExtensionDeclaration $declaration): ExtensionSelection => new ExtensionSelection($declaration->identity(), $declaration->version()), $declarations),
            [],
        );
    }

    private function declaration(string $identity, string $contribution, string $schema = 'kongono.application-graph-ir'): ExtensionDeclaration
    {
        return new ExtensionDeclaration(
            ContractVersion::Version1,
            new ExtensionIdentity($identity),
            new ExtensionVersion('v1'),
            ExtensionCategory::TrustedInProcess,
            new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('b', 64)), str_repeat('c', 64)),
            ExtensionLifecycle::Application,
            [],
            [new ContributionDeclaration(new ContributionIdentity($contribution), new ContributionSchema($schema, 1), ContributionVisibility::Application, str_repeat('d', 64))],
        );
    }

    private function emptyBundle(ExtensionDeclaration $declaration): ExtensionGraphContribution
    {
        return new ExtensionGraphContribution($declaration->identity(), $declaration->version(), $declaration->contributions()[0]->identity(), [], []);
    }

    /**
     * @param list<GraphNodeRecord> $nodes
     * @param list<GraphRelationRecord> $relations
     */
    private function bundle(ExtensionDeclaration $declaration, array $nodes, array $relations): ExtensionGraphContribution
    {
        return new ExtensionGraphContribution($declaration->identity(), $declaration->version(), $declaration->contributions()[0]->identity(), $nodes, $relations);
    }

    private function contractNode(ExtensionDeclaration $declaration, string $seed): GraphNodeRecord
    {
        return new GraphNodeRecord(GraphContractVersion::Version1, new NodeIdentity(str_repeat($seed, 64)), NodeType::CompositionContract, [new GraphFieldRecord(new FieldName('symbol'), 'Contract')], ExtensionGraphProvenance::create($declaration->identity(), $declaration->version(), $declaration->contributions()[0]));
    }

    private function serviceNode(ExtensionDeclaration $declaration, string $seed): GraphNodeRecord
    {
        return new GraphNodeRecord(GraphContractVersion::Version1, new NodeIdentity(str_repeat($seed, 64)), NodeType::CompositionService, [new GraphFieldRecord(new FieldName('class'), 'Service'), new GraphFieldRecord(new FieldName('lifetime'), 'operation')], ExtensionGraphProvenance::create($declaration->identity(), $declaration->version(), $declaration->contributions()[0]));
    }

    private function relation(ExtensionDeclaration $declaration, string $seed, string $source, string $target): GraphRelationRecord
    {
        return new GraphRelationRecord(GraphContractVersion::Version1, new RelationIdentity(str_repeat($seed, 64)), RelationType::ImplementsContract, new NodeIdentity(str_repeat($source, 64)), new NodeIdentity(str_repeat($target, 64)), [], ExtensionGraphProvenance::create($declaration->identity(), $declaration->version(), $declaration->contributions()[0]));
    }

    /** @param callable(): mixed $operation */
    private function assertInvalid(callable $operation, ?string $privateSentinel = null): void
    {
        try {
            $operation();
            self::fail('Invalid admission was accepted.');
        } catch (InvalidExtensionAdmissionException $failure) {
            self::assertSame('KGN-EXTENSION-ADMISSION-1201', $failure->diagnosticId());
            if (null !== $privateSentinel) {
                self::assertStringNotContainsString($privateSentinel, (string) $failure);
            }
        }
    }

    /** @param callable(): mixed $operation */
    private function assertConflict(callable $operation): void
    {
        try {
            $operation();
            self::fail('Invalid conflict resolution was accepted.');
        } catch (UnresolvedExtensionConflictException $failure) {
            self::assertSame('KGN-EXTENSION-ADMISSION-1201', $failure->diagnosticId());
        }
    }
}
