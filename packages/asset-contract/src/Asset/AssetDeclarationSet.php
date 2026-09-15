<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Asset;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetContract\Internal\ContractGuard;

/** Holds one detached, bounded, ordinally canonical declaration set. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetDeclarationSet
{
    /** @var list<AssetDeclaration> */
    private array $declarations;

    /** @var list<AssetDependency> */
    private array $dependencies;

    /** @var list<AssetEntryPoint> */
    private array $entryPoints;

    /**
     * @param array<array-key, mixed> $declarations
     * @param array<array-key, mixed> $dependencies
     * @param array<array-key, mixed> $entryPoints
     */
    public function __construct(
        private ContractVersion $contractVersion,
        array $declarations,
        array $dependencies,
        array $entryPoints,
    ) {
        $this->declarations = self::validateDeclarations($contractVersion, $declarations);
        $identities = array_fill_keys(array_map(static fn(AssetDeclaration $declaration): string => $declaration->identity()->value(), $this->declarations), true);
        $this->dependencies = self::validateDependencies($contractVersion, $dependencies, $identities);
        $this->entryPoints = self::validateEntryPoints($contractVersion, $entryPoints, $identities);
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    /** @return list<AssetDeclaration> */
    public function declarations(): array
    {
        return [...$this->declarations];
    }

    /** @return list<AssetDependency> */
    public function dependencies(): array
    {
        return [...$this->dependencies];
    }

    /** @return list<AssetEntryPoint> */
    public function entryPoints(): array
    {
        return [...$this->entryPoints];
    }

    public function declarationCount(): int
    {
        return count($this->declarations);
    }

    public function dependencyCount(): int
    {
        return count($this->dependencies);
    }

    public function entryPointCount(): int
    {
        return count($this->entryPoints);
    }

    public function declaration(LogicalAssetIdentity $identity): ?AssetDeclaration
    {
        foreach ($this->declarations as $declaration) {
            if ($declaration->identity()->equals($identity)) {
                return $declaration;
            }
        }

        return null;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
            && self::equalList($this->declarations, $other->declarations)
            && self::equalList($this->dependencies, $other->dependencies)
            && self::equalList($this->entryPoints, $other->entryPoints);
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<AssetDeclaration>
     */
    private static function validateDeclarations(ContractVersion $version, array $values): array
    {
        if (!array_is_list($values)) {
            throw InvalidAssetContractException::invalidDeclaration('declarations_not_list', entryCount: count($values));
        }
        if (count($values) > 1024) {
            throw InvalidAssetContractException::contractBoundExceeded('declaration_count', 1024, count($values));
        }
        $byIdentity = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof AssetDeclaration) {
                throw InvalidAssetContractException::invalidDeclaration('invalid_declaration_entry', count($values), $position);
            }
            if (!ContractGuard::sameContractVersion($value->contractVersion(), $version)) {
                throw InvalidAssetContractException::invalidDeclaration('declaration_contract_version_relation', count($values), $position);
            }
            $identity = $value->identity()->value();
            if (isset($byIdentity[$identity])) {
                throw InvalidAssetContractException::invalidDeclaration('duplicate_declaration', count($values), $position);
            }
            $byIdentity[$identity] = $value;
        }
        ksort($byIdentity, SORT_STRING);

        return array_values($byIdentity);
    }

    /**
     * @param array<array-key, mixed> $values
     * @param array<string, bool> $identities
     * @return list<AssetDependency>
     */
    private static function validateDependencies(ContractVersion $version, array $values, array $identities): array
    {
        if (!array_is_list($values)) {
            throw InvalidAssetContractException::invalidDependency('dependencies_not_list', entryCount: count($values));
        }
        if (count($values) > 4096) {
            throw InvalidAssetContractException::contractBoundExceeded('dependency_count', 4096, count($values));
        }
        $byKey = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof AssetDependency) {
                throw InvalidAssetContractException::invalidDependency('invalid_dependency_entry', count($values), $position);
            }
            if (!ContractGuard::sameContractVersion($value->contractVersion(), $version)) {
                throw InvalidAssetContractException::invalidDependency('dependency_contract_version_relation', count($values), $position, dependencyKind: $value->kind()->value);
            }
            if (!isset($identities[$value->source()->value()])) {
                throw InvalidAssetContractException::invalidDependency('missing_dependency_source', count($values), $position, dependencyKind: $value->kind()->value);
            }
            if (!isset($identities[$value->target()->value()])) {
                throw InvalidAssetContractException::invalidDependency('missing_dependency_target', count($values), $position, dependencyKind: $value->kind()->value);
            }
            $key = $value->orderingKey();
            if (isset($byKey[$key])) {
                throw InvalidAssetContractException::invalidDependency('duplicate_dependency', count($values), $position, dependencyKind: $value->kind()->value);
            }
            $byKey[$key] = $value;
        }
        ksort($byKey, SORT_STRING);

        return array_values($byKey);
    }

    /**
     * @param array<array-key, mixed> $values
     * @param array<string, bool> $identities
     * @return list<AssetEntryPoint>
     */
    private static function validateEntryPoints(ContractVersion $version, array $values, array $identities): array
    {
        if (!array_is_list($values)) {
            throw InvalidAssetContractException::invalidEntryPoint('entry_points_not_list', entryCount: count($values));
        }
        if (count($values) > 256) {
            throw InvalidAssetContractException::contractBoundExceeded('entry_point_count', 256, count($values));
        }
        $byIdentity = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof AssetEntryPoint) {
                throw InvalidAssetContractException::invalidEntryPoint('invalid_entry_point_entry', count($values), $position);
            }
            if (!ContractGuard::sameContractVersion($value->contractVersion(), $version)) {
                throw InvalidAssetContractException::invalidEntryPoint('entry_point_contract_version_relation', count($values), $position);
            }
            $identity = $value->identity()->value();
            if (!isset($identities[$identity])) {
                throw InvalidAssetContractException::invalidEntryPoint('missing_entry_point_declaration', count($values), $position);
            }
            if (isset($byIdentity[$identity])) {
                throw InvalidAssetContractException::invalidEntryPoint('duplicate_entry_point', count($values), $position);
            }
            $byIdentity[$identity] = $value;
        }
        ksort($byIdentity, SORT_STRING);

        return array_values($byIdentity);
    }

    /**
     * @param list<AssetDeclaration|AssetDependency|AssetEntryPoint> $left
     * @param list<AssetDeclaration|AssetDependency|AssetEntryPoint> $right
     */
    private static function equalList(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $position => $value) {
            $other = $right[$position];
            if ($value instanceof AssetDeclaration && (!$other instanceof AssetDeclaration || !$value->equals($other))) {
                return false;
            }
            if ($value instanceof AssetDependency && (!$other instanceof AssetDependency || !$value->equals($other))) {
                return false;
            }
            if ($value instanceof AssetEntryPoint && (!$other instanceof AssetEntryPoint || !$value->equals($other))) {
                return false;
            }
        }

        return true;
    }
}
