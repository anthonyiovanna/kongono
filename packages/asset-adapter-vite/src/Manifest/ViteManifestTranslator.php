<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Manifest;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Output\AdaptedAssetOutput;
use Kongono\AssetAdapterContract\Output\AdapterEntryBinding;
use Kongono\AssetAdapterContract\Output\AdapterOutputDependency;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetAdapterVite\Exception\InvalidViteManifestException;
use Kongono\AssetBuild\Build\AssetOutputPath;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\Identity\ContentIdentity;
use Throwable;

/** Deterministically translates closed Vite evidence into neutral opaque outputs. */
#[Api(ApiStatus::Experimental)]
final class ViteManifestTranslator
{
    /** @param array<string, string> $bytesByFile */
    public function translate(AssetSourceSet $sourceSet, AdapterProfileIdentity $profile, ViteManifest $manifest, array $bytesByFile, int $depthLimit = 256): AdapterOutputSet
    {
        try {
            $sources = [];
            $entrySources = [];
            foreach ($sourceSet->sources() as $source) {
                $sources[$source->path()->value()] = $source;
            }
            foreach ($sourceSet->declarationSet()->entryPoints() as $entry) {
                $source = $sourceSet->source($entry->identity());
                if (null !== $source) {
                    $entrySources[$source->path()->value()] = $entry->identity();
                }
            }

            $entriesByKey = [];
            $mainByFile = [];
            foreach ($manifest->entries() as $entry) {
                $entriesByKey[$entry->key()] = $entry;
                if (isset($mainByFile[$entry->file()])) {
                    throw InvalidViteManifestException::invalid('unresolved_reference');
                }
                $mainByFile[$entry->file()] = $entry;
                if (null !== $entry->source() && !isset($sources[$entry->source()])) {
                    throw InvalidViteManifestException::invalid('source_relation');
                }
                if ($entry->isDynamicEntry() && (null === $entry->source() || isset($entrySources[$entry->source()]))) {
                    throw InvalidViteManifestException::invalid('entry_relation');
                }
            }

            $ids = [];
            foreach ($manifest->entries() as $entry) {
                $ids[$entry->file()] = AdapterOutputIdentity::fromProducerKey($profile, 'key:' . $entry->key());
            }
            foreach ($manifest->emittedFiles() as $file) {
                $ids[$file] ??= AdapterOutputIdentity::fromProducerKey($profile, 'file:' . $file);
            }

            $outputs = [];
            foreach ($manifest->emittedFiles() as $file) {
                $bytes = $bytesByFile[$file] ?? null;
                if (!is_string($bytes)) {
                    throw InvalidViteManifestException::invalid('output_graph');
                }
                $source = null;
                if (isset($mainByFile[$file]) && null !== $mainByFile[$file]->source()) {
                    $source = $sources[$mainByFile[$file]->source()] ?? null;
                }
                [$kind, $type] = $this->type($file, $source, $sourceSet);
                $content = ContentIdentity::fromBytes($bytes);
                $outputs[] = new AdaptedAssetOutput($ids[$file], $kind, $type, $content, strlen($bytes), new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)), PreloadHint::None, AssetOutputPath::fromContentIdentity($content), $bytes);
            }

            $bindings = [];
            $claimed = [];
            $dependencies = [];
            foreach ($manifest->entries() as $entry) {
                if ($entry->isEntry()) {
                    if (null === $entry->source() || !isset($entrySources[$entry->source()]) || isset($claimed[$entry->source()])) {
                        throw InvalidViteManifestException::invalid('entry_relation');
                    }
                    $claimed[$entry->source()] = true;
                    $bindings[] = new AdapterEntryBinding($entrySources[$entry->source()], $ids[$entry->file()]);
                }
                foreach ($entry->imports() as $key) {
                    $target = $entriesByKey[$key] ?? null;
                    if (null === $target) {
                        throw InvalidViteManifestException::invalid('unresolved_reference');
                    } $dependencies[] = new AdapterOutputDependency($ids[$entry->file()], $ids[$target->file()], AssetDependencyKind::Static);
                }
                foreach ($entry->dynamicImports() as $key) {
                    $target = $entriesByKey[$key] ?? null;
                    if (null === $target) {
                        throw InvalidViteManifestException::invalid('unresolved_reference');
                    } $dependencies[] = new AdapterOutputDependency($ids[$entry->file()], $ids[$target->file()], AssetDependencyKind::Dynamic);
                }
                foreach ([...$entry->css(), ...$entry->assets()] as $file) {
                    if (!isset($ids[$file])) {
                        throw InvalidViteManifestException::invalid('unresolved_reference');
                    } $dependencies[] = new AdapterOutputDependency($ids[$entry->file()], $ids[$file], AssetDependencyKind::Resource);
                }
            }

            if (count($claimed) !== count($entrySources) || [] !== array_diff_key($bytesByFile, $ids) || [] !== array_diff_key($ids, $bytesByFile)) {
                throw InvalidViteManifestException::invalid('entry_relation');
            }
            return new AdapterOutputSet($sourceSet, $outputs, $bindings, $dependencies, $depthLimit);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidViteManifestException) {
                throw $failure;
            }
            throw InvalidViteManifestException::invalid('output_graph', previous: $failure);
        }
    }

    /** @return array{AssetKind, AssetContentType} */
    private function type(string $file, ?AssetSource $source, AssetSourceSet $set): array
    {
        $rawSuffix = pathinfo($file, PATHINFO_EXTENSION);
        $suffix = strtolower($rawSuffix);
        if ($rawSuffix !== $suffix) {
            throw InvalidViteManifestException::invalid('output_type');
        }
        if ('js' === $suffix) {
            return [AssetKind::Script, AssetContentType::TextJavascript];
        }
        if ('css' === $suffix) {
            return [AssetKind::Style, AssetContentType::TextCss];
        }
        if (null === $source) {
            throw InvalidViteManifestException::invalid('output_type');
        }
        $declaration = $set->declarationSet()->declaration($source->identity());
        if (null === $declaration || !$this->compatible($suffix, $declaration->contentType())) {
            throw InvalidViteManifestException::invalid('output_type');
        }
        return [$declaration->kind(), $declaration->contentType()];
    }

    private function compatible(string $suffix, AssetContentType $type): bool
    {
        return match ($type) {
            AssetContentType::TextJavascript => 'js' === $suffix, AssetContentType::TextCss => 'css' === $suffix, AssetContentType::TextPlain => 'txt' === $suffix, AssetContentType::ApplicationJson => 'json' === $suffix, AssetContentType::ApplicationWasm => 'wasm' === $suffix, AssetContentType::ApplicationOctetStream => 'bin' === $suffix, AssetContentType::ImageAvif => 'avif' === $suffix, AssetContentType::ImageGif => 'gif' === $suffix, AssetContentType::ImageJpeg => in_array($suffix, ['jpg', 'jpeg'], true), AssetContentType::ImagePng => 'png' === $suffix, AssetContentType::ImageSvgXml => 'svg' === $suffix, AssetContentType::ImageWebp => 'webp' === $suffix, AssetContentType::FontWoff2 => 'woff2' === $suffix, AssetContentType::AudioMpeg => 'mp3' === $suffix, AssetContentType::AudioOgg => 'ogg' === $suffix, AssetContentType::VideoMp4 => 'mp4' === $suffix, AssetContentType::VideoWebm => 'webm' === $suffix,
        };
    }
}
