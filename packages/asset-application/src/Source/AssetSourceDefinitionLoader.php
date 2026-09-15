<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Exception\ExceptionInterface;
use Kongono\AssetApplication\Exception\InvalidAssetApplicationException;
use Kongono\AssetApplication\Internal\AssetApplicationPathGuard;
use Kongono\AssetApplication\Internal\StrictJsonDecoder;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Throwable;

/** Parses one closed Version 1 source document and reads only its named files. */
#[Api(ApiStatus::Experimental)]
final class AssetSourceDefinitionLoader
{
    public function load(string $applicationRoot, string $definitionBytes): AssetSourceSet
    {
        try {
            $model = StrictJsonDecoder::decodeObject($definitionBytes);
            self::exactKeys($model, ['schema', 'version', 'contract_version', 'declarations', 'dependencies', 'entry_points']);
            if ('kongono.asset-application-source' !== $model['schema'] || 1 !== $model['version'] || 1 !== $model['contract_version']) {
                throw InvalidAssetApplicationException::invalid('invalid_schema');
            }
            $declarationRows = self::list($model['declarations'], 'invalid_declaration');
            if (count($declarationRows) > 1024) {
                throw InvalidAssetApplicationException::invalid('invalid_declaration');
            }
            $declarations = [];
            $sources = [];
            foreach ($declarationRows as $row) {
                $row = self::object($row, 'invalid_declaration');
                self::exactKeys($row, ['identity', 'kind', 'content_type', 'source_path']);
                $identity = new LogicalAssetIdentity(self::string($row['identity'], 'invalid_declaration'));
                $kind = AssetKind::from(self::string($row['kind'], 'invalid_declaration'));
                $contentType = AssetContentType::from(self::string($row['content_type'], 'invalid_declaration'));
                $sourcePath = self::string($row['source_path'], 'invalid_declaration');
                $read = AssetApplicationPathGuard::readRelativeSource($applicationRoot, $sourcePath);
                $declarations[] = new AssetDeclaration(ContractVersion::Version1, $identity, $kind, $contentType);
                $sources[] = new AssetSource($identity, new AssetSourcePath($sourcePath), $read['bytes']);
            }
            $dependencies = [];
            foreach (self::list($model['dependencies'], 'invalid_dependency') as $row) {
                $row = self::object($row, 'invalid_dependency');
                self::exactKeys($row, ['source', 'target', 'kind']);
                $dependencies[] = new AssetDependency(
                    ContractVersion::Version1,
                    new LogicalAssetIdentity(self::string($row['source'], 'invalid_dependency')),
                    new LogicalAssetIdentity(self::string($row['target'], 'invalid_dependency')),
                    AssetDependencyKind::from(self::string($row['kind'], 'invalid_dependency')),
                );
            }
            $entryPoints = [];
            foreach (self::list($model['entry_points'], 'invalid_entry_point') as $entry) {
                $entryPoints[] = new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity(self::string($entry, 'invalid_entry_point')));
            }
            $set = new AssetDeclarationSet(ContractVersion::Version1, $declarations, $dependencies, $entryPoints);

            return new AssetSourceSet($set, $sources);
        } catch (Throwable $failure) {
            if (($failure instanceof \Error && !$failure instanceof \ValueError) || $failure instanceof ExceptionInterface) {
                throw $failure;
            }
            throw InvalidAssetApplicationException::invalid('invalid_json', $failure);
        }
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string>         $keys
     */
    private static function exactKeys(array $value, array $keys): void
    {
        $actual = array_keys($value);
        sort($actual, SORT_STRING);
        sort($keys, SORT_STRING);
        if ($actual !== $keys) {
            throw InvalidAssetApplicationException::invalid('invalid_schema');
        }
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $violation): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidAssetApplicationException::invalid($violation);
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw InvalidAssetApplicationException::invalid($violation);
            }
            $object[$key] = $item;
        }

        return $object;
    }

    /** @return list<mixed> */
    private static function list(mixed $value, string $violation): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidAssetApplicationException::invalid($violation);
        }

        return $value;
    }

    private static function string(mixed $value, string $violation): string
    {
        if (!is_string($value)) {
            throw InvalidAssetApplicationException::invalid($violation);
        }

        return $value;
    }
}
