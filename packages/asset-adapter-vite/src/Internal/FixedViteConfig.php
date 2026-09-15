<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Source\AssetSourceSet;

/** Generates the exact plain-ESM configuration for the fixed Vite profile. */
#[Api(ApiStatus::Internal)]final class FixedViteConfig
{
    public static function bytes(AssetSourceSet $sourceSet, string $sourceRoot, string $outputRoot): string
    {
        $input = [];
        foreach ($sourceSet->declarationSet()->entryPoints() as $entry) {
            $source = $sourceSet->source($entry->identity());
            if (null === $source) {
                throw new \LogicException('Entry source relation was lost.');
            }$input[$entry->identity()->value()] = $sourceRoot . '/' . $source->path()->value();
        }ksort($input, SORT_STRING);
        $json = static fn(mixed $value): string => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $entries = [];
        foreach ($input as $key => $value) {
            $entries[] = $json($key) . ': ' . $json($value);
        }return "export default {\n" . "  root: " . $json($sourceRoot) . ",\n" . "  base: './',\n" . "  clearScreen: false,\n" . "  publicDir: false,\n" . "  build: {\n" . "    outDir: " . $json($outputRoot) . ",\n" . "    emptyOutDir: false,\n" . "    manifest: 'kongono-vite-manifest.json',\n" . "    target: 'es2022',\n" . "    sourcemap: false,\n" . "    minify: false,\n" . "    assetsInlineLimit: 0,\n" . "    copyPublicDir: false,\n" . "    cssCodeSplit: true,\n" . "    modulePreload: { polyfill: false },\n" . "    rollupOptions: {\n" . "      input: {" . implode(', ', $entries) . "},\n" . "      output: { entryFileNames: 'assets/[name]-[hash].js', chunkFileNames: 'assets/chunks/[name]-[hash].js', assetFileNames: 'assets/[name]-[hash][extname]' }\n" . "    },\n" . "    write: true\n" . "  }\n" . "};\n";
    }
    private function __construct() {}
}
