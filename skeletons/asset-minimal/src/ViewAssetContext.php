<?php

declare(strict_types=1);

namespace AssetMinimalApp;

use Kongono\AssetApplication\Summary\ResolvedAssetSummary;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;

/** Demonstrates an explicit application-owned mapping into Phase 18 values. */
final class ViewAssetContext
{
    /** @return array{ViewContextSchema,ViewContext} */
    public static function fromResolved(ResolvedAssetSummary $asset): array
    {
        return [
            new ViewContextSchema(PresentationValueSchema::record([
                new PresentationFieldSchema('stylesheet_url', PresentationValueSchema::string(2048), true),
                new PresentationFieldSchema('stylesheet_integrity', PresentationValueSchema::string(256), true),
            ])),
            new ViewContext([
                'stylesheet_url' => new StringValue($asset->publicUrl()),
                'stylesheet_integrity' => new StringValue($asset->integrity() ?? ''),
            ]),
        ];
    }
}
