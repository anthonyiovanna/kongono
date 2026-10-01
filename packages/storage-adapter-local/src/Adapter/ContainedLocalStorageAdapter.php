<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageAdapterContract\AdapterContractVersion;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuarantee;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitation;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use Kongono\StorageAdapterLocal\Internal\CanonicalJson;
use Kongono\StorageAdapterLocal\Internal\LocalContinuationCodec;
use Kongono\StorageAdapterLocal\Internal\LocalObjectHeader;
use Kongono\StorageAdapterLocal\Internal\LocalObjectLock;
use Kongono\StorageAdapterLocal\Internal\LocalObjectReader;
use Kongono\StorageAdapterLocal\Internal\LocalObjectWriter;
use Kongono\StorageAdapterLocal\Internal\LocalPathLayout;
use Kongono\StorageAdapterLocal\Internal\LocalReadSource;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Metadata\StorageMetadataEntry;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListEntry;
use Kongono\StorageContract\Result\StorageListPage;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageContract\Version\StorageObjectVersion;
use Throwable;

/** Implements the explicitly rooted contained local-filesystem adapter. */
#[Api(ApiStatus::Experimental)]
final class ContainedLocalStorageAdapter implements StorageAdapter
{
    private readonly StorageAdapterDescriptor $descriptor;
    private readonly LocalPathLayout $layout;

    public function __construct(LocalStorageRoot $root, StorageProfileArtifact $artifact, StorageProfileName $profileName)
    {
        $this->descriptor = new StorageAdapterDescriptor(
            AdapterContractVersion::Version1,
            new StorageAdapterIdentity('contained-local-filesystem', 1),
            $artifact,
            $profileName,
            new StorageAdapterGuaranteeSet(StorageAdapterGuarantee::cases()),
            new StorageAdapterLimitationSet(StorageAdapterLimitation::cases()),
        );
        $this->layout = new LocalPathLayout($root);
    }

    public function descriptor(): StorageAdapterDescriptor
    {
        return $this->descriptor;
    }

    public function read(StorageReadRequest $request): StorageAdapterRead
    {
        if (!$this->requestAccepted($request->contractVersion(), $request->objectIdentity()->namespace()->value())) {
            return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Rejected));
        }
        if (!$this->supports(StorageCapability::Read)) {
            return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Unsupported));
        }
        $profile = $this->profile();
        if ($request->maximumByteCount()->value() > $profile->limits()->maxObjectBytes()) {
            return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Rejected));
        }
        $namespace = $request->objectIdentity()->namespace()->value();
        $logical = $request->objectIdentity()->logicalIdentity()->value();
        $lock = null;
        $handle = null;
        $outcome = StorageReadOutcome::Unavailable;
        try {
            $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, false);
            $path = $this->layout->assertContained($this->layout->objectPath($namespace, $logical), true);
            if (!file_exists($path)) {
                $outcome = StorageReadOutcome::Absent;
            } else {
                $record = LocalObjectReader::open($path, $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
                $handle = $record['handle'];
                if ($record['header']->byteCount() > $request->maximumByteCount()->value() || $record['header']->byteCount() > $profile->limits()->maxObjectBytes()) {
                    $outcome = StorageReadOutcome::Rejected;
                } else {
                    $result = new StorageReadResult(StorageReadOutcome::Completed, $record['header']->evidence());
                    $source = new LocalReadSource($handle, $lock, $record['header']->byteCount(), $profile->limits()->maxChunkBytes());
                    $returned = new StorageAdapterRead($request, $result, $source);
                    return $returned;
                }
            }
        } catch (Throwable) {
            $outcome = StorageReadOutcome::Unavailable;
        }
        if (is_resource($handle)) {
            try {
                if (!fclose($handle)) {
                    $outcome = StorageReadOutcome::Unavailable;
                }
            } catch (Throwable) {
                $outcome = StorageReadOutcome::Unavailable;
            }
        }
        if (null !== $lock && !$lock->closed()) {
            try {
                $lock->close();
            } catch (Throwable) {
                $outcome = StorageReadOutcome::Unavailable;
            }
        }
        return new StorageAdapterRead($request, new StorageReadResult($outcome));
    }

    public function write(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        $operation = $request->operation();
        if (!$this->requestAccepted($request->contractVersion(), $request->objectIdentity()->namespace()->value()) || $source->closed()) {
            return new StorageMutationResult(StorageMutationOutcome::Rejected);
        }
        $required = StorageOperation::Create === $operation ? StorageCapability::Create : StorageCapability::Replace;
        if (!$this->supports($required) || (StorageOperation::Replace === $operation && !$this->supports(StorageCapability::ConditionalMutation))) {
            return new StorageMutationResult(StorageMutationOutcome::Unsupported);
        }
        $profile = $this->profile();
        $declared = $request->declaredByteCount()->value();
        if ($declared > $profile->limits()->maxObjectBytes() || !$this->supports(StorageCapability::ContentDigest) || $profile->digestAlgorithm() !== $request->contentDigest()->algorithm() || ([] !== $request->metadata()->entries() && !$this->supports(StorageCapability::Metadata))) {
            return new StorageMutationResult(StorageMutationOutcome::Rejected);
        }
        $namespace = $request->objectIdentity()->namespace()->value();
        $logical = $request->objectIdentity()->logicalIdentity()->value();
        $lock = null;
        $handle = null;
        $temporary = null;
        $owned = null;
        $effect = false;
        $outcome = StorageMutationOutcome::Unavailable;
        $version = null;
        try {
            $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, true);
            $path = $this->layout->assertContained($this->layout->objectPath($namespace, $logical), true);
            $exists = file_exists($path);
            $prior = null;
            if (StorageOperation::Create === $operation && $exists) {
                $outcome = StorageMutationOutcome::AlreadyExists;
            } elseif (StorageOperation::Replace === $operation && !$exists) {
                $outcome = StorageMutationOutcome::Absent;
            } else {
                if ($exists) {
                    $record = LocalObjectReader::open($path, $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
                    $prior = $record['header']->version();
                    if (!fclose($record['handle'])) {
                        throw LocalStorageOperationException::cleanup('write');
                    }
                }
                $expected = $request->condition()->version();
                if (StorageOperation::Replace === $operation && (null === $expected || !hash_equals($prior, $expected->value()))) {
                    $outcome = StorageMutationOutcome::Conflict;
                } else {
                    $version = bin2hex(random_bytes(32));
                    if ($version === $prior) {
                        throw LocalStorageOperationException::failed('generation_collision', 'write');
                    }
                    $metadata = array_map(static fn(StorageMetadataEntry $entry): array => ['name' => $entry->name(), 'value' => $entry->value()], $request->metadata()->entries());
                    $header = LocalObjectHeader::create($namespace, $logical, $this->layout, $declared, $request->contentDigest()->algorithm()->value, $request->contentDigest()->digest(), $request->mediaType()->value(), $metadata, $version, $this->descriptor->artifact(), $this->descriptor->profileName());
                    $temporary = $this->layout->assertContained($this->layout->temporaryPath($namespace, $logical, $request->operationIdentity()->value()), true);
                    if (false === @lstat($temporary)) {
                        $handle = @fopen($temporary, 'x+b');
                        if (false !== $handle) {
                            $owned = fstat($handle);
                            $this->layout->assertFileHandle($temporary, $handle);
                            try {
                                $written = LocalObjectWriter::write($handle, $header, $source, $profile->limits()->maxChunkBytes());
                            } catch (Throwable $failure) {
                                $outcome = StorageMutationOutcome::Rejected;
                                throw $failure;
                            }
                            if (!$written) {
                                throw LocalStorageOperationException::failed('temporary_write_failed', 'write');
                            }
                            $this->layout->assertFileHandle($temporary, $handle);
                            $closed = fclose($handle);
                            $handle = null;
                            if (!$closed) {
                                throw LocalStorageOperationException::cleanup('write');
                            }
                            $this->assertOwnedTemporary($temporary, $owned);
                            $this->layout->assertContained($path, true);
                            if (!$exists && file_exists($path)) {
                                throw LocalStorageOperationException::failed('record_preexisting', 'write');
                            }
                            if ($exists) {
                                $effect = true;
                                if (!@unlink($path)) {
                                    throw LocalStorageOperationException::failed('record_remove_failed', 'write');
                                }
                            }
                            $this->assertOwnedTemporary($temporary, $owned);
                            $this->layout->assertContained($path, true);
                            $effect = true;
                            if (!@rename($temporary, $path)) {
                                throw LocalStorageOperationException::failed('record_publish_failed', 'write');
                            }
                            $owned = null;
                            $this->layout->assertContained($path);
                            $outcome = StorageMutationOutcome::Completed;
                        }
                    }
                }
            }
        } catch (Throwable) {
            if ($effect) {
                $outcome = StorageMutationOutcome::Uncertain;
            }
        }
        $cleanupFailed = false;
        if (null !== $handle && false !== $handle) {
            try {
                $cleanupFailed = !fclose($handle);
            } catch (Throwable) {
                $cleanupFailed = true;
            }
        }
        if (null !== $temporary && is_array($owned)) {
            try {
                $this->assertOwnedTemporary($temporary, $owned);
                if (!@unlink($temporary)) {
                    $cleanupFailed = true;
                }
            } catch (Throwable) {
                $cleanupFailed = true;
            }
        }
        if (null !== $lock && !$lock->closed()) {
            try {
                $lock->close();
            } catch (Throwable) {
                $cleanupFailed = true;
            }
        }
        if ($cleanupFailed) {
            $outcome = $effect ? StorageMutationOutcome::Uncertain : StorageMutationOutcome::Unavailable;
        }
        return StorageMutationOutcome::Completed === $outcome && null !== $version
            ? new StorageMutationResult($outcome, new StorageObjectVersion($version), $request->contentDigest())
            : new StorageMutationResult($outcome);
    }

    /** @param array{dev: int, ino: int}|false|null $owned */
    private function assertOwnedTemporary(string $path, array|false|null $owned): void
    {
        $this->layout->assertContained($path);
        $current = @lstat($path);
        if (!is_array($owned) || false === $current || $current['dev'] !== $owned['dev'] || $current['ino'] !== $owned['ino']) {
            throw LocalStorageOperationException::cleanup('write');
        }
    }

    public function delete(StorageDeleteRequest $request): StorageMutationResult
    {
        if (!$this->requestAccepted($request->contractVersion(), $request->objectIdentity()->namespace()->value())) {
            return new StorageMutationResult(StorageMutationOutcome::Rejected);
        }
        if (!$this->supports(StorageCapability::Delete) || !$this->supports(StorageCapability::ConditionalMutation)) {
            return new StorageMutationResult(StorageMutationOutcome::Unsupported);
        }
        $namespace = $request->objectIdentity()->namespace()->value();
        $logical = $request->objectIdentity()->logicalIdentity()->value();
        $lock = null;
        $effect = false;
        $expected = $request->condition()->version();
        $outcome = StorageMutationOutcome::Unavailable;
        try {
            $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, true);
            $path = $this->layout->assertContained($this->layout->objectPath($namespace, $logical), true);
            if (!file_exists($path)) {
                $outcome = StorageMutationOutcome::Absent;
            } else {
                $record = LocalObjectReader::open($path, $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
                if (!fclose($record['handle'])) {
                    throw LocalStorageOperationException::cleanup('delete');
                }
                if (null === $expected || !hash_equals($record['header']->version(), $expected->value())) {
                    $outcome = StorageMutationOutcome::Conflict;
                } else {
                    $this->layout->assertContained($path);
                    $effect = true;
                    $outcome = @unlink($path) ? StorageMutationOutcome::Completed : StorageMutationOutcome::Uncertain;
                }
            }
        } catch (Throwable) {
            $outcome = $effect ? StorageMutationOutcome::Uncertain : StorageMutationOutcome::Unavailable;
        }
        if (null !== $lock && !$lock->closed()) {
            try {
                $lock->close();
            } catch (Throwable) {
                $outcome = $effect ? StorageMutationOutcome::Uncertain : StorageMutationOutcome::Unavailable;
            }
        }
        return StorageMutationOutcome::Completed === $outcome && null !== $expected
            ? new StorageMutationResult($outcome, $expected)
            : new StorageMutationResult($outcome);
    }

    public function list(StorageListRequest $request): StorageListResult
    {
        $namespace = $request->namespace()->value();
        if (!$this->requestAccepted($request->contractVersion(), $namespace) || $request->pageSize() > $this->profile()->limits()->maxListPageSize()) {
            return new StorageListResult(StorageListOutcome::Rejected);
        }
        if (!$this->supports(StorageCapability::List)) {
            return new StorageListResult(StorageListOutcome::Unsupported);
        }
        try {
            $this->layout->ensureNamespace($namespace);
            $after = null === $request->continuationToken() ? null : LocalContinuationCodec::decode($request->continuationToken(), $this->descriptor, $namespace);
            $directory = $this->layout->assertContained($this->layout->objectDirectory($namespace), false, true);
            $entries = [];
            $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo) {
                    return new StorageListResult(StorageListOutcome::Rejected);
                }
                if (!$file->isFile() || $file->isLink() || 1 !== preg_match('/\A[a-f0-9]{64}\.record\z/D', $file->getFilename())) {
                    return new StorageListResult(StorageListOutcome::Rejected);
                }
                $logical = $this->logicalFromRecord($file->getPathname());
                if (null !== $after && strcmp($logical, $after) <= 0) {
                    continue;
                }
                $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, false);
                try {
                    $record = LocalObjectReader::open($file->getPathname(), $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
                    if (!fclose($record['handle'])) {
                        throw LocalStorageOperationException::cleanup('list');
                    }
                    $evidence = $record['header']->evidence();
                    $entries[$logical] = new StorageListEntry($evidence->identity(), $evidence->byteCount(), $evidence->objectVersion());
                } finally {
                    $lock->close();
                }
            }
            ksort($entries, SORT_STRING);
            $values = array_values($entries);
            $complete = count($values) <= $request->pageSize();
            $pageValues = array_slice($values, 0, $request->pageSize());
            $token = null;
            if (!$complete) {
                $lastEntry = end($pageValues);
                if (!$lastEntry instanceof StorageListEntry) {
                    throw LocalStorageOperationException::failed('empty_non_complete_page', 'list');
                }
                $last = $lastEntry->identity()->logicalIdentity()->value();
                $token = LocalContinuationCodec::encode($this->descriptor, $namespace, $last);
            }

            return new StorageListResult(StorageListOutcome::Completed, new StorageListPage($pageValues, $complete, $token));
        } catch (Throwable) {
            return new StorageListResult(StorageListOutcome::Rejected);
        }
    }

    private function profile(): StorageProfileDeclaration
    {
        return $this->descriptor->profile();
    }
    private function supports(StorageCapability $capability): bool
    {
        return $this->profile()->capabilities()->contains($capability);
    }
    private function requestAccepted(ContractVersion $version, string $namespace): bool
    {
        return match ($version) {
            ContractVersion::Version1 => $namespace === $this->profile()->namespace()->value(),
        };
    }

    private function logicalFromRecord(string $path): string
    {
        $this->layout->assertContained($path);
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw LocalStorageOperationException::failed('record_open_failed', 'list');
        }
        try {
            $this->layout->assertFileHandle($path, $handle);
            if (fread($handle, strlen(LocalObjectReader::MARKER)) !== LocalObjectReader::MARKER) {
                throw LocalStorageOperationException::failed('record_framing', 'list');
            }
            $line = fgets($handle, 10);
            if (false === $line || 1 !== preg_match('/\A[0-9a-f]{8}\n\z/D', $line)) {
                throw LocalStorageOperationException::failed('record_framing', 'list');
            }
            $length = intval(substr($line, 0, 8), 16);
            if ($length < 1 || $length > CanonicalJson::MAX_BYTES) {
                throw LocalStorageOperationException::failed('header_length', 'list');
            }
            $bytes = fread($handle, $length);
            if (false === $bytes) {
                throw LocalStorageOperationException::failed('record_framing', 'list');
            }

            return LocalObjectHeader::decode($bytes)->logical();
        } finally {
            if (!fclose($handle)) {
                throw LocalStorageOperationException::cleanup('list');
            }
        }
    }
}
