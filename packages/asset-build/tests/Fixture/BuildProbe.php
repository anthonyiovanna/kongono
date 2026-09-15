<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Fixture;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
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

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

if (!class_exists(NativeAssetBuilder::class)) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Kongono\\AssetBuild\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $path = dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });
}

final class BuildProbe
{
    public static function sourceSet(bool $reverse = false, string $scriptBytes = "console.log('kongono');\n"): AssetSourceSet
    {
        $script = new LogicalAssetIdentity('app.script');
        $style = new LogicalAssetIdentity('app.style');
        $declarations = [
            new AssetDeclaration(ContractVersion::Version1, $script, AssetKind::Script, AssetContentType::TextJavascript),
            new AssetDeclaration(ContractVersion::Version1, $style, AssetKind::Style, AssetContentType::TextCss),
        ];
        $sources = [
            new AssetSource($script, new AssetSourcePath('assets/app.js'), $scriptBytes),
            new AssetSource($style, new AssetSourcePath('assets/app.css'), "body{}\n"),
        ];
        if ($reverse) {
            $declarations = array_reverse($declarations);
            $sources = array_reverse($sources);
        }

        return new AssetSourceSet(new AssetDeclarationSet(
            ContractVersion::Version1,
            $declarations,
            [new AssetDependency(ContractVersion::Version1, $script, $style, AssetDependencyKind::Static)],
            [new AssetEntryPoint(ContractVersion::Version1, $script)],
        ), $sources);
    }

    /** @return array{artifact_sha256:string, artifact:string, objects:array<string, string>} */
    public static function run(bool $reverse = false): array
    {
        $result = (new NativeAssetBuilder())->build(self::sourceSet($reverse));

        return ['artifact_sha256' => $result->artifact()->buildIdentity()->value(), 'artifact' => $result->artifact()->bytes(), 'objects' => $result->outputBytesByPath()];
    }

    /**
     * @param list<array{int, int, AssetDependencyKind}> $dependencies
     * @param list<int> $entryIndexes
     */
    public static function graphSourceSet(int $count, array $dependencies, array $entryIndexes, bool $reverse = false): AssetSourceSet
    {
        $identities = [];
        $declarations = [];
        $sources = [];
        for ($index = 0; $index < $count; ++$index) {
            $identity = new LogicalAssetIdentity(sprintf('asset.%04d', $index));
            $identities[] = $identity;
            $declarations[] = new AssetDeclaration(ContractVersion::Version1, $identity, AssetKind::Script, AssetContentType::TextJavascript);
            $sources[] = new AssetSource($identity, new AssetSourcePath(sprintf('assets/asset-%04d.bin', $index)), sprintf('asset-%04d', $index));
        }
        $relations = array_map(static fn(array $relation): AssetDependency => new AssetDependency(
            ContractVersion::Version1,
            $identities[$relation[0]],
            $identities[$relation[1]],
            $relation[2],
        ), $dependencies);
        $entries = array_map(static fn(int $index): AssetEntryPoint => new AssetEntryPoint(ContractVersion::Version1, $identities[$index]), $entryIndexes);
        if ($reverse) {
            $declarations = array_reverse($declarations);
            $sources = array_reverse($sources);
            $relations = array_reverse($relations);
            $entries = array_reverse($entries);
        }

        return new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, $declarations, $relations, $entries), $sources);
    }
}

if (isset($_SERVER['SCRIPT_FILENAME']) && is_string($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    echo json_encode(BuildProbe::run(in_array('--reverse', $argv ?? [], true)), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
}
