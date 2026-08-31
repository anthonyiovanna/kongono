<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;

/** Centralizes exact list, type, bound, uniqueness, and binary-order admission checks. */
#[Api(ApiStatus::Internal)]
final class AdmissionGuard
{
    /**
     * @param array<array-key, mixed> $values
     * @return list<ExtensionSelection>
     */
    public static function selections(array $values): array
    {
        return self::objects($values, ExtensionSelection::class, 64, 'selection', static fn(ExtensionSelection $value): string => $value->identity()->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<ContributionConflictResolution>
     */
    public static function resolutions(array $values): array
    {
        return self::objects($values, ContributionConflictResolution::class, 8192, 'resolution', static fn(ContributionConflictResolution $value): string => $value->contributionIdentity()->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<GraphNodeRecord>
     */
    public static function nodeRecords(array $values): array
    {
        return self::objects($values, GraphNodeRecord::class, 256, 'node', static fn(GraphNodeRecord $value): string => $value->identity()->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<GraphRelationRecord>
     */
    public static function relationRecords(array $values): array
    {
        return self::objects($values, GraphRelationRecord::class, 1024, 'relation', static fn(GraphRelationRecord $value): string => $value->identity()->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<ContributionIdentity>
     */
    public static function contributionIdentities(array $values): array
    {
        return self::objects($values, ContributionIdentity::class, 128, 'contribution_identity', static fn(ContributionIdentity $value): string => $value->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<ExtensionGraphContribution>
     */
    public static function contributions(array $values): array
    {
        return self::objects($values, ExtensionGraphContribution::class, 8192, 'contribution', static fn(ExtensionGraphContribution $value): string => $value->extensionIdentity()->value() . "\0" . $value->contributionIdentity()->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<\Kongono\ExtensionContract\Declaration\ExtensionDeclaration>
     */
    public static function declarations(array $values): array
    {
        return self::objects($values, \Kongono\ExtensionContract\Declaration\ExtensionDeclaration::class, 256, 'declaration', static fn(\Kongono\ExtensionContract\Declaration\ExtensionDeclaration $value): string => $value->identity()->value());
    }

    /**
     * @template T of object
     * @param array<array-key, mixed> $values
     * @param class-string<T> $type
     * @param callable(T): string $key
     * @return list<T>
     */
    private static function objects(array $values, string $type, int $maximum, string $kind, callable $key): array
    {
        if (!array_is_list($values)) {
            throw InvalidExtensionAdmissionException::invalid($kind . '_not_list', $kind, count($values));
        }
        if (count($values) > $maximum) {
            throw InvalidExtensionAdmissionException::invalid($kind . '_count', $kind, count($values));
        }

        $accepted = [];
        $seen = [];
        $previous = null;
        foreach ($values as $position => $value) {
            if (!$value instanceof $type) {
                throw InvalidExtensionAdmissionException::invalid('invalid_' . $kind . '_entry', $kind, count($values), $position);
            }
            $current = $key($value);
            if (isset($seen[$current])) {
                throw InvalidExtensionAdmissionException::invalid('duplicate_' . $kind, $kind, count($values), $position);
            }
            if (null !== $previous && strcmp($previous, $current) >= 0) {
                throw InvalidExtensionAdmissionException::invalid('noncanonical_' . $kind . '_order', $kind, count($values), $position);
            }
            $seen[$current] = true;
            $previous = $current;
            $accepted[] = $value;
        }

        return $accepted;
    }
}
