<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Diagnostic\AssetResolutionDiagnostic;
use Throwable;

/** Reports invalid contract artifact policy or resolution relations. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetResolutionException extends AssetResolutionException
{
    public static function invalidContract(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::InvalidContract, ['violation' => self::safe($violation)], $previous);
    }
    public static function invalidArtifact(string $violation, ?int $entryCount = null, ?int $limit = null, ?string $producerKind = null, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::InvalidArtifact, self::context($violation, $entryCount, $limit, $producerKind), $previous);
    }
    public static function invalidPolicy(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::InvalidPublicUrlPolicy, ['violation' => self::safe($violation)], $previous);
    }
    public static function integrityPolicy(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::UnsatisfiedIntegrityPolicy, ['violation' => self::safe($violation)], $previous);
    }
    public static function invalidPreload(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::InvalidPreloadRelation, ['violation' => self::safe($violation)], $previous);
    }
    public static function invalidHttp(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::InvalidHttpMetadataRelation, ['violation' => self::safe($violation)], $previous);
    }
    public static function relation(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetResolutionDiagnostic::ResolutionRelationFailure, ['violation' => self::safe($violation)], $previous);
    }

    /** @return array<string, string|int|bool> */
    private static function context(string $violation, ?int $entryCount, ?int $limit, ?string $producerKind): array
    {
        $context = ['violation' => self::safe($violation)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $producerKind) {
            $context['producer_kind'] = in_array($producerKind, ['native', 'adapter'], true) ? $producerKind : 'unsupported';
        }

        return $context;
    }

    private static function safe(string $value): string
    {
        return 1 === preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $value) ? $value : 'unsupported_violation';
    }
}
