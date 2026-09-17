<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Security;

use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Exception\InvalidMemoryCacheAdapterException;
use PHPUnit\Framework\TestCase;

final class MemoryCacheAdapterDisclosureTest extends TestCase
{
    public function testFailurePresentationDoesNotExposeInjectedMarkers(): void
    {
        $marker = 'raw-cache-key-value-version-marker';
        try {
            new MemoryAdapterCapacity(0, 1, 1);
            self::fail('Expected invalid capacity.');
        } catch (InvalidMemoryCacheAdapterException $exception) {
            self::assertStringNotContainsString($marker, $exception->getMessage());
            self::assertStringNotContainsString($marker, (string) $exception);
            self::assertStringNotContainsString($marker, var_export($exception->diagnosticContext(), true));
        }
    }
}
