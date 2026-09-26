<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Diagnostic\StorageAdapterContractDiagnostic;
use Kongono\StorageAdapterContract\Exception\InvalidStorageAdapterContractException;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuarantee;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitation;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSource;

/** Centralizes neutral adapter-protocol relations. */
#[Api(ApiStatus::Internal)]
final class AdapterContractGuard
{
    public static function identity(string $identity): string
    {
        if (strlen($identity) < 1 || strlen($identity) > 64 || 1 !== preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $identity)) {
            throw self::invalid(StorageAdapterContractDiagnostic::InvalidIdentity, 'identity_grammar');
        }

        return $identity;
    }

    public static function revision(int $revision): int
    {
        if ($revision < 1 || $revision > 9_999) {
            throw self::invalid(StorageAdapterContractDiagnostic::InvalidIdentity, 'revision_range', limit: 9_999);
        }

        return $revision;
    }

    public static function profile(StorageProfileArtifact $artifact, StorageProfileName $name): StorageProfileDeclaration
    {
        $profile = $artifact->profiles()->profile($name);
        if (null === $profile) {
            throw self::invalid(StorageAdapterContractDiagnostic::InvalidDescriptor, 'profile_absent');
        }

        return $profile;
    }

    /** @param array<array-key, mixed> $values
     * @return list<StorageAdapterGuarantee>
     */
    public static function guarantees(array $values): array
    {
        return self::enumSet($values, StorageAdapterGuarantee::cases(), StorageAdapterGuarantee::class);
    }

    /** @param array<array-key, mixed> $values
     * @return list<StorageAdapterLimitation>
     */
    public static function limitations(array $values): array
    {
        return self::enumSet($values, StorageAdapterLimitation::cases(), StorageAdapterLimitation::class);
    }

    public static function read(StorageReadRequest $request, StorageReadResult $result, ?StorageByteSource $source): void
    {
        $completed = StorageReadOutcome::Completed === $result->outcome();
        if ($completed !== (null !== $source) || (null !== $source && $source->closed())) {
            throw self::invalid(StorageAdapterContractDiagnostic::InvalidRead, 'source_outcome_relation', outcome: $result->outcome()->value);
        }
        $evidence = $result->evidence();
        if (null !== $evidence && !$request->objectIdentity()->equals($evidence->identity())) {
            throw self::invalid(StorageAdapterContractDiagnostic::InvalidRead, 'evidence_identity_relation', outcome: $result->outcome()->value);
        }
    }

    /** @template T of \UnitEnum
     * @param array<array-key, mixed> $values
     * @param list<T> $cases
     * @param class-string<T> $type
     * @return list<T>
     */
    private static function enumSet(array $values, array $cases, string $type): array
    {
        if (!array_is_list($values)) {
            throw self::invalid(StorageAdapterContractDiagnostic::InvalidSet, 'not_list', count: count($values));
        }
        $present = [];
        foreach ($values as $value) {
            if (!$value instanceof $type || isset($present[$value->name])) {
                throw self::invalid(StorageAdapterContractDiagnostic::InvalidSet, $value instanceof $type ? 'duplicate_member' : 'invalid_member', count: count($values));
            }
            $present[$value->name] = true;
        }
        $ordered = [];
        foreach ($cases as $case) {
            if (isset($present[$case->name])) {
                $ordered[] = $case;
            }
        }

        return $ordered;
    }

    private static function invalid(StorageAdapterContractDiagnostic $diagnostic, string $violation, ?int $count = null, ?int $limit = null, ?string $outcome = null): InvalidStorageAdapterContractException
    {
        $context = ['violation' => $violation];
        if (null !== $count) {
            $context['count'] = max(0, $count);
        }
        if (null !== $limit) {
            $context['limit'] = $limit;
        }
        if (null !== $outcome) {
            $context['outcome'] = $outcome;
        }

        return InvalidStorageAdapterContractException::forDiagnostic($diagnostic, $context);
    }
}
