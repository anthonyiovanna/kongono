<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Diagnostic\AssetContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct or validate asset-contract evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetContractException extends AssetContractException
{
    private const array IDENTITY_VIOLATIONS = ['logical_asset_identity_grammar', 'content_identity_grammar'];
    private const array DECLARATION_VIOLATIONS = ['kind_content_type_relation', 'declarations_not_list', 'invalid_declaration_entry', 'declaration_contract_version_relation', 'duplicate_declaration'];
    private const array DEPENDENCY_VIOLATIONS = ['self_dependency', 'dependencies_not_list', 'invalid_dependency_entry', 'dependency_contract_version_relation', 'duplicate_dependency', 'missing_dependency_source', 'missing_dependency_target'];
    private const array ENTRY_POINT_VIOLATIONS = ['entry_points_not_list', 'invalid_entry_point_entry', 'entry_point_contract_version_relation', 'duplicate_entry_point', 'missing_entry_point_declaration'];
    private const array DELIVERY_VIOLATIONS = ['kind_content_type_relation', 'integrity_digest_grammar', 'byte_count_range', 'integrity_content_identity_relation', 'module_preload_kind_relation'];
    private const array BOUND_VIOLATIONS = ['content_bytes', 'declaration_count', 'dependency_count', 'entry_point_count'];
    private const array ASSET_KINDS = ['script', 'style', 'image', 'font', 'media', 'web_assembly', 'data'];
    private const array DEPENDENCY_KINDS = ['static', 'dynamic', 'resource'];
    private const array ALGORITHMS = ['sha256', 'sha384', 'sha512'];
    private const array PRELOAD_HINTS = ['none', 'preload', 'module_preload'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetContractDiagnostic::InvalidIdentity, ['violation' => self::safe($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidDeclaration(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?string $assetKind = null, ?Throwable $previous = null): self
    {
        return new self(AssetContractDiagnostic::InvalidDeclaration, self::context($violation, self::DECLARATION_VIOLATIONS, $entryCount, $entryPosition, assetKind: $assetKind), $previous);
    }

    public static function invalidDependency(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?string $dependencyKind = null, ?Throwable $previous = null): self
    {
        return new self(AssetContractDiagnostic::InvalidDependency, self::context($violation, self::DEPENDENCY_VIOLATIONS, $entryCount, $entryPosition, dependencyKind: $dependencyKind), $previous);
    }

    public static function invalidEntryPoint(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        return new self(AssetContractDiagnostic::InvalidEntryPoint, self::context($violation, self::ENTRY_POINT_VIOLATIONS, $entryCount, $entryPosition), $previous);
    }

    public static function invalidDeliveryMetadata(string $violation, ?string $assetKind = null, ?string $algorithm = null, ?string $preloadHint = null, ?Throwable $previous = null): self
    {
        return new self(AssetContractDiagnostic::InvalidDeliveryMetadata, self::context($violation, self::DELIVERY_VIOLATIONS, assetKind: $assetKind, algorithm: $algorithm, preloadHint: $preloadHint), $previous);
    }

    public static function contractBoundExceeded(string $violation, int $limit, ?int $entryCount = null, ?Throwable $previous = null): self
    {
        return new self(AssetContractDiagnostic::ContractBoundExceeded, self::context($violation, self::BOUND_VIOLATIONS, $entryCount, limit: max(0, $limit)), $previous);
    }

    /**
     * @param list<string> $allowed
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, array $allowed, ?int $entryCount = null, ?int $entryPosition = null, ?int $limit = null, ?string $assetKind = null, ?string $dependencyKind = null, ?string $algorithm = null, ?string $preloadHint = null): array
    {
        $context = ['violation' => self::safe($violation, $allowed)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $assetKind) {
            $context['asset_kind'] = self::safeToken($assetKind, self::ASSET_KINDS, 'unsupported_asset_kind');
        }
        if (null !== $dependencyKind) {
            $context['dependency_kind'] = self::safeToken($dependencyKind, self::DEPENDENCY_KINDS, 'unsupported_dependency_kind');
        }
        if (null !== $algorithm) {
            $context['algorithm'] = self::safeToken($algorithm, self::ALGORITHMS, 'unsupported_algorithm');
        }
        if (null !== $preloadHint) {
            $context['preload_hint'] = self::safeToken($preloadHint, self::PRELOAD_HINTS, 'unsupported_preload_hint');
        }

        return $context;
    }

    /** @param list<string> $allowed */
    private static function safe(string $value, array $allowed): string
    {
        return self::safeToken($value, $allowed, 'unsupported_violation');
    }

    /** @param list<string> $allowed */
    private static function safeToken(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
