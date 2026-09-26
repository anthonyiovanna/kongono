<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Security;

use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageRuntimeDisclosureTest extends TestCase
{
    public function testPriorProviderMaterialIsNotPresented(): void
    {
        $markers = ['object-secret','namespace-secret','C:\\private\\root','digest-secret','token-secret'];
        foreach ($markers as $marker) {
            $failure = InvalidStorageRuntimeException::evidence('object_identity_mismatch', 'read', 'completed', new RuntimeException($marker));
            self::assertStringNotContainsString($marker, $failure->getMessage());
            self::assertStringNotContainsString($marker, (string) $failure);
            self::assertStringNotContainsString($marker, json_encode($failure->diagnosticContext(), JSON_THROW_ON_ERROR));
        }
    }
}
