<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuarantee;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterListTest extends TestCase
{
    public function testListingGuaranteeIsExplicit(): void
    {
        self::assertSame('ordinal_bounded_listing', StorageAdapterGuarantee::OrdinalBoundedListing->value);
    }
}
