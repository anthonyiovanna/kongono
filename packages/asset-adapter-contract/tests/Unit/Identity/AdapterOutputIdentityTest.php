<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Identity;

use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use PHPUnit\Framework\TestCase;

final class AdapterOutputIdentityTest extends TestCase
{
    public function testOpaqueOutputIdentityIsDeterministicAndTypeDistinct(): void
    {
        $profile = new AdapterProfileIdentity('vite/client.1');
        $identity = AdapterOutputIdentity::fromProducerKey($profile, 'private-key');
        self::assertTrue($identity->equals(AdapterOutputIdentity::fromProducerKey($profile, 'private-key')));
        self::assertSame(AdapterOutputIdentity::class, $identity::class);
    }
}
