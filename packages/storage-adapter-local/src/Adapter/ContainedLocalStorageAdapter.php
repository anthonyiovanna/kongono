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
        try {
            $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, false);
            $path = $this->layout->objectPath($namespace, $logical);
            if (!file_exists($path)) {
                $lock->close();

                return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent));
            }
            $record = LocalObjectReader::open($path, $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
            if ($record['header']->byteCount() > $request->maximumByteCount()->value() || $record['header']->byteCount() > $profile->limits()->maxObjectBytes()) {
                fclose($record['handle']);
                $lock->close();

                return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Rejected));
            }
            $result = new StorageReadResult(StorageReadOutcome::Completed, $record['header']->evidence());
            $source = new LocalReadSource($record['handle'], $lock, $record['header']->byteCount(), $profile->limits()->maxChunkBytes());

            return new StorageAdapterRead($request, $result, $source);
        } catch (Throwable) {
            if (null !== $lock && !$lock->closed()) {
                try {
                    $lock->close();
                } catch (Throwable) {
                }
            }

            return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Unavailable));
        }
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
        $temporary = null;
        try {
            $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, true);
            $path = $this->layout->assertContained($this->layout->objectPath($namespace, $logical), true);
            $exists = file_exists($path);
            $prior = 'creation';
            if (StorageOperation::Create === $operation && $exists) {
                $lock->close();

                return new StorageMutationResult(StorageMutationOutcome::AlreadyExists);
            }
            if (StorageOperation::Replace === $operation) {
                if (!$exists) {
                    $lock->close();
                    return new StorageMutationResult(StorageMutationOutcome::Absent);
                }
                $record = LocalObjectReader::open($path, $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
                fclose($record['handle']);
                $prior = $record['header']->version();
                $expected = $request->condition()->version();
                if (null === $expected || !hash_equals($prior, $expected->value())) {
                    $lock->close();
                    return new StorageMutationResult(StorageMutationOutcome::Conflict);
                }
            }
            $metadata = array_map(static fn(StorageMetadataEntry $entry): array => ['name' => $entry->name(),'value' => $entry->value()], $request->metadata()->entries());
            $version = hash('sha256', "kongono.storage.local.object-version.v1\0" . $prior . "\0" . $namespace . "\0" . $logical . "\0" . $request->contentDigest()->digest() . "\0" . json_encode($metadata, JSON_THROW_ON_ERROR));
            $header = LocalObjectHeader::create($namespace, $logical, $this->layout, $declared, $request->contentDigest()->algorithm()->value, $request->contentDigest()->digest(), $request->mediaType()->value(), $metadata, $version, $this->descriptor->artifact(), $this->descriptor->profileName());
            $temporary = $this->layout->assertContained($this->layout->temporaryPath($namespace, $logical, $request->operationIdentity()->value()), true);
            if (file_exists($temporary)) {
                return new StorageMutationResult(StorageMutationOutcome::Unavailable);
            }
            LocalObjectWriter::write($temporary, $header, $source, $profile->limits()->maxChunkBytes());
            $this->layout->assertContained($temporary);
            $this->layout->assertContained($path, true);
            if ($exists && !@unlink($path)) {
                return new StorageMutationResult(StorageMutationOutcome::Unavailable);
            }
            if (!@rename($temporary, $path)) {
                return new StorageMutationResult($exists ? StorageMutationOutcome::Uncertain : StorageMutationOutcome::Unavailable);
            }
            $temporary = null;
            $this->layout->assertContained($path);
            $lock->close();

            return new StorageMutationResult(StorageMutationOutcome::Completed, new StorageObjectVersion($version), $request->contentDigest());
        } catch (LocalStorageOperationException) {
            return new StorageMutationResult(StorageMutationOutcome::Rejected);
        } catch (Throwable) {
            return new StorageMutationResult(StorageMutationOutcome::Unavailable);
        } finally {
            if (null !== $temporary && file_exists($temporary) && is_file($temporary) && !is_link($temporary)) {
                @unlink($temporary);
            }
            if (null !== $lock && !$lock->closed()) {
                try {
                    $lock->close();
                } catch (Throwable) {
                }
            }
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
        try {
            $lock = LocalObjectLock::acquire($this->layout, $namespace, $logical, true);
            $path = $this->layout->objectPath($namespace, $logical);
            if (!file_exists($path)) {
                $lock->close();
                return new StorageMutationResult(StorageMutationOutcome::Absent);
            }
            $record = LocalObjectReader::open($path, $namespace, $logical, $this->layout, $this->descriptor->artifact(), $this->descriptor->profileName());
            fclose($record['handle']);
            $expected = $request->condition()->version();
            if (null === $expected || !hash_equals($record['header']->version(), $expected->value())) {
                $lock->close();
                return new StorageMutationResult(StorageMutationOutcome::Conflict);
            }
            $this->layout->assertContained($path);
            if (!@unlink($path)) {
                return new StorageMutationResult(StorageMutationOutcome::Uncertain);
            }
            $lock->close();

            return new StorageMutationResult(StorageMutationOutcome::Completed, $expected);
        } catch (Throwable) {
            return new StorageMutationResult(StorageMutationOutcome::Unavailable);
        } finally {
            if (null !== $lock && !$lock->closed()) {
                try {
                    $lock->close();
                } catch (Throwable) {
                }
            }
        }
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
            $directory = $this->layout->assertContained($this->layout->objectDirectory($namespace));
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
                    fclose($record['handle']);
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
        $handle = fopen($path, 'rb');
        if (false === $handle) {
            throw LocalStorageOperationException::failed('record_open_failed', 'list');
        }
        try {
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
            fclose($handle);
        }
    }
}
