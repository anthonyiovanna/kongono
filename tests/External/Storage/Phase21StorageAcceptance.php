<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Storage;

use FilesystemIterator;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\Process\NativeKongoEntrypoint;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\ObservabilityContext;
use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageAdapterContract\AdapterContractVersion;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageAdapterLocal\Exception\InvalidLocalStorageConfigurationException;
use Kongono\StorageAdapterLocal\Exception\LocalStorageAdapterException;
use Kongono\StorageApplication\Authorization\StorageOperationAuthorizer;
use Kongono\StorageApplication\Definition\StorageApplicationDefinition;
use Kongono\StorageApplication\Exception\StorageApplicationException;
use Kongono\StorageApplication\Exception\StorageApplicationInvariantException;
use Kongono\StorageApplication\Exception\StorageApplicationWorkflowException;
use Kongono\StorageApplication\Exception\StorageAuthorizationDeniedException;
use Kongono\StorageApplication\Exception\StorageAuthorizationFailureException;
use Kongono\StorageApplication\Exception\StorageRuntimeCompositionException;
use Kongono\StorageApplication\Runtime\AuthorizedStorageRuntime;
use Kongono\StorageApplication\Runtime\StorageApplicationRuntimeFactory;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageApplication\Workflow\StorageApplicationWorkflow;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactPublisher;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Exception\StaleStorageProfileArtifactException;
use Kongono\StorageBuild\Exception\StorageBuildException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Pagination\StorageContinuationToken;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListPage;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSink;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageContract\Version\StorageObjectVersion;
use Kongono\StorageInspection\Derivation\StorageIntelligenceDeriver;
use Kongono\StorageInspection\Exception\InvalidStorageInspectionException;
use Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException;
use Kongono\StorageInspection\Exception\StorageInspectionBindingException;
use Kongono\StorageInspection\Exception\StorageInspectionException;
use Kongono\StorageInspection\Exception\StorageInspectionProjectionException;
use Kongono\StorageInspection\Exception\StorageInspectionVerificationException;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;
use Kongono\StorageInspection\Projection\StorageObservationProjector;
use Kongono\StorageInspection\Projection\StorageSecurityAuditProjector;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;
use Kongono\StorageInspection\RuntimeEvidence\DetachedStorageRuntimeEvidence;
use Kongono\StorageInspection\Verification\StorageIntelligenceVerdict;
use Kongono\StorageInspection\Verification\StorageIntelligenceVerifier;
use Kongono\StorageRuntime\Binding\StorageRuntimeBinding;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use Kongono\StorageRuntime\Exception\StorageRuntimeException;
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationAndCleanupException;
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationException;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use TypeError;

/** Root-owned, local-only Phase 21 acceptance. Public output is intentionally closed. */
final class Phase21StorageAcceptance
{
    private const SUCCESS = '{"schema":"kongono.phase-21-storage-acceptance","version":1,"phase":"21","profile":"storage-platform-local/1","status":"accepted","checks":["contract-build","publication","authorization","local-operations","outcome-truth","stream-lifecycle","runtime-isolation","application-parity","inspection","nondisclosure","process-integrity","cleanup"],"check_count":12}';
    private const FAILURE = "Kongono Phase 21 external acceptance failed.\n";
    private const STREAM_LIMIT = 1_048_576;
    private const RAW_LIMIT = self::STREAM_LIMIT + 8192;
    private const PROCESS_TIMEOUT_SECONDS = 180;

    /** @var list<resource> */
    private static array $uncertainProcesses = [];

    private int $cases = 0;
    private ?TemporaryStorageWorkspace $workspace = null;
    /** @var array<string, bool> */
    private array $captures = [];

    public function __construct(private readonly string $repositoryRoot) {}

    /** @param list<string> $arguments
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        if (['--capture-self-test'] === $arguments) {
            try {
                $this->workspace = new TemporaryStorageWorkspace($this->repositoryRoot);
                $this->captureSelfTest();
                $this->cleanup();
                $this->recordCaseCount('self-test');
                fwrite($stdout, "capture-self-test-ok\n");
                return 0;
            } catch (Throwable $failure) {
                $this->retainFailure($failure);
                fwrite($stderr, self::FAILURE);
                return 1;
            }
        }
        if (1 === count($arguments)) {
            return $this->childMode($arguments[0], $stdout, $stderr);
        }
        if ([] !== $arguments) {
            fwrite($stderr, self::FAILURE);
            return 1;
        }
        try {
            $repositoryBefore = $this->repositoryFingerprint();
            $this->workspace = new TemporaryStorageWorkspace($this->repositoryRoot);
            $this->contractBuild();
            $this->publication();
            $this->authorization();
            $this->localOperations();
            $this->outcomeTruth();
            $this->streamLifecycle();
            $this->runtimeIsolation();
            $this->applicationParity();
            $this->inspection();
            $this->nondisclosure();
            $this->processIntegrity();
            $this->cleanup();
            $this->check($repositoryBefore === $this->repositoryFingerprint());
            $this->recordCaseCount('acceptance');
            fwrite($stdout, self::SUCCESS . "\n");
            return 0;
        } catch (Throwable $failure) {
            $this->retainFailure($failure);
            fwrite($stderr, self::FAILURE);
            return 1;
        }
    }

    private function retainFailure(Throwable $failure): void
    {
        if (null !== $this->workspace) {
            $detail = get_class($failure) . "\n" . substr($failure->getMessage(), 0, 4096) . "\n" . $this->cases . "\n";
            try {
                $this->workspace->write('capture/failure.txt', $detail);
            } catch (Throwable) {
                // The original failure and all existing capture files remain the evidence.
            }
        }
    }

    /** @param resource $stdout
     * @param resource $stderr
     */
    private function childMode(string $mode, $stdout, $stderr): int
    {
        switch ($mode) {
            case '--child-capture-ok':
                fwrite($stdout, "capture-ok\n");
                return 0;
            case '--child-success':
                $build = (new StorageProfileBuilder())->build(self::profiles());
                $artifact = $build->artifact();
                fwrite($stdout, json_encode(['status' => 'ok', 'source' => $build->sourceIdentity()->value(), 'build' => $build->buildIdentity()->value(), 'artifact' => $artifact->artifactIdentity()->value(), 'bytes_base64' => base64_encode($artifact->bytes())], JSON_THROW_ON_ERROR) . "\n");
                return 0;
            case '--child-failure':
                fwrite($stderr, "controlled-child-failure\n");
                return 17;
            case '--child-malformed':
                fwrite($stdout, "{bad json\n");
                return 0;
            case '--child-stdout-overflow':
                fwrite($stdout, str_repeat('O', self::STREAM_LIMIT + 1024));
                return 0;
            case '--child-stderr-overflow':
                fwrite($stderr, str_repeat('E', self::STREAM_LIMIT + 1024));
                return 0;
            case '--child-timeout':
                usleep(3_000_000);
                return 0;
            case '--child-assertion-failure':
                fwrite($stderr, "controlled-assertion-failure\n");
                return 23;
            case '--child-burst':
                for ($i = 0; $i < 32; ++$i) {
                    fwrite($stdout, str_repeat('A', 1024));
                    fwrite($stderr, str_repeat('B', 1024));
                }
                return 0;
            case '--child-trace-mode':
                try {
                    fwrite($stdout, json_encode($this->traceFailureEvidence(), JSON_THROW_ON_ERROR) . "\n");
                    return 0;
                } catch (Throwable) {
                    fwrite($stderr, self::FAILURE);
                    return 1;
                }
            default:
                fwrite($stderr, self::FAILURE);
                return 1;
        }
    }

    private static function profiles(): StorageProfileSet
    {
        return new StorageProfileSet([new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName('demo'),
            new StorageNamespace('objects'),
            new StorageCapabilitySet([
                StorageCapability::Read,
                StorageCapability::Create,
                StorageCapability::Replace,
                StorageCapability::Delete,
                StorageCapability::List,
                StorageCapability::Metadata,
                StorageCapability::ContentDigest,
                StorageCapability::ConditionalMutation,
            ]),
            StorageDigestAlgorithm::Sha256,
            new StorageProfileLimits(1024, 4096, 10),
        )]);
    }

    private static function current(): CurrentStorageApplicationBuild
    {
        $profiles = self::profiles();
        return new CurrentStorageApplicationBuild($profiles, (new StorageProfileBuilder())->build($profiles)->artifact());
    }

    private function contractBuild(): void
    {
        $first = self::profiles();
        $second = self::profiles();
        $builder = new StorageProfileBuilder();
        $a = $builder->build($first);
        $b = $builder->build($second);
        $this->check($a->equals($b));
        $this->check($a->sourceIdentity()->equals($first->identity()));
        $this->check($a->artifactIdentity()->value() === hash('sha256', $a->artifact()->bytes()));
        $this->check($a->sourceIdentity()->value() !== $a->buildIdentity()->value());
        $this->check($a->buildIdentity()->value() !== $a->artifactIdentity()->value());
        $this->check($a->sourceIdentity()->value() !== $a->artifactIdentity()->value() && $a->artifact()->bytes() === $b->artifact()->bytes());
        $validator = new StorageProfileArtifactValidator();
        $this->check($validator->validateCurrent($first, $a->artifact()->bytes())->equals($a->artifact()));
        $this->fails(static fn() => $validator->validateStructure($a->artifact()->bytes() . ' '));
        $this->fails(static fn() => $validator->validateStructure('{"schema":"wrong"}'));
        $mixedVersion = str_replace('"contract_version":1', '"contract_version":2', $a->artifact()->bytes());
        $this->check($mixedVersion !== $a->artifact()->bytes());
        $this->fails(static fn() => $validator->validateStructure($mixedVersion), InvalidStorageProfileArtifactException::class);
        $changed = new StorageProfileSet([new StorageProfileDeclaration(ContractVersion::Version1, new StorageProfileName('other'), new StorageNamespace('objects'), new StorageCapabilitySet([StorageCapability::Read]), null, new StorageProfileLimits(1024, 4096, 10))]);
        $this->fails(static fn() => $validator->validateCurrent($changed, $a->artifact()->bytes()), StaleStorageProfileArtifactException::class);
        $alpha = new StorageProfileDeclaration(ContractVersion::Version1, new StorageProfileName('alpha'), new StorageNamespace('alpha-objects'), new StorageCapabilitySet([StorageCapability::Read]), null, new StorageProfileLimits(1024, 4096, 10));
        $zeta = new StorageProfileDeclaration(ContractVersion::Version1, new StorageProfileName('zeta'), new StorageNamespace('zeta-objects'), new StorageCapabilitySet([StorageCapability::Read]), null, new StorageProfileLimits(1024, 4096, 10));
        $this->check((new StorageProfileBuilder())->build(new StorageProfileSet([$zeta, $alpha]))->equals((new StorageProfileBuilder())->build(new StorageProfileSet([$alpha, $zeta]))));
        $this->fails(static fn() => new StorageProfileSet([$alpha, $alpha]));
        $this->check(StorageContractBounds::MAX_CHUNK_BYTES === (new StorageProfileLimits(StorageContractBounds::MAX_CHUNK_BYTES, StorageContractBounds::MAX_OBJECT_BYTES, StorageContractBounds::MAX_LIST_PAGE_SIZE))->maxChunkBytes());
        $this->fails(static fn() => new StorageProfileLimits(StorageContractBounds::MAX_CHUNK_BYTES + 1, StorageContractBounds::MAX_OBJECT_BYTES, 1), InvalidStorageProfileException::class);
        $this->fails(static fn() => new StorageProfileLimits(1, StorageContractBounds::MAX_OBJECT_BYTES + 1, 1), InvalidStorageProfileException::class);
        $this->fails(static fn() => new StorageProfileLimits(1, 1, StorageContractBounds::MAX_LIST_PAGE_SIZE + 1), InvalidStorageProfileException::class);
        $this->fails(static fn() => new StorageProfileSet([]), InvalidStorageProfileException::class);
        $this->fails(static fn() => new StorageProfileSet(array_fill(0, 257, $alpha)), InvalidStorageProfileException::class);
        $this->check(StorageContractBounds::MAX_LOGICAL_IDENTITY_BYTES === strlen((new LogicalObjectIdentity(str_repeat('a', StorageContractBounds::MAX_LOGICAL_IDENTITY_BYTES)))->value()));
        $this->fails(static fn() => new LogicalObjectIdentity(str_repeat('a', StorageContractBounds::MAX_LOGICAL_IDENTITY_BYTES + 1)));
    }

    private function publication(): void
    {
        $workspace = $this->owned();
        $artifact = self::current()->artifact();
        $root = $workspace->directory('work/published');
        $sentinel = $workspace->write('work/sentinel', 'outside-publication');
        $publisher = new StorageProfileArtifactPublisher();
        $first = $publisher->publish($artifact, $root);
        $second = $publisher->publish($artifact, $root);
        $this->check($first->created() && !$second->created());
        $this->check($first->absoluteArtifactPath() === $second->absoluteArtifactPath());
        $digest = $artifact->artifactIdentity()->value();
        $relative = 'objects/sha256/' . substr($digest, 0, 2) . '/' . $digest . '/storage-profile.json';
        $this->check($relative === $first->relativeArtifactPath() && realpath($first->absoluteArtifactPath()) === realpath($root . '/' . $relative));
        $this->check($first->artifactByteCount() === strlen($artifact->bytes()) && $first->artifactIdentity()->equals($artifact->artifactIdentity()));
        $workspace->assertFile($first->absoluteArtifactPath());
        $this->check(file_get_contents($first->absoluteArtifactPath()) === $artifact->bytes());
        $this->check(file_get_contents($sentinel) === 'outside-publication');
        $this->fails(static fn() => $publisher->publish($artifact, $root . '/../outside'));
        $target = $first->absoluteArtifactPath();
        $this->check(false !== file_put_contents($target, 'conflict'));
        $this->fails(static fn() => $publisher->publish($artifact, $root));
        $this->check(file_get_contents($target) === 'conflict');
        $this->check(unlink($target));
        $this->check(mkdir($target));
        $this->fails(static fn() => $publisher->publish($artifact, $root));
        $this->check(rmdir($target));
        if (@symlink($sentinel, $target)) {
            $this->fails(static fn() => $publisher->publish($artifact, $root));
            $this->check('outside-publication' === file_get_contents($sentinel));
            $this->check(unlink($target));
        } else {
            $this->check(!file_exists($target) && !is_link($target));
        }
        $workspace->registerProducedTree('work/published');
    }

    private function authorization(): void
    {
        $workspace = $this->owned();
        $current = self::current();
        $root = $workspace->directory('work/authorization');
        $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), new StorageProfileName('demo'));
        $this->check([] === array_values(array_diff((array) scandir($root), ['.', '..'])));
        $this->fails(static fn() => (new StorageApplicationRuntimeFactory())->create($current, new StorageProfileName('demo'), new StorageAdapterIdentity('wrong-adapter', 1), $adapter, new Phase21Authorizer(true)), StorageRuntimeCompositionException::class);
        $this->check([] === array_values(array_diff((array) scandir($root), ['.', '..'])));
        $authorizer = new Phase21Authorizer(false);
        $runtime = (new StorageApplicationRuntimeFactory())->create($current, new StorageProfileName('demo'), new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $authorizer);
        $request = self::writeRequest('auth-create', 'auth-object', 'abc');
        $source = new Phase21ByteSource('abc');
        $this->fails(static fn() => $runtime->create($request, $source), StorageAuthorizationDeniedException::class);
        $this->check(1 === $authorizer->calls && $authorizer->lastRequest === $request && 0 === $source->reads && 0 === $source->closes);
        $authorizerCause = new RuntimeException('PrivateAuthorizerMarker');
        $authorizer->failure = $authorizerCause;
        try {
            $runtime->create($request, $source);
            $this->check(false);
        } catch (StorageAuthorizationFailureException $failure) {
            $this->check($failure->getPrevious() === $authorizerCause && !str_contains(self::publicFailureText($failure), 'PrivateAuthorizerMarker'));
        }
        $this->check(2 === $authorizer->calls && 0 === $source->closes);
        $this->check([] === array_values(array_diff((array) scandir($root), ['.', '..'])));
        $authorizer->failure = null;
        $authorizer->allow = true;
        $result = $runtime->create($request, $source);
        $this->check(StorageMutationOutcome::Completed === $result->outcome());
        $this->check(3 === $authorizer->calls && $authorizer->lastRequest === $request && 1 === $source->closes);
        $version = $result->resultingVersion();
        if (!$version instanceof StorageObjectVersion) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $mismatchedSource = new Phase21ByteSource('abc');
        $replaceRequest = self::writeRequest('auth-mismatch', 'auth-object', 'abc', StorageOperation::Replace, StorageMutationCondition::ifVersion($version));
        $this->fails(static fn() => $runtime->create($replaceRequest, $mismatchedSource), StorageApplicationInvariantException::class);
        $this->check(3 === $authorizer->calls && 0 === $mismatchedSource->reads && 0 === $mismatchedSource->closes);
        $typeCause = new TypeError('PrivateApplicationMarker');
        $typePolicy = new Phase21Authorizer(true);
        $typePolicy->failure = $typeCause;
        $typeAdapter = new Phase21ScriptedAdapter($current, StorageMutationOutcome::Rejected);
        $typeRuntime = new AuthorizedStorageRuntime(self::scriptedRuntime($current, $typeAdapter), $typePolicy);
        $typeSource = new Phase21ByteSource('abc');
        try {
            $typeRuntime->create(self::writeRequest('auth-type', 'type-object', 'abc'), $typeSource);
            $this->check(false);
        } catch (StorageAuthorizationFailureException $failure) {
            $this->check($failure->getPrevious() === $typeCause && !str_contains(self::publicFailureText($failure), 'PrivateApplicationMarker'));
            $this->check(1 === $typePolicy->calls && 0 === $typeAdapter->calls && 0 === $typeSource->reads && 0 === $typeSource->closes);
        }
        $typePolicy->failure = null;
        $this->check(StorageMutationOutcome::Rejected === $typeRuntime->create(self::writeRequest('auth-type-after', 'type-object', 'abc'), $typeSource)->outcome());
        $this->check(2 === $typePolicy->calls && 1 === $typeAdapter->calls && 1 === $typeSource->closes);
        $workspace->registerProducedTree('work/authorization');
    }

    private function localOperations(): void
    {
        $workspace = $this->owned();
        $current = self::current();
        $root = $workspace->directory('work/local');
        $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), new StorageProfileName('demo'));
        $policy = new Phase21Authorizer(true);
        $runtime = (new StorageApplicationRuntimeFactory())->create($current, new StorageProfileName('demo'), new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $policy);
        $first = $runtime->create(self::writeRequest('local-create-a', 'a', 'abc'), new Phase21ByteSource('abc'));
        $version = $first->resultingVersion();
        $this->check(StorageMutationOutcome::Completed === $first->outcome() && $version instanceof StorageObjectVersion);
        $duplicate = $runtime->create(self::writeRequest('local-create-b', 'a', 'abc'), new Phase21ByteSource('abc'));
        $this->check(StorageMutationOutcome::AlreadyExists === $duplicate->outcome());
        $read = $runtime->read(self::readRequest('local-read-a', 'a'));
        $this->check(StorageReadOutcome::Completed === $read->result()->outcome());
        $evidence = $read->result()->evidence();
        if (null === $evidence || !$version instanceof StorageObjectVersion) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check(3 === $evidence->byteCount()->value() && $evidence->contentDigest()?->digest() === hash('sha256', 'abc') && true === $evidence->objectVersion()?->equals($version) && $evidence->identity()->equals(self::identity('a')));
        $this->check([] === $evidence->metadata()->entries() && 'text/plain' === $evidence->mediaType()?->value());
        $source = $read->source();
        $this->check($source instanceof StorageByteSource);
        if (!$source instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check('abc' === $source->read(2)?->bytes() . $source->read(2)?->bytes());
        $this->check(null === $source->read(2) && $source->exhausted());
        $source->close();
        $this->check($source->closed());
        $this->check(StorageMutationOutcome::Completed === $runtime->create(self::writeRequest('local-create-c', 'b', ''), new Phase21ByteSource(''))->outcome());
        $emptyRead = $runtime->read(self::readRequest('local-read-empty', 'b'))->source();
        $this->check($emptyRead instanceof StorageByteSource);
        if (!$emptyRead instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check(null === $emptyRead->read(1024) && $emptyRead->exhausted());
        $emptyRead->close();
        $page1 = $runtime->list(new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('local-list-a'), new StorageNamespace('objects'), 1));
        $this->check(StorageListOutcome::Completed === $page1->outcome() && 1 === count($page1->page()?->entries() ?? []));
        $this->check('a' === $page1->page()?->entries()[0]->identity()->logicalIdentity()->value());
        $this->check(3 === $page1->page()?->entries()[0]->byteCount()?->value());
        $token = $page1->page()?->continuationToken();
        $this->check(null !== $token);
        $this->check($token instanceof StorageContinuationToken && 'a' !== $token->value() && strlen($token->value()) <= StorageContractBounds::MAX_CONTINUATION_BYTES);
        $page2 = $runtime->list(new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('local-list-b'), new StorageNamespace('objects'), 1, $token));
        $this->check(true === $page2->page()?->complete());
        $this->check('b' === $page2->page()?->entries()[0]->identity()->logicalIdentity()->value());
        $replaced = $runtime->replace(self::writeRequest('local-replace', 'a', 'xyz', StorageOperation::Replace, StorageMutationCondition::ifVersion($version)), new Phase21ByteSource('xyz'));
        $next = $replaced->resultingVersion();
        $this->check(StorageMutationOutcome::Completed === $replaced->outcome() && $next instanceof StorageObjectVersion);
        $conflict = $runtime->replace(self::writeRequest('local-conflict', 'a', 'bad', StorageOperation::Replace, StorageMutationCondition::ifVersion($version)), new Phase21ByteSource('bad'));
        $this->check(StorageMutationOutcome::Conflict === $conflict->outcome());
        $afterConflict = $runtime->read(self::readRequest('local-after-conflict', 'a'));
        $afterSource = $afterConflict->source();
        if (!$afterSource instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check(StorageReadOutcome::Completed === $afterConflict->result()->outcome() && $afterConflict->result()->evidence()?->contentDigest()?->digest() === hash('sha256', 'xyz'));
        $afterBytes = $afterSource->read(3);
        $afterEnd = $afterSource->read(2);
        $this->check('xyz' === $afterBytes?->bytes() && null === $afterEnd);
        $afterSource->close();
        if (!$next instanceof StorageObjectVersion) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $deleted = $runtime->delete(new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('local-delete'), self::identity('a'), StorageMutationCondition::ifVersion($next)));
        $this->check(StorageMutationOutcome::Completed === $deleted->outcome());
        $absent = $runtime->delete(new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('local-delete-absent'), self::identity('a'), StorageMutationCondition::ifVersion($next)));
        $this->check(StorageMutationOutcome::Absent === $absent->outcome());
        $absentRead = $runtime->read(self::readRequest('local-read-absent', 'a'));
        $this->check(StorageReadOutcome::Absent === $absentRead->result()->outcome() && null === $absentRead->source());
        $workspace->registerProducedTree('work/local');
    }

    private function outcomeTruth(): void
    {
        $current = self::current();
        foreach ([StorageMutationOutcome::Unavailable, StorageMutationOutcome::Uncertain, StorageMutationOutcome::Rejected] as $outcome) {
            $adapter = new Phase21ScriptedAdapter($current, $outcome);
            $runtime = self::scriptedRuntime($current, $adapter);
            $source = new Phase21ByteSource('abc');
            $result = $runtime->create(self::writeRequest('scripted-' . $outcome->value, 'scripted', 'abc'), $source);
            $this->check($result->outcome() === $outcome);
            $this->check(null === $result->resultingVersion() && null === $result->resultingDigest());
            $this->check(1 === $adapter->calls && 1 === $source->closes);
            if (StorageMutationOutcome::Uncertain === $outcome) {
                $this->check(1 === $adapter->simulatedEffects);
            }
        }
        $primaryCause = new RuntimeException('PrivateRuntimeMarker');
        $cleanupCause = new RuntimeException('PrivateCleanupMarker');
        $adapter = new Phase21ScriptedAdapter($current, null, $primaryCause);
        $source = new Phase21ByteSource('abc', false, false, $cleanupCause);
        try {
            self::scriptedRuntime($current, $adapter)->create(self::writeRequest('scripted-both', 'scripted', 'abc'), $source);
            $this->check(false);
        } catch (StorageRuntimeOperationAndCleanupException $failure) {
            $this->check(1 === $adapter->calls && 1 === $source->closes);
            $this->check($failure->primaryFailure() instanceof StorageRuntimeOperationException && $failure->primaryFailure()->getPrevious() === $primaryCause);
            $this->check([$cleanupCause] === $failure->cleanupFailures() && $failure->getPrevious() === $failure->primaryFailure());
            $this->check(!str_contains(self::publicFailureText($failure), 'PrivateRuntimeMarker') && !str_contains(self::publicFailureText($failure), 'PrivateCleanupMarker'));
        }
    }

    private function streamLifecycle(): void
    {
        $workspace = $this->owned();
        $current = self::current();
        $root = $workspace->directory('work/stream');
        $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), new StorageProfileName('demo'));
        $directSource = new Phase21ByteSource('direct');
        $direct = $adapter->write(self::writeRequest('direct-write', 'direct', 'direct'), $directSource);
        $this->check(StorageMutationOutcome::Completed === $direct->outcome() && 0 === $directSource->closes);
        $directSource->close();
        $this->check(1 === $directSource->closes);
        $runtime = (new StorageApplicationRuntimeFactory())->create($current, new StorageProfileName('demo'), new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, new Phase21Authorizer(true));
        $bytes = str_repeat('x', 2050);
        $writer = new Phase21ByteSource($bytes);
        $created = $runtime->create(self::writeRequest('stream-large', 'large', $bytes), $writer);
        $this->check(StorageMutationOutcome::Completed === $created->outcome() && 1 === $writer->closes);
        $this->check($created->resultingDigest()?->digest() === hash('sha256', $bytes));
        $read = $runtime->read(self::readRequest('stream-large-read', 'large'));
        $largeEvidence = $read->result()->evidence();
        if (null === $largeEvidence) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check(StorageReadOutcome::Completed === $read->result()->outcome() && 2050 === $largeEvidence->byteCount()->value() && hash('sha256', $bytes) === $largeEvidence->contentDigest()?->digest());
        $source = $read->source();
        $this->check($source instanceof StorageByteSource);
        if (!$source instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $received = '';
        while (null !== ($part = $source->read(512))) {
            $this->check(strlen($part->bytes()) <= 512);
            $received .= $part->bytes();
        }
        $this->check($received === $bytes && $source->exhausted());
        $source->close();
        $this->check($source->closed());
        $early = $runtime->read(self::readRequest('stream-early', 'large'))->source();
        $this->check($early instanceof StorageByteSource);
        if (!$early instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check(null !== $early->read(1));
        $this->check(!$early->exhausted());
        $early->close();
        $this->check($early->closed());
        $this->check(!$early->exhausted());
        $this->fails(static fn() => $early->read(1), InvalidStorageRuntimeException::class);
        $badDigestSource = new Phase21ByteSource('abd');
        $badDigest = $runtime->create(self::writeRequest('stream-bad-digest', 'bad-digest', 'abc'), $badDigestSource);
        $this->check(StorageMutationOutcome::Completed !== $badDigest->outcome() && 1 === $badDigestSource->closes);
        $earlyEofSource = new Phase21ByteSource('ab');
        $earlyEof = $runtime->create(self::writeRequest('stream-early-eof', 'early-eof', 'abc'), $earlyEofSource);
        $this->check(StorageMutationOutcome::Completed !== $earlyEof->outcome() && 1 === $earlyEofSource->closes);
        $overrunBytes = str_repeat('x', 1025);
        $overrunSource = new Phase21ByteSource($overrunBytes, false, false, null, true);
        $overrun = $runtime->create(self::writeRequest('stream-overrun', 'overrun', $overrunBytes), $overrunSource);
        $this->check(StorageMutationOutcome::Completed !== $overrun->outcome() && 1 === $overrunSource->closes);
        foreach (['bad-digest', 'early-eof', 'overrun'] as $logical) {
            $this->check(StorageReadOutcome::Absent === $runtime->read(self::readRequest('stream-no-' . $logical, $logical))->result()->outcome());
        }
        $empty = $runtime->read(self::readRequest('stream-empty', 'direct'))->source();
        $this->check($empty instanceof StorageByteSource);
        if (!$empty instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $empty->close();
        $sink = new Phase21ByteSink();
        $sink->write(new StorageChunk('abc'));
        $sink->abort();
        $this->check($sink->closed() && 'abc' === $sink->bytes && 1 === $sink->aborts);
        $workspace->registerProducedTree('work/stream');
    }

    private function runtimeIsolation(): void
    {
        $current = self::current();
        $left = new Phase21ScriptedAdapter($current, StorageMutationOutcome::Rejected);
        $right = new Phase21ScriptedAdapter($current, StorageMutationOutcome::Unavailable);
        $a = self::scriptedRuntime($current, $left);
        $b = self::scriptedRuntime($current, $right);
        $this->check(StorageMutationOutcome::Rejected === $a->create(self::writeRequest('iso-a1', 'scripted', 'abc'), new Phase21ByteSource('abc'))->outcome());
        $this->check(StorageMutationOutcome::Unavailable === $b->create(self::writeRequest('iso-b1', 'scripted', 'abc'), new Phase21ByteSource('abc'))->outcome());
        $this->check(StorageMutationOutcome::Rejected === $a->create(self::writeRequest('iso-a2', 'scripted', 'abc'), new Phase21ByteSource('abc'))->outcome());
        $this->check(2 === $left->calls && 1 === $right->calls);
        $policy = new Phase21Authorizer(true);
        $authorized = new AuthorizedStorageRuntime($a, $policy);
        $source = new Phase21ByteSource('abc');
        $policy->onAuthorize = static function (StorageWriteRequest $request) use (&$authorized, $source): void {
            $authorized->create($request, $source);
        };
        try {
            $authorized->create(self::writeRequest('iso-reentry', 'scripted', 'abc'), $source);
            $this->check(false);
        } catch (StorageAuthorizationFailureException $failure) {
            $this->check($failure->getPrevious() instanceof StorageApplicationInvariantException);
        }
        $this->check(0 === $source->closes && 2 === $left->calls);
        $policy->onAuthorize = null;
        $this->check(StorageMutationOutcome::Rejected === $authorized->create(self::writeRequest('iso-after', 'scripted', 'abc'), $source)->outcome());
        $this->check(1 === $source->closes && 3 === $left->calls);
        $failure = new RuntimeException('PrivateIsolationMarker');
        $broken = new Phase21ScriptedAdapter($current, null, $failure);
        $brokenSource = new Phase21ByteSource('abc');
        try {
            self::scriptedRuntime($current, $broken)->create(self::writeRequest('iso-failure', 'scripted', 'abc'), $brokenSource);
            $this->check(false);
        } catch (StorageRuntimeOperationException $caught) {
            $this->check($caught->getPrevious() === $failure && 1 === $broken->calls && 1 === $brokenSource->closes);
        }
        $this->check(StorageMutationOutcome::Unavailable === $b->create(self::writeRequest('iso-b2', 'scripted', 'abc'), new Phase21ByteSource('abc'))->outcome());
        $this->check(StorageMutationOutcome::Rejected === $a->create(self::writeRequest('iso-a3', 'scripted', 'abc'), new Phase21ByteSource('abc'))->outcome());
        $this->check(4 === $left->calls && 2 === $right->calls);
    }

    private static function scriptedRuntime(CurrentStorageApplicationBuild $current, Phase21ScriptedAdapter $adapter): ExactCurrentStorageRuntime
    {
        return new ExactCurrentStorageRuntime(new StorageRuntimeBinding($current->profiles(), $current->artifact()->bytes(), new StorageProfileName('demo'), new StorageAdapterIdentity('scripted', 1), $adapter));
    }

    private function applicationParity(): void
    {
        $workspace = $this->owned();
        $app = $workspace->directory('work/application');
        $output = $workspace->directory('work/application/profiles');
        $source = '{"schema":"kongono/storage-application-profile-source/1","profiles":[{"name":"demo","contract_version":1,"namespace":"objects","capabilities":["read","create","replace","delete","list","metadata","content_digest","conditional_mutation"],"digest_algorithm":"sha256","limits":{"max_chunk_bytes":1024,"max_object_bytes":4096,"max_list_page_size":10}}]}';
        $workspace->write('work/application/source.json', $source);
        $definition = new StorageApplicationDefinition($app, 'source.json', $output);
        $workflow = new StorageApplicationWorkflow();
        $plan = $workflow->plan($definition);
        $this->check($plan->publicationRequired() && 1 === $plan->profileCount());
        $inputs = ['--application-root=' . $app, '--output-directory=' . $output, '--source-file=source.json', '--no-interaction'];
        [$statusPlan, $jsonPlan] = self::console(['--json', 'storage:plan', ...$inputs]);
        [$statusBuildPlan, $jsonBuildPlan] = self::console(['--json', 'storage:build', '--plan', ...$inputs]);
        $direct = ['profile_count' => $plan->profileCount(), 'current' => !$plan->publicationRequired(), 'publication_required' => $plan->publicationRequired()];
        $planSummary = self::consoleFields($jsonPlan);
        $buildSummary = self::consoleFields($jsonBuildPlan);
        $this->check(0 === $statusPlan && 0 === $statusBuildPlan);
        foreach ($direct as $key => $value) {
            $this->check($value === $planSummary[$key] && $value === $buildSummary[$key]);
        }
        $this->check([] === array_values(array_diff((array) scandir($output), ['.', '..'])));
        [$missing, ] = self::console(['--json', 'storage:check', ...$inputs]);
        $this->check(3 === $missing);
        [$apply, $jsonApply] = self::console(['--json', 'storage:build', '--apply', ...$inputs]);
        $this->check(0 === $apply && true === self::consoleFields($jsonApply)['current']);
        [$check, $jsonCheck] = self::console(['--json', 'storage:check', ...$inputs]);
        $this->check(0 === $check && true === self::consoleFields($jsonCheck)['current']);
        $this->check(!$workflow->plan($definition)->publicationRequired());
        $this->check($workflow->check($definition)->artifact()->equals($workflow->build($definition)->artifact()));
        $currentArtifact = $workflow->check($definition)->artifact();
        $currentDigest = $currentArtifact->artifactIdentity()->value();
        $currentPath = $output . '/objects/sha256/' . substr($currentDigest, 0, 2) . '/' . $currentDigest . '/storage-profile.json';
        $workspace->assertFile($currentPath);
        $this->check($currentArtifact->bytes() === file_get_contents($currentPath));
        $workspace->write('work/application/stale.json', str_replace('"name":"demo"', '"name":"stale-demo"', $source));
        $stale = new StorageApplicationDefinition($app, 'stale.json', $output);
        $this->fails(static fn() => $workflow->check($stale), StorageApplicationWorkflowException::class);
        $this->check($currentArtifact->bytes() === file_get_contents($currentPath));

        $copy = $workspace->copyTree($this->repositoryRoot . '/skeletons/storage-minimal', 'work/slice');
        foreach (['ExampleByteSource', 'ExampleStorageAuthorizer', 'StorageWorkflow', 'StorageDemo'] as $class) {
            require_once $copy . '/src/' . $class . '.php';
        }
        $sliceSource = $copy . '/kongono.storage.json';
        $this->check(is_file($sliceSource));
        $sliceOutput = $workspace->directory('work/slice/profiles');
        $objects = $workspace->directory('work/slice-objects');
        $sentinel = $workspace->write('work/slice-objects/sentinel', 'preserve');
        $sliceWorkflow = new \StorageMinimalApp\StorageWorkflow($copy, 'kongono.storage.json', $sliceOutput);
        $this->check($sliceWorkflow->plan()->publicationRequired());
        $this->check(1 === $sliceWorkflow->build()->profiles()->profileCount());
        $this->check(1 === $sliceWorkflow->check()->profiles()->profileCount());
        $demo = new \StorageMinimalApp\StorageDemo();
        $result = $demo->run($copy, 'kongono.storage.json', $sliceOutput, $objects);
        $this->check(['create' => 'completed', 'read' => 'completed', 'replace' => 'completed', 'delete' => 'completed'] === $result);
        $this->check('preserve' === file_get_contents($sentinel));
        $existing = 'caller-owned-existing';
        $sliceAdapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($objects), $sliceWorkflow->check()->artifact(), new StorageProfileName('demo'));
        $existingRequest = new StorageWriteRequest(ContractVersion::Version1, new StorageOperationIdentity('existing-create'), StorageOperation::Create, new StorageObjectIdentity(new StorageNamespace('demo-objects'), new LogicalObjectIdentity('demo-object')), new StorageByteCount(strlen($existing)), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $existing)), new StorageMediaType('text/plain'), new StorageMetadata([]), StorageMutationCondition::ifAbsent());
        $existingSource = new Phase21ByteSource($existing);
        $this->check(StorageMutationOutcome::Completed === $sliceAdapter->write($existingRequest, $existingSource)->outcome());
        $this->check(0 === $existingSource->closes);
        $existingSource->close();
        $this->check(['create' => 'already_exists', 'read' => 'skipped', 'replace' => 'skipped', 'delete' => 'skipped'] === $demo->run($copy, 'kongono.storage.json', $sliceOutput, $objects));
        $this->check('preserve' === file_get_contents($sentinel));
        $existingRead = $sliceAdapter->read(new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('existing-read'), new StorageObjectIdentity(new StorageNamespace('demo-objects'), new LogicalObjectIdentity('demo-object')), new StorageByteCount(4096)));
        $existingReadSource = $existingRead->source();
        if (!$existingReadSource instanceof StorageByteSource) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $this->check(StorageReadOutcome::Completed === $existingRead->result()->outcome() && $existingRead->result()->evidence()?->contentDigest()?->digest() === hash('sha256', $existing));
        $existingBytes = $existingReadSource->read(1024);
        $existingEnd = $existingReadSource->read(1);
        $this->check($existing === $existingBytes?->bytes() && null === $existingEnd);
        $existingReadSource->close();
        $this->fails(static fn() => $demo->run($copy, 'missing.json', $sliceOutput, $objects));
        $workspace->registerProducedTree('work/application');
        $workspace->registerProducedTree('work/slice');
        $workspace->registerProducedTree('work/slice-objects');
    }

    /** @param list<string> $arguments
     * @return array{int, string}
     */
    private static function console(array $arguments): array
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        if (false === $stdout || false === $stderr) {
            throw new RuntimeException('phase21_capture_failed');
        }
        try {
            $status = (new NativeKongoEntrypoint((new KongoApplicationFactory())->create()))->run($arguments, $stdout, $stderr);
            rewind($stdout);
            rewind($stderr);
            return [$status, (string) stream_get_contents($stdout) . (string) stream_get_contents($stderr)];
        } finally {
            fclose($stdout);
            fclose($stderr);
        }
    }

    /** @return array<string, bool|int|string> */
    private static function consoleFields(string $json): array
    {
        $document = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($document) || !isset($document['data']) || !is_array($document['data']) || !isset($document['data']['fields']) || !is_array($document['data']['fields'])) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
        $fields = [];
        foreach ($document['data']['fields'] as $field) {
            if (!is_array($field) || !isset($field['name'], $field['value']) || !is_string($field['name']) || (!is_string($field['value']) && !is_bool($field['value']) && !is_int($field['value']))) {
                throw new RuntimeException('phase21_acceptance_failed');
            }
            $fields[$field['name']] = $field['value'];
        }
        return $fields;
    }

    private function inspection(): void
    {
        $current = self::current();
        $inspector = new StorageInspector();
        $unusedAdapter = new Phase21ScriptedAdapter($current, StorageMutationOutcome::Rejected);
        $absent = $inspector->inspect(new StorageInspectionRequest($current));
        $this->check(StorageInspection::RUNTIME_NOT_SUPPLIED === $absent->runtimeEvidenceStatus());
        $bindings = [
            'profile_set_sha256' => $current->profiles()->identity()->value(),
            'build_sha256' => $current->artifact()->buildIdentity()->value(),
            'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
        ];
        $zero = new DetachedStorageRuntimeEvidence($bindings, array_fill_keys(DetachedStorageRuntimeEvidence::OUTCOMES, 0));
        $zeroInspection = $inspector->inspect(new StorageInspectionRequest($current, $zero));
        $this->check(StorageInspection::RUNTIME_SUPPLIED === $zeroInspection->runtimeEvidenceStatus() && 0 === $zeroInspection->count('outcome_total'));
        $counts = array_fill_keys(DetachedStorageRuntimeEvidence::OUTCOMES, 0);
        foreach (DetachedStorageRuntimeEvidence::OUTCOMES as $key) {
            $counts[$key] = 1;
        }
        $runtimeEvidence = new DetachedStorageRuntimeEvidence($bindings, $counts);
        $request = new StorageInspectionRequest($current, $runtimeEvidence);
        $inspection = $inspector->inspect($request);
        $this->check(17 === $inspection->count('outcome_total'));
        $this->check(DetachedStorageRuntimeEvidence::OUTCOMES === array_keys($runtimeEvidence->outcomeCounts()));
        $this->check(0 === $unusedAdapter->calls && !$inspection->flag('adapter_access') && !$inspection->flag('provider_access') && !$inspection->flag('production_observed'));
        $deriver = new StorageIntelligenceDeriver();
        $context = ObservabilityContext::uncorrelated();
        $observation = (new StorageObservationProjector())->project($inspection, $context);
        $audit = (new StorageSecurityAuditProjector())->project($inspection, $context);
        $this->check('kongono.storage.inspection' === $observation->identity()->name());
        $safe = [];
        foreach ($observation->attributes()->attributes() as $attribute) {
            $safe[$attribute->key()->value()] = $attribute->value()->value();
        }
        $this->check('constructed-not-recorded' === ($safe['inspection.projection'] ?? null) && false === ($safe['inspection.adapter_access'] ?? null));
        $this->check(count($safe) === count($audit->attributes()->attributes()) && 0 === $unusedAdapter->calls);
        $this->check(!str_contains(json_encode($safe, JSON_THROW_ON_ERROR), 'PrivateObjectMarker'));
        foreach ($bindings as $protectedDigest) {
            $this->check(!in_array($protectedDigest, $safe, true));
        }
        foreach (StorageInspectionDeveloperRole::cases() as $role) {
            $view = $deriver->derive($inspection, $role);
            $this->check($role->purpose() === $view->purpose());
            $this->check('not-established' === $view->graphEvidence()->relationship());
            $this->check('not-performed' === $view->toArray()['role_execution'] && 'not-performed' === $view->toArray()['recording']);
            $this->check(!str_contains($view->canonicalBytes(), 'PrivateObjectMarker'));
            $this->check(StorageIntelligenceVerdict::Pass === (new StorageIntelligenceVerifier())->verify($request, $inspection, $view, $observation, $audit)->verdict());
        }
        $graph = new ApplicationGraphIntelligenceView(GraphRole::Hantei, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), GraphContractVersion::Version1, 10, 6, ['composition.input' => 1, 'composition.service' => 4, 'composition.contract' => 3, 'composition.root' => 2], ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1]);
        $graphView = $deriver->derive($inspection, StorageInspectionDeveloperRole::Hantei, $graph);
        $this->check($graphView->graphEvidence()->supplied() && 'not-established' === $graphView->graphEvidence()->relationship());
        $this->check(StorageIntelligenceVerdict::Pass === (new StorageIntelligenceVerifier())->verify($request, $inspection, $graphView, $observation, $audit, $graph)->verdict());
        $changed = $inspection->protectedBindings();
        $changed['build_sha256'] = str_repeat('f', 64);
        $tampered = new StorageInspection($inspection->runtimeEvidenceStatus(), $changed, $inspection->counts(), $inspection->flags());
        $view = $deriver->derive($tampered, StorageInspectionDeveloperRole::Chie);
        $this->check(StorageIntelligenceVerdict::Fail === (new StorageIntelligenceVerifier())->verify($request, $tampered, $view, $observation, $audit)->verdict());
        $this->fails(static fn() => new DetachedStorageRuntimeEvidence($bindings, [...$counts, 'extra' => 0]));
    }

    private function nondisclosure(): void
    {
        $marker = 'PrivateInspectionMarker';
        $previous = new RuntimeException($marker);
        $failures = [
            InvalidStorageInspectionException::invalid($marker, $previous),
            StorageInspectionBindingException::mismatch($marker, $previous),
            InvalidStorageIntelligenceEvidenceException::invalid($marker, $previous),
            StorageInspectionProjectionException::failed($marker, $previous),
            StorageInspectionVerificationException::failed($marker, $previous),
        ];
        foreach ($failures as $position => $failure) {
            $this->check('KGN-STORAGE-INSPECTION-' . (2151 + $position) === $failure->diagnosticId());
            $public = self::publicFailureText($failure);
            $this->check(!str_contains($public, $marker));
            $this->check($failure->getPrevious() === $previous);
        }
        foreach (['0', '1'] as $setting) {
            $result = $this->captureChild('trace-' . $setting, '--child-trace-mode', self::PROCESS_TIMEOUT_SECONDS, $setting);
            $data = json_decode($this->rawStdout($result), true, 8, JSON_THROW_ON_ERROR);
            $this->check(0 === $result->exitCode && $result->retired && !$result->timedOut && '' === $this->rawStderr($result));
            $this->check(is_array($data) && $setting === ($data['ignore_args'] ?? null) && ['build' => true, 'local' => true, 'runtime' => true, 'application' => true, 'inspection' => true] === ($data['cases'] ?? null) && true === ($data['owned_cleanup'] ?? null));
            foreach (['PrivateBuildMarker', 'PrivateLocalMarker', 'PrivateRuntimeMarker', 'PrivateApplicationMarker', 'PrivateInspectionMarker'] as $hidden) {
                $this->check(!str_contains($this->rawStdout($result), $hidden));
            }
            $this->acknowledge($result);
        }
    }

    private static function publicFailureText(StorageBuildException|LocalStorageAdapterException|StorageRuntimeException|StorageApplicationException|StorageInspectionException $failure): string
    {
        $safe = [];
        foreach ($failure->diagnosticContext()->fields() as $field) {
            if (DiagnosticDisclosure::Safe === $field->disclosure()) {
                $safe[$field->key()] = $field->value();
            }
        }

        return $failure->getMessage() . (string) $failure . $failure->diagnosticId() . $failure->diagnosticTitle() . $failure->diagnosticRemediation() . $failure->diagnosticContext()->operation() . $failure->diagnosticContext()->violatedInvariant() . json_encode($safe, JSON_THROW_ON_ERROR);
    }

    /** @return array{ignore_args: string, cases: array{build: true, local: true, runtime: true, application: true, inspection: true}, owned_cleanup: true} */
    private function traceFailureEvidence(): array
    {
        $setting = ini_get('zend.exception_ignore_args');
        if (!in_array($setting, ['0', '1'], true)) {
            throw new RuntimeException('phase21_trace_setting_failed');
        }
        $workspace = new TemporaryStorageWorkspace($this->repositoryRoot);
        $current = self::current();

        $build = false;
        try {
            (new StorageProfileArtifactValidator())->validateStructure('{PrivateBuildMarker');
        } catch (InvalidStorageProfileArtifactException $failure) {
            $build = null !== $failure->getPrevious() && !str_contains(self::publicFailureText($failure), 'PrivateBuildMarker');
        }

        $missingRoot = $workspace->path('work/PrivateLocalMarker');
        $local = false;
        try {
            new LocalStorageRoot($missingRoot);
        } catch (InvalidLocalStorageConfigurationException $failure) {
            $local = !file_exists($missingRoot) && !str_contains(self::publicFailureText($failure), 'PrivateLocalMarker');
        }
        $localRoot = $workspace->directory('work/local');
        $localAdapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($localRoot), $current->artifact(), new StorageProfileName('demo'));
        $foreign = new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('trace-local-reject'), new StorageObjectIdentity(new StorageNamespace('wrong-objects'), new LogicalObjectIdentity('foreign')), new StorageByteCount(1));
        $local = $local && StorageReadOutcome::Rejected === $localAdapter->read($foreign)->result()->outcome() && [] === array_values(array_diff((array) scandir($localRoot), ['.', '..']));

        $runtimeCause = new RuntimeException('PrivateRuntimeMarker');
        $runtimeAdapter = new Phase21ScriptedAdapter($current, null, $runtimeCause);
        $runtimeSource = new Phase21ByteSource('abc');
        $runtime = false;
        try {
            self::scriptedRuntime($current, $runtimeAdapter)->create(self::writeRequest('trace-runtime-fail', 'runtime', 'abc'), $runtimeSource);
        } catch (StorageRuntimeOperationException $failure) {
            $runtime = $failure->getPrevious() === $runtimeCause && 1 === $runtimeAdapter->calls && 1 === $runtimeSource->closes && !str_contains(self::publicFailureText($failure), 'PrivateRuntimeMarker');
        }

        $applicationCause = new TypeError('PrivateApplicationMarker');
        $applicationAuthorizer = new Phase21Authorizer(true);
        $applicationAuthorizer->failure = $applicationCause;
        $applicationAdapter = new Phase21ScriptedAdapter($current, StorageMutationOutcome::Rejected);
        $applicationSource = new Phase21ByteSource('abc');
        $application = false;
        try {
            (new AuthorizedStorageRuntime(self::scriptedRuntime($current, $applicationAdapter), $applicationAuthorizer))->create(self::writeRequest('trace-application-fail', 'application', 'abc'), $applicationSource);
        } catch (StorageAuthorizationFailureException $failure) {
            $application = $failure->getPrevious() === $applicationCause && 1 === $applicationAuthorizer->calls && 0 === $applicationAdapter->calls && 0 === $applicationSource->reads && 0 === $applicationSource->closes && !str_contains(self::publicFailureText($failure), 'PrivateApplicationMarker');
        }

        $inspectionCause = new RuntimeException('PrivateInspectionMarker');
        $inspectionFailure = InvalidStorageInspectionException::invalid('PrivateInspectionMarker', $inspectionCause);
        $inspection = $inspectionFailure->getPrevious() === $inspectionCause && !str_contains(self::publicFailureText($inspectionFailure), 'PrivateInspectionMarker');
        if (!$build || !$local || !$runtime || !$application || !$inspection) {
            throw new RuntimeException('phase21_trace_failure');
        }
        $root = $workspace->root();
        $workspace->cleanup();
        if (file_exists($root) || is_link($root)) {
            throw new RuntimeException('phase21_trace_cleanup_failed');
        }

        return ['ignore_args' => $setting, 'cases' => ['build' => true, 'local' => true, 'runtime' => true, 'application' => true, 'inspection' => true], 'owned_cleanup' => true];
    }

    private function processIntegrity(): void
    {
        $first = $this->captureChild('fresh-one', '--child-success');
        $second = $this->captureChild('fresh-two', '--child-success');
        $expected = (new StorageProfileBuilder())->build(self::profiles());
        foreach ([$first, $second] as $result) {
            $data = json_decode($this->rawStdout($result), true, 8, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['status'], $data['source'], $data['build'], $data['artifact'], $data['bytes_base64']) || !is_string($data['bytes_base64'])) {
                throw new RuntimeException('phase21_capture_failed');
            }
            $this->check(0 === $result->exitCode && !$result->timedOut && $result->retired && !$result->stdoutOverflow && !$result->stderrOverflow);
            $this->check('ok' === $data['status'] && $expected->sourceIdentity()->value() === $data['source'] && $expected->buildIdentity()->value() === $data['build'] && $expected->artifactIdentity()->value() === $data['artifact']);
            $this->check($expected->artifact()->bytes() === base64_decode($data['bytes_base64'], true));
            $this->check('' === $this->rawStderr($result));
            $this->check($result->stdoutRawBytes === $result->stdoutBytes && 0 === $result->stderrRawBytes && is_file(dirname($result->stdoutPath) . '/result.json'));
            $this->acknowledge($result);
        }
        $this->check($this->rawStdout($first) === $this->rawStdout($second));
        $failed = $this->captureChild('nonzero', '--child-failure');
        $this->check(17 === $failed->exitCode && '' === $this->rawStdout($failed) && "controlled-child-failure\n" === $this->rawStderr($failed));
        $this->check(is_file($failed->stderrPath));
        $this->acknowledge($failed);
        $malformed = $this->captureChild('malformed', '--child-malformed');
        $this->check(0 === $malformed->exitCode);
        $this->fails(fn() => json_decode($this->rawStdout($malformed), true, 8, JSON_THROW_ON_ERROR));
        $this->acknowledge($malformed);
        foreach ([['oversize-out', '--child-stdout-overflow', true], ['oversize-err', '--child-stderr-overflow', false]] as [$label, $mode, $stdout]) {
            $result = $this->captureChild($label, $mode);
            $this->check($stdout ? $result->stdoutOverflow : $result->stderrOverflow);
            $this->check($stdout ? self::STREAM_LIMIT === $result->stdoutBytes : self::STREAM_LIMIT === $result->stderrBytes);
            $this->check($stdout ? self::STREAM_LIMIT + 1024 === $result->stdoutRawBytes && 1024 === $result->stdoutOmittedBytes : self::STREAM_LIMIT + 1024 === $result->stderrRawBytes && 1024 === $result->stderrOmittedBytes);
            $metadata = json_decode((string) file_get_contents(dirname($result->stdoutPath) . '/result.json'), true, 8, JSON_THROW_ON_ERROR);
            $this->check(is_array($metadata) && $result->processId === ($metadata['pid'] ?? null) && $result->stdoutRawBytes === ($metadata['stdout_raw_bytes'] ?? null) && $result->stderrRawBytes === ($metadata['stderr_raw_bytes'] ?? null));
            $this->check($result->retired);
            $this->acknowledge($result);
        }
        $timeout = $this->captureChild('timeout', '--child-timeout', 1);
        $this->check($timeout->timedOut && $timeout->retired);
        $this->check(0 === $timeout->stdoutRawBytes && 0 === $timeout->stderrRawBytes && is_file(dirname($timeout->stdoutPath) . '/result.json'));
        $this->acknowledge($timeout);
        $assertion = $this->captureChild('assertion', '--child-assertion-failure');
        $this->check(23 === $assertion->exitCode && "controlled-assertion-failure\n" === $this->rawStderr($assertion));
        $this->check(is_file($assertion->stderrPath) && false === $this->captures['assertion']);
        $this->check(is_file(dirname($assertion->stdoutPath) . '/result.json'));
        $this->fails(fn() => $this->cleanup());
        $this->check(is_file($assertion->stderrPath));
        $this->acknowledge($assertion);
        $third = $this->captureChild('fresh-after-failure', '--child-success');
        $this->check(0 === $third->exitCode && $this->rawStdout($third) === $this->rawStdout($first));
        $this->acknowledge($third);
    }

    private function captureSelfTest(): void
    {
        $normal = $this->captureChild('self-test-normal', '--child-capture-ok');
        $this->check(0 === $normal->exitCode && $normal->retired && !$normal->timedOut && "capture-ok\n" === $this->rawStdout($normal) && '' === $this->rawStderr($normal));
        $this->check(11 === $normal->stdoutRawBytes && 11 === $normal->stdoutBytes && 0 === $normal->stdoutOmittedBytes);
        $this->acknowledge($normal);

        $failed = $this->captureChild('self-test-nonzero', '--child-failure');
        $this->check(17 === $failed->exitCode && '' === $this->rawStdout($failed) && "controlled-child-failure\n" === $this->rawStderr($failed));
        $this->acknowledge($failed);

        foreach ([['self-test-overflow-out', '--child-stdout-overflow', true], ['self-test-overflow-err', '--child-stderr-overflow', false]] as [$label, $mode, $isStdout]) {
            $result = $this->captureChild($label, $mode);
            $this->check(0 === $result->exitCode && $result->retired && !$result->timedOut);
            $this->check($isStdout ? $result->stdoutRawBytes === self::STREAM_LIMIT + 1024 && self::STREAM_LIMIT === $result->stdoutBytes && 1024 === $result->stdoutOmittedBytes && $result->stdoutOverflow && 0 === $result->stderrRawBytes : $result->stderrRawBytes === self::STREAM_LIMIT + 1024 && self::STREAM_LIMIT === $result->stderrBytes && 1024 === $result->stderrOmittedBytes && $result->stderrOverflow && 0 === $result->stdoutRawBytes);
            $this->check($isStdout ? str_repeat('O', self::STREAM_LIMIT) === $this->rawStdout($result) : str_repeat('E', self::STREAM_LIMIT) === $this->rawStderr($result));
            $this->acknowledge($result);
        }

        $burst = $this->captureChild('self-test-burst', '--child-burst');
        $this->check(0 === $burst->exitCode && 32_768 === $burst->stdoutRawBytes && 32_768 === $burst->stderrRawBytes && str_repeat('A', 32_768) === $this->rawStdout($burst) && str_repeat('B', 32_768) === $this->rawStderr($burst));
        $this->acknowledge($burst);

        $timeout = $this->captureChild('self-test-timeout', '--child-timeout', 1);
        $this->check($timeout->timedOut && $timeout->retired && 0 === $timeout->stdoutRawBytes && 0 === $timeout->stderrRawBytes);
        $this->acknowledge($timeout);
        $following = $this->captureChild('self-test-after-timeout', '--child-capture-ok');
        $this->check(0 === $following->exitCode && "capture-ok\n" === $this->rawStdout($following));
        $this->acknowledge($following);

        $malformed = $this->captureChild('self-test-malformed', '--child-malformed');
        $this->fails(fn() => json_decode($this->rawStdout($malformed), true, 8, JSON_THROW_ON_ERROR));
        $this->check("{bad json\n" === $this->rawStdout($malformed) && is_file(dirname($malformed->stdoutPath) . '/result.json'));
        $this->acknowledge($malformed);

        $assertion = $this->captureChild('self-test-assertion', '--child-assertion-failure');
        $before = hash_file('sha256', $assertion->stderrPath);
        $this->fails(fn() => $this->check(false));
        $this->check($before === hash_file('sha256', $assertion->stderrPath) && is_file(dirname($assertion->stdoutPath) . '/result.json'));
        $this->acknowledge($assertion);
        $independent = $this->captureChild('self-test-independent', '--child-capture-ok');
        $this->check(0 === $independent->exitCode && "capture-ok\n" === $this->rawStdout($independent));
        $this->acknowledge($independent);

        $workspace = $this->owned();
        $sentinel = $workspace->createInterferenceFixture('capture/self-test-sentinel', 'unregistered-sentinel');
        $captureHash = hash_file('sha256', $normal->stdoutPath);
        $this->fails(fn() => $workspace->cleanup());
        $this->check('unregistered-sentinel' === file_get_contents($sentinel) && $captureHash === hash_file('sha256', $normal->stdoutPath) && is_file(dirname($normal->stdoutPath) . '/result.json'));
        $workspace->removeInterferenceFixture('capture/self-test-sentinel');
        $repeat = $this->captureChild('self-test-repeat', '--child-capture-ok');
        $this->check(0 === $repeat->exitCode && "capture-ok\n" === $this->rawStdout($repeat) && $repeat->processId !== $normal->processId);
        $this->acknowledge($repeat);
    }

    private function cleanup(): void
    {
        $this->check(!in_array(false, $this->captures, true));
        $workspace = $this->owned();
        $root = $workspace->root();
        $workspace->cleanup();
        $this->workspace = null;
        $this->check(!file_exists($root) && !is_link($root));
    }

    private function captureChild(string $label, string $mode, int $timeoutSeconds = self::PROCESS_TIMEOUT_SECONDS, ?string $traceMode = null): ProcessResult
    {
        if (1 !== preg_match('/\A[a-z0-9-]+\z/D', $label) || isset($this->captures[$label]) || count($this->captures) >= 32 || [] !== self::$uncertainProcesses || $timeoutSeconds < 1 || $timeoutSeconds > self::PROCESS_TIMEOUT_SECONDS || !in_array($mode, ['--child-capture-ok', '--child-success', '--child-failure', '--child-malformed', '--child-stdout-overflow', '--child-stderr-overflow', '--child-timeout', '--child-assertion-failure', '--child-trace-mode', '--child-burst'], true)) {
            throw new RuntimeException('phase21_process_failed');
        }
        $workspace = $this->owned();
        $stdoutPath = $workspace->exclusiveFile('capture/' . $label . '/stdout.bin');
        $stderrPath = $workspace->exclusiveFile('capture/' . $label . '/stderr.bin');
        $arguments = [PHP_BINARY];
        if (null !== $traceMode) {
            if (!in_array($traceMode, ['0', '1'], true)) {
                throw new RuntimeException('phase21_process_failed');
            }
            $arguments[] = '-d';
            $arguments[] = 'zend.exception_ignore_args=' . $traceMode;
        }
        $arguments[] = __DIR__ . '/run.php';
        $arguments[] = $mode;
        $environment = [];
        foreach (['PATH', 'PHPRC', 'PHP_INI_SCAN_DIR', 'SystemRoot', 'WINDIR', 'COMPOSER_DISABLE_NETWORK'] as $name) {
            $value = getenv($name);
            if (is_string($value)) {
                $environment[$name] = $value;
            }
        }
        $environment['TEMP'] = $workspace->path('work');
        $environment['TMP'] = $environment['TEMP'];
        $pipes = [];
        $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['file', $stdoutPath, 'ab'], 2 => ['file', $stderrPath, 'ab']], $pipes, $this->repositoryRoot, $environment, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('phase21_process_failed');
        }
        fclose($pipes[0]);
        $started = hrtime(true);
        $deadline = $started + $timeoutSeconds * 1_000_000_000;
        $timedOut = false;
        $exit = -1;
        $pid = 0;
        $retired = false;
        $retirementDeadline = null;
        try {
            do {
                $status = proc_get_status($process);
                if ($status['pid'] <= 0) {
                    throw new RuntimeException('phase21_process_failed');
                }
                if (0 !== $pid && $status['pid'] !== $pid) {
                    throw new RuntimeException('phase21_process_failed');
                }
                $pid = $status['pid'];
                if ($status['exitcode'] >= 0) {
                    $exit = $status['exitcode'];
                }
                self::captureSize($workspace, $stdoutPath);
                self::captureSize($workspace, $stderrPath);
                if (!$status['running']) {
                    $retired = true;
                    break;
                }
                if (null === $retirementDeadline && hrtime(true) >= $deadline) {
                    $timedOut = true;
                    proc_terminate($process);
                    $retirementDeadline = hrtime(true) + 30_000_000_000;
                }
                if (null !== $retirementDeadline && hrtime(true) >= $retirementDeadline) {
                    throw new RuntimeException('phase21_child_retirement_uncertain_pid_' . $pid);
                }
                usleep(10_000);
            } while (true);
        } catch (Throwable $failure) {
            proc_terminate($process);
            $stopBy = hrtime(true) + 30_000_000_000;
            do {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $retired = true;
                    break;
                }
                usleep(10_000);
            } while (hrtime(true) < $stopBy);
            if (!$retired) {
                self::$uncertainProcesses[] = $process;
                throw new RuntimeException('phase21_child_retirement_uncertain_pid_' . $pid, 0, $failure);
            }
            proc_close($process);
            throw $failure;
        }
        $stdoutRawBytes = self::captureSize($workspace, $stdoutPath);
        $stderrRawBytes = self::captureSize($workspace, $stderrPath);
        $closed = proc_close($process);
        if ($exit < 0) {
            $exit = $closed;
        }
        $stdoutBytes = min($stdoutRawBytes, self::STREAM_LIMIT);
        $stderrBytes = min($stderrRawBytes, self::STREAM_LIMIT);
        $result = new ProcessResult($exit, $stdoutPath, $stderrPath, $stdoutBytes, $stderrBytes, $stdoutRawBytes, $stderrRawBytes, $stdoutRawBytes - $stdoutBytes, $stderrRawBytes - $stderrBytes, $stdoutRawBytes > self::STREAM_LIMIT, $stderrRawBytes > self::STREAM_LIMIT, $timedOut, $retired, $pid);
        $metadata = json_encode(['exit' => $exit, 'pid' => $pid, 'timed_out' => $timedOut, 'retired' => $retired, 'stdout_raw_bytes' => $stdoutRawBytes, 'stderr_raw_bytes' => $stderrRawBytes, 'stdout_bytes' => $stdoutBytes, 'stderr_bytes' => $stderrBytes, 'stdout_omitted_bytes' => $result->stdoutOmittedBytes, 'stderr_omitted_bytes' => $result->stderrOmittedBytes, 'stdout_overflow' => $result->stdoutOverflow, 'stderr_overflow' => $result->stderrOverflow, 'elapsed_ns' => hrtime(true) - $started], JSON_THROW_ON_ERROR);
        $workspace->write('capture/' . $label . '/result.json', $metadata);
        $this->captures[$label] = false;
        return $result;
    }

    private static function captureSize(TemporaryStorageWorkspace $workspace, string $path): int
    {
        $workspace->assertFile($path);
        clearstatcache(true, $path);
        $size = filesize($path);
        if (false === $size || $size > self::RAW_LIMIT) {
            throw new RuntimeException('phase21_capture_failed');
        }
        return $size;
    }

    private function rawStdout(ProcessResult $result): string
    {
        $this->owned()->assertFile($result->stdoutPath);
        if (self::captureSize($this->owned(), $result->stdoutPath) !== $result->stdoutRawBytes) {
            throw new RuntimeException('phase21_capture_failed');
        }
        if ($result->stdoutBytes < 0 || $result->stdoutBytes > self::STREAM_LIMIT) {
            throw new RuntimeException('phase21_capture_failed');
        }
        $bytes = 0 === $result->stdoutBytes ? '' : file_get_contents($result->stdoutPath, false, null, 0, $result->stdoutBytes);
        if (false === $bytes || strlen($bytes) !== $result->stdoutBytes) {
            throw new RuntimeException('phase21_capture_failed');
        }
        return $bytes;
    }

    private function rawStderr(ProcessResult $result): string
    {
        $this->owned()->assertFile($result->stderrPath);
        if (self::captureSize($this->owned(), $result->stderrPath) !== $result->stderrRawBytes) {
            throw new RuntimeException('phase21_capture_failed');
        }
        if ($result->stderrBytes < 0 || $result->stderrBytes > self::STREAM_LIMIT) {
            throw new RuntimeException('phase21_capture_failed');
        }
        $bytes = 0 === $result->stderrBytes ? '' : file_get_contents($result->stderrPath, false, null, 0, $result->stderrBytes);
        if (false === $bytes || strlen($bytes) !== $result->stderrBytes) {
            throw new RuntimeException('phase21_capture_failed');
        }
        return $bytes;
    }

    private function acknowledge(ProcessResult $result): void
    {
        $label = basename(dirname($result->stdoutPath));
        if (!array_key_exists($label, $this->captures) || !$result->retired) {
            throw new RuntimeException('phase21_capture_failed');
        }
        $this->rawStdout($result);
        $this->rawStderr($result);
        $this->captures[$label] = true;
    }

    private static function identity(string $logical): StorageObjectIdentity
    {
        return new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity($logical));
    }

    private static function writeRequest(string $operationId, string $logical, string $bytes, StorageOperation $operation = StorageOperation::Create, ?StorageMutationCondition $condition = null): StorageWriteRequest
    {
        return new StorageWriteRequest(
            ContractVersion::Version1,
            new StorageOperationIdentity($operationId),
            $operation,
            self::identity($logical),
            new StorageByteCount(strlen($bytes)),
            new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)),
            new StorageMediaType('text/plain'),
            new StorageMetadata([]),
            $condition ?? StorageMutationCondition::ifAbsent(),
        );
    }

    private static function readRequest(string $operationId, string $logical): StorageReadRequest
    {
        return new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity($operationId), self::identity($logical), new StorageByteCount(4096));
    }

    private function owned(): TemporaryStorageWorkspace
    {
        return $this->workspace ?? throw new RuntimeException('phase21_workspace_failed');
    }

    private function repositoryFingerprint(): string
    {
        $root = str_replace('\\', '/', $this->repositoryRoot);
        $relativeFiles = ['.editorconfig', '.gitattributes', '.gitignore', '.php-cs-fixer.dist.php', 'AGENTS.md', 'ARCHITECTURE.md', 'CONTRIBUTING.md', 'README.md', 'SECURITY.md', 'composer.json', 'composer.lock', 'phpstan.neon.dist', 'phpunit.xml.dist'];
        foreach (['docs', 'packages', 'skeletons', 'tests'] as $directory) {
            $base = $root . '/' . $directory;
            if (!is_dir($base) || is_link($base)) {
                throw new RuntimeException('phase21_repository_fingerprint_failed');
            }
            $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($entries as $entry) {
                if (!$entry instanceof SplFileInfo || $entry->isLink()) {
                    throw new RuntimeException('phase21_repository_fingerprint_failed');
                }
                if ($entry->isDir()) {
                    continue;
                }
                if (!$entry->isFile()) {
                    throw new RuntimeException('phase21_repository_fingerprint_failed');
                }
                $path = str_replace('\\', '/', $entry->getPathname());
                $relativeFiles[] = substr($path, strlen($root) + 1);
            }
        }
        sort($relativeFiles, SORT_STRING);
        $digest = hash_init('sha256');
        foreach ($relativeFiles as $relative) {
            $path = $root . '/' . $relative;
            if (!is_file($path) || is_link($path)) {
                throw new RuntimeException('phase21_repository_fingerprint_failed');
            }
            $size = filesize($path);
            $fileDigest = hash_file('sha256', $path);
            if (false === $size || false === $fileDigest) {
                throw new RuntimeException('phase21_repository_fingerprint_failed');
            }
            hash_update($digest, $relative . "\0" . $size . "\0" . $fileDigest . "\n");
        }

        return hash_final($digest);
    }

    private function recordCaseCount(string $mode): void
    {
        $target = getenv('KONGONO_PHASE21_CASE_EVIDENCE');
        if (false === $target) {
            return;
        }
        $temporary = getenv('TEMP');
        if (false === $temporary || 1 !== preg_match('/\Aphase21-[a-z0-9-]+-cases\.json\z/D', basename($target))) {
            throw new RuntimeException('phase21_case_evidence_failed');
        }
        $private = realpath($temporary);
        $evidence = false === $private ? false : realpath(dirname($private) . '/evidence');
        $parent = realpath(dirname($target));
        if (false === $evidence || false === $parent || 0 !== strcasecmp(str_replace('\\', '/', $evidence), str_replace('\\', '/', $parent)) || file_exists($target) || is_link($target)) {
            throw new RuntimeException('phase21_case_evidence_failed');
        }
        $bytes = json_encode(['assertions' => $this->cases, 'mode' => $mode], JSON_THROW_ON_ERROR) . "\n";
        $handle = fopen($target, 'xb');
        if (false === $handle) {
            throw new RuntimeException('phase21_case_evidence_failed');
        }
        $written = fwrite($handle, $bytes);
        $closed = fclose($handle);
        if (strlen($bytes) !== $written || !$closed) {
            throw new RuntimeException('phase21_case_evidence_failed');
        }
    }

    private function check(bool $condition): void
    {
        ++$this->cases;
        if (!$condition) {
            throw new RuntimeException('phase21_acceptance_failed');
        }
    }

    /** @param callable(): mixed $operation */
    private function fails(callable $operation, ?string $expectedClass = null): void
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            $this->check(null === $expectedClass || $failure instanceof $expectedClass);
            return;
        }
        $this->check(false);
    }
}

final class Phase21ByteSource implements StorageByteSource
{
    private int $offset = 0;
    public int $reads = 0;
    public int $closes = 0;

    public function __construct(private readonly string $bytes, private readonly bool $failRead = false, private readonly bool $failClose = false, private readonly ?Throwable $closeFailure = null, private readonly bool $overrun = false) {}

    public function read(int $maximumBytes): ?StorageChunk
    {
        ++$this->reads;
        if ($this->failRead) {
            throw new RuntimeException('private-read-marker');
        }
        if ($this->offset >= strlen($this->bytes)) {
            return null;
        }
        $chunk = substr($this->bytes, $this->offset, $maximumBytes + ($this->overrun ? 1 : 0));
        $this->offset += strlen($chunk);
        return new StorageChunk($chunk);
    }

    public function exhausted(): bool
    {
        return $this->offset >= strlen($this->bytes);
    }

    public function close(): void
    {
        ++$this->closes;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
        if ($this->failClose) {
            throw new RuntimeException('private-close-marker');
        }
    }

    public function closed(): bool
    {
        return $this->closes > 0;
    }
}

final class Phase21Authorizer implements StorageOperationAuthorizer
{
    public int $calls = 0;
    public ?StorageReadRequest $lastRead = null;
    public StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest|null $lastRequest = null;
    public ?Throwable $failure = null;
    /** @var (\Closure(StorageWriteRequest): void)|null */
    public ?\Closure $onAuthorize = null;

    public function __construct(public bool $allow) {}

    public function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): bool
    {
        ++$this->calls;
        $this->lastRequest = $request;
        if (null !== $this->failure) {
            throw $this->failure;
        }
        if ($request instanceof StorageWriteRequest && null !== $this->onAuthorize) {
            ($this->onAuthorize)($request);
        }
        return $this->allow;
    }
}

final class Phase21ByteSink implements StorageByteSink
{
    public string $bytes = '';
    public int $aborts = 0;
    private bool $isClosed = false;

    public function write(StorageChunk $chunk): void
    {
        $this->bytes .= $chunk->bytes();
    }

    public function close(): void
    {
        $this->isClosed = true;
    }

    public function abort(): void
    {
        ++$this->aborts;
        $this->isClosed = true;
    }

    public function closed(): bool
    {
        return $this->isClosed;
    }
}

final class Phase21ScriptedAdapter implements StorageAdapter
{
    public int $calls = 0;
    public int $simulatedEffects = 0;

    public function __construct(
        private readonly CurrentStorageApplicationBuild $current,
        private readonly ?StorageMutationOutcome $writeOutcome,
        private readonly ?Throwable $writeFailure = null,
    ) {}

    public function descriptor(): StorageAdapterDescriptor
    {
        return new StorageAdapterDescriptor(AdapterContractVersion::Version1, new StorageAdapterIdentity('scripted', 1), $this->current->artifact(), new StorageProfileName('demo'), new StorageAdapterGuaranteeSet([]), new StorageAdapterLimitationSet([]));
    }

    public function read(StorageReadRequest $request): StorageAdapterRead
    {
        ++$this->calls;
        return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent));
    }

    public function write(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        ++$this->calls;
        if (null !== $this->writeFailure) {
            throw $this->writeFailure;
        }
        if (StorageMutationOutcome::Uncertain === $this->writeOutcome) {
            ++$this->simulatedEffects;
        }
        return new StorageMutationResult($this->writeOutcome ?? StorageMutationOutcome::Rejected);
    }

    public function delete(StorageDeleteRequest $request): StorageMutationResult
    {
        ++$this->calls;
        return new StorageMutationResult(StorageMutationOutcome::Absent);
    }

    public function list(StorageListRequest $request): StorageListResult
    {
        ++$this->calls;
        return new StorageListResult(StorageListOutcome::Completed, new StorageListPage([], true));
    }
}
