<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Identity;

use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use PHPUnit\Framework\TestCase;

final class AdapterProfileIdentityTest extends TestCase
{
    public function testGrammarAndEquality(): void
    {
        self::assertTrue((new AdapterProfileIdentity('vite/client.1'))->equals(new AdapterProfileIdentity('vite/client.1')));
        $this->expectException(InvalidAssetAdapterContractException::class);
        new AdapterProfileIdentity('../vite');
    }
}
