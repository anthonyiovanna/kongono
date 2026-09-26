<?php

declare(strict_types=1);

namespace StorageMinimalApp;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageApplication\Runtime\StorageApplicationRuntimeFactory;
use Kongono\StorageBuild\Profile\StorageProfileName;
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
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageContract\Version\StorageObjectVersion;
use RuntimeException;

/** Executes one bounded create/read/replace/delete sequence against an explicit root. */
final class StorageDemo
{
    /** @return array{create:string,read:string,replace:string,delete:string} */
    public function run(string $applicationRoot, string $sourceFile, string $outputDirectory, string $objectRoot): array
    {
        $current = (new StorageWorkflow($applicationRoot, $sourceFile, $outputDirectory))->check();
        $profile = new StorageProfileName('demo');
        $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($objectRoot), $current->artifact(), $profile);
        $runtime = (new StorageApplicationRuntimeFactory())->create(
            $current,
            $profile,
            new StorageAdapterIdentity('contained-local-filesystem', 1),
            $adapter,
            new ExampleStorageAuthorizer(),
        );
        $identity = new StorageObjectIdentity(new StorageNamespace('demo-objects'), new LogicalObjectIdentity('demo-object'));
        $status = ['create' => 'skipped', 'read' => 'skipped', 'replace' => 'skipped', 'delete' => 'skipped'];
        $first = 'storage demo';
        $created = $runtime->create($this->writeRequest(StorageOperation::Create, 'demo-create', $identity, $first, StorageMutationCondition::ifAbsent()), new ExampleByteSource($first));
        $status['create'] = $created->outcome()->value;
        if (StorageMutationOutcome::Completed !== $created->outcome()) {
            return $status;
        }
        $createdVersion = $created->resultingVersion();
        if (!$createdVersion instanceof StorageObjectVersion) {
            throw new RuntimeException('Completed create omitted version.');
        }
        $read = $runtime->read(new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('demo-read'), $identity, new StorageByteCount(4096)));
        $status['read'] = $read->result()->outcome()->value;
        if (StorageReadOutcome::Completed !== $read->result()->outcome()) {
            return $status;
        }
        $source = $read->source();
        if (!$source instanceof StorageByteSource) {
            throw new RuntimeException('Completed read omitted source.');
        }
        $received = '';
        try {
            while (null !== ($chunk = $source->read(1024))) {
                $received .= $chunk->bytes();
                if (strlen($received) > 4096) {
                    throw new RuntimeException('Read exceeded the example bound.');
                }
            }
        } finally {
            $source->close();
        }
        if ($received !== $first) {
            throw new RuntimeException('Read evidence did not match the example bytes.');
        }
        $second = 'storage demo updated';
        $replaced = $runtime->replace($this->writeRequest(StorageOperation::Replace, 'demo-replace', $identity, $second, StorageMutationCondition::ifVersion($createdVersion)), new ExampleByteSource($second));
        $status['replace'] = $replaced->outcome()->value;
        if (StorageMutationOutcome::Completed !== $replaced->outcome()) {
            return $status;
        }
        $replacedVersion = $replaced->resultingVersion();
        if (!$replacedVersion instanceof StorageObjectVersion) {
            throw new RuntimeException('Completed replace omitted version.');
        }
        $deleted = $runtime->delete(new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('demo-delete'), $identity, StorageMutationCondition::ifVersion($replacedVersion)));
        $status['delete'] = $deleted->outcome()->value;
        return $status;
    }

    private function writeRequest(StorageOperation $operation, string $operationName, StorageObjectIdentity $identity, string $bytes, StorageMutationCondition $condition): StorageWriteRequest
    {
        return new StorageWriteRequest(
            ContractVersion::Version1,
            new StorageOperationIdentity($operationName),
            $operation,
            $identity,
            new StorageByteCount(strlen($bytes)),
            new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)),
            new StorageMediaType('text/plain'),
            new StorageMetadata([]),
            $condition,
        );
    }
}
