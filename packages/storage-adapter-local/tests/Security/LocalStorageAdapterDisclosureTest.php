<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Security;

use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use PHPUnit\Framework\TestCase;
use Throwable;

final class LocalStorageAdapterDisclosureTest extends TestCase
{
    public function testRootIsNotRenderedInFailure(): void
    {
        $secret = 'C:/SECRET/customer/root';
        try {
            new LocalStorageRoot($secret);
            self::fail('Expected failure');
        } catch (Throwable $e) {
            self::assertStringNotContainsString($secret, (string) $e);
        }
    }
}
