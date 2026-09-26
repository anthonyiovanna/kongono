<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Metadata\StorageMetadataEntry;
use Kongono\StorageContract\Result\StorageObjectEvidence;
use Kongono\StorageContract\Version\StorageObjectVersion;

/** Carries one validated canonical local-record header. */
#[Api(ApiStatus::Internal)]
final readonly class LocalObjectHeader
{
    /** @param list<array{name: string, value: string}> $metadata */
    public function __construct(
        private string $namespace,
        private string $logical,
        private string $namespaceHash,
        private string $objectHash,
        private int $byteCount,
        private string $digestAlgorithm,
        private string $digest,
        private ?string $mediaType,
        private array $metadata,
        private string $version,
        private string $sourceIdentity,
        private string $buildIdentity,
        private string $artifactIdentity,
        private string $profile,
    ) {}

    /** @param list<array{name: string, value: string}> $metadata */
    public static function create(string $namespace, string $logical, LocalPathLayout $layout, int $byteCount, string $algorithm, string $digest, ?string $mediaType, array $metadata, string $version, StorageProfileArtifact $artifact, StorageProfileName $profile): self
    {
        return new self($namespace, $logical, $layout->namespaceHash($namespace), $layout->objectHash($namespace, $logical), $byteCount, $algorithm, $digest, $mediaType, $metadata, $version, $artifact->sourceIdentity()->value(), $artifact->buildIdentity()->value(), $artifact->artifactIdentity()->value(), $profile->value());
    }

    public static function decode(string $bytes): self
    {
        try {
            $row = CanonicalJson::decode($bytes);
        } catch (JsonException $error) {
            throw LocalStorageOperationException::failed('invalid_header_json', 'read', $error);
        }
        $keys = ['schema','namespace','logical_identity','namespace_path_identity','object_path_identity','byte_count','digest_algorithm','digest','media_type','metadata','object_version','source_identity','build_identity','artifact_identity','profile'];
        if (array_keys($row) !== $keys || 'kongono/storage-local-record/1' !== ($row['schema'] ?? null) || !is_string($row['namespace']) || !is_string($row['logical_identity']) || !is_string($row['namespace_path_identity']) || !is_string($row['object_path_identity']) || !is_int($row['byte_count']) || !is_string($row['digest_algorithm']) || !is_string($row['digest']) || !(is_string($row['media_type']) || null === $row['media_type']) || !is_array($row['metadata']) || !is_string($row['object_version']) || !is_string($row['source_identity']) || !is_string($row['build_identity']) || !is_string($row['artifact_identity']) || !is_string($row['profile'])) {
            throw LocalStorageOperationException::failed('invalid_header_shape', 'read');
        }
        $metadata = [];
        foreach ($row['metadata'] as $entry) {
            if (!is_array($entry) || array_keys($entry) !== ['name','value'] || !is_string($entry['name']) || !is_string($entry['value'])) {
                throw LocalStorageOperationException::failed('invalid_header_metadata', 'read');
            }
            $metadata[] = ['name' => $entry['name'], 'value' => $entry['value']];
        }

        return new self($row['namespace'], $row['logical_identity'], $row['namespace_path_identity'], $row['object_path_identity'], $row['byte_count'], $row['digest_algorithm'], $row['digest'], $row['media_type'], $metadata, $row['object_version'], $row['source_identity'], $row['build_identity'], $row['artifact_identity'], $row['profile']);
    }

    public function encode(): string
    {
        return CanonicalJson::encode(['schema' => 'kongono/storage-local-record/1','namespace' => $this->namespace,'logical_identity' => $this->logical,'namespace_path_identity' => $this->namespaceHash,'object_path_identity' => $this->objectHash,'byte_count' => $this->byteCount,'digest_algorithm' => $this->digestAlgorithm,'digest' => $this->digest,'media_type' => $this->mediaType,'metadata' => $this->metadata,'object_version' => $this->version,'source_identity' => $this->sourceIdentity,'build_identity' => $this->buildIdentity,'artifact_identity' => $this->artifactIdentity,'profile' => $this->profile]);
    }

    public function validate(string $namespace, string $logical, LocalPathLayout $layout, StorageProfileArtifact $artifact, StorageProfileName $profile): void
    {
        if ($this->namespace !== $namespace || $this->logical !== $logical || $this->namespaceHash !== $layout->namespaceHash($namespace) || $this->objectHash !== $layout->objectHash($namespace, $logical) || $this->sourceIdentity !== $artifact->sourceIdentity()->value() || $this->buildIdentity !== $artifact->buildIdentity()->value() || $this->artifactIdentity !== $artifact->artifactIdentity()->value() || $this->profile !== $profile->value()) {
            throw LocalStorageOperationException::failed('mixed_record_evidence', 'read');
        }
    }

    public function evidence(): StorageObjectEvidence
    {
        $entries = array_map(static fn(array $entry): StorageMetadataEntry => new StorageMetadataEntry($entry['name'], $entry['value']), $this->metadata);

        return new StorageObjectEvidence(new StorageObjectIdentity(new StorageNamespace($this->namespace), new LogicalObjectIdentity($this->logical)), new StorageByteCount($this->byteCount), new StorageContentDigest(StorageDigestAlgorithm::from($this->digestAlgorithm), $this->digest), null === $this->mediaType ? null : new StorageMediaType($this->mediaType), new StorageMetadata($entries), new StorageObjectVersion($this->version));
    }

    public function byteCount(): int
    {
        return $this->byteCount;
    }
    public function digestAlgorithm(): string
    {
        return $this->digestAlgorithm;
    }
    public function digest(): string
    {
        return $this->digest;
    }
    public function version(): string
    {
        return $this->version;
    }
    public function logical(): string
    {
        return $this->logical;
    }
}
