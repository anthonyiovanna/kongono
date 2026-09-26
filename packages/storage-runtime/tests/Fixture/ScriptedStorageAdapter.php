<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Fixture;

use Closure;
use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageAdapterContract\AdapterContractVersion;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Content\StorageByteCount;
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
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListPage;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageContract\Version\StorageObjectVersion;
use Kongono\StorageRuntime\Binding\StorageRuntimeBinding;
use LogicException;

final class ScriptedStorageAdapter implements StorageAdapter
{
    public int $calls = 0;

    /**
     * @param (Closure(StorageReadRequest): mixed)|null $readScript
     * @param (Closure(StorageWriteRequest, StorageByteSource): mixed)|null $writeScript
     * @param (Closure(StorageDeleteRequest): mixed)|null $deleteScript
     * @param (Closure(StorageListRequest): mixed)|null $listScript
     */
    public function __construct(
        private readonly StorageAdapterDescriptor $descriptor,
        private readonly ?Closure $readScript = null,
        private readonly ?Closure $writeScript = null,
        private readonly ?Closure $deleteScript = null,
        private readonly ?Closure $listScript = null,
    ) {}

    public function descriptor(): StorageAdapterDescriptor
    {
        return $this->descriptor;
    }

    public function read(StorageReadRequest $request): StorageAdapterRead
    {
        ++$this->calls;
        $result = null === $this->readScript ? new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent)) : ($this->readScript)($request);
        if (!$result instanceof StorageAdapterRead) {
            throw new LogicException('Scripted read callback returned an invalid result.');
        }

        return $result;
    }

    public function write(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        ++$this->calls;
        if (null !== $this->writeScript) {
            $result = ($this->writeScript)($request, $source);
            if (!$result instanceof StorageMutationResult) {
                throw new LogicException('Scripted write callback returned an invalid result.');
            }

            return $result;
        }
        while (null !== $source->read(4)) {
        }
        return new StorageMutationResult(StorageMutationOutcome::Completed, new StorageObjectVersion('version-one'), $request->contentDigest());
    }

    public function delete(StorageDeleteRequest $request): StorageMutationResult
    {
        ++$this->calls;
        $result = null === $this->deleteScript ? new StorageMutationResult(StorageMutationOutcome::Completed, $request->condition()->version()) : ($this->deleteScript)($request);
        if (!$result instanceof StorageMutationResult) {
            throw new LogicException('Scripted delete callback returned an invalid result.');
        }

        return $result;
    }

    public function list(StorageListRequest $request): StorageListResult
    {
        ++$this->calls;
        $result = null === $this->listScript ? new StorageListResult(StorageListOutcome::Completed, new StorageListPage([], true)) : ($this->listScript)($request);
        if (!$result instanceof StorageListResult) {
            throw new LogicException('Scripted list callback returned an invalid result.');
        }

        return $result;
    }

    public static function set(int $maxObjectBytes = 16): StorageProfileSet
    {
        return new StorageProfileSet([new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName('primary'),
            new StorageNamespace('runtime-tests'),
            new StorageCapabilitySet(StorageCapability::cases()),
            StorageDigestAlgorithm::Sha256,
            new StorageProfileLimits(4, $maxObjectBytes, 3),
        )]);
    }

    public static function artifact(?StorageProfileSet $set = null): StorageProfileArtifact
    {
        return (new StorageProfileBuilder())->build($set ?? self::set())->artifact();
    }

    public static function makeDescriptor(?StorageProfileArtifact $artifact = null, string $profile = 'primary', string $identity = 'scripted'): StorageAdapterDescriptor
    {
        return new StorageAdapterDescriptor(AdapterContractVersion::Version1, new StorageAdapterIdentity($identity, 1), $artifact ?? self::artifact(), new StorageProfileName($profile), new StorageAdapterGuaranteeSet([]), new StorageAdapterLimitationSet([]));
    }

    public static function binding(?self $adapter = null, ?StorageProfileSet $set = null): StorageRuntimeBinding
    {
        $set ??= self::set();
        $artifact = self::artifact($set);
        $adapter ??= new self(self::makeDescriptor($artifact));
        return new StorageRuntimeBinding($set, $artifact->bytes(), new StorageProfileName('primary'), new StorageAdapterIdentity('scripted', 1), $adapter);
    }

    public static function identity(): StorageObjectIdentity
    {
        return new StorageObjectIdentity(new StorageNamespace('runtime-tests'), new LogicalObjectIdentity('object-one'));
    }

    public static function readRequest(int $maximum = 16): StorageReadRequest
    {
        return new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('read-one'), self::identity(), new StorageByteCount($maximum));
    }

    public static function writeRequest(string $bytes, StorageOperation $operation = StorageOperation::Create): StorageWriteRequest
    {
        return new StorageWriteRequest(ContractVersion::Version1, new StorageOperationIdentity($operation->value . '-one'), $operation, self::identity(), new StorageByteCount(strlen($bytes)), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)), new StorageMediaType('text/plain'), new StorageMetadata([]), StorageOperation::Create === $operation ? StorageMutationCondition::ifAbsent() : StorageMutationCondition::ifVersion(new StorageObjectVersion('version-zero')));
    }
}
