<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Manifest;

use Kongono\AssetAdapterVite\Exception\InvalidViteManifestException;
use Kongono\AssetAdapterVite\Manifest\ViteManifestDecoder;
use PHPUnit\Framework\TestCase;

final class ViteManifestDecoderTest extends TestCase
{
    public function testClosedManifestIsSortedAndDuplicateMembersFail(): void
    {
        $manifest = (new ViteManifestDecoder())->decode('{"b":{"file":"b.js"},"a":{"file":"a.js","imports":["b"]}}');
        self::assertSame(['a','b'], array_map(static fn($entry) => $entry->key(), $manifest->entries()));
        $this->expectException(InvalidViteManifestException::class);
        (new ViteManifestDecoder())->decode('{"a":{"file":"a.js","file":"b.js"}}');
    }
}
