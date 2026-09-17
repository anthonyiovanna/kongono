<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Security;

use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use PHPUnit\Framework\TestCase;

final class CacheAdapterContractDisclosureTest extends TestCase
{
    public function testMessagesAndStringificationExcludeInjectedIdentity(): void
    {
        $marker = 'raw-key-secret-marker';
        try {
            new CacheAdapterIdentity($marker . '/', 0);
            self::fail('Expected invalid identity.');
        } catch (InvalidCacheAdapterContractException $exception) {
            self::assertStringNotContainsString($marker, $exception->getMessage());
            self::assertStringNotContainsString($marker, (string) $exception);
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($marker, (string) $field->value());
            }
        }
    }
}
