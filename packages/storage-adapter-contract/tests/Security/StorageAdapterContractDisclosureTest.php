<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Security;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use PHPUnit\Framework\TestCase;
use Throwable;

final class StorageAdapterContractDisclosureTest extends TestCase
{
    public function testInvalidValueIsNotRendered(): void
    {
        try {
            new StorageAdapterIdentity('SECRET/ROOT', 0);
            self::fail('Expected failure');
        } catch (Throwable $e) {
            self::assertStringNotContainsString('SECRET/ROOT', (string) $e);
        }
    }
}
