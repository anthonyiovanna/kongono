<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Internal;

use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupFailure;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupResult;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoRecord;
use PHPUnit\Framework\TestCase;

final class AddressInfoLookupResultTest extends TestCase
{
    public function testSuccessAndFailureAreExclusiveAndDetached(): void
    {
        $record = new AddressInfoRecord(['ai_family' => AF_INET]);
        $success = AddressInfoLookupResult::succeeded([$record]);
        $copy = $success->records();
        array_shift($copy);
        self::assertSame([$record], $success->records());
        self::assertNull($success->failure());

        $failure = AddressInfoLookupResult::failed(AddressInfoLookupFailure::NoAddress);
        self::assertSame([], $failure->records());
        self::assertSame(AddressInfoLookupFailure::NoAddress, $failure->failure());
    }
}
