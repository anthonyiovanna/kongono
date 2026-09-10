<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Internal;

use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoRecord;
use PHPUnit\Framework\TestCase;

final class AddressInfoRecordTest extends TestCase
{
    public function testExplainedValuesAreDetached(): void
    {
        $values = ['ai_family' => AF_INET, 'ai_addr' => ['sin_addr' => '127.0.0.1', 'sin_port' => 443]];
        $record = new AddressInfoRecord($values);
        $copy = $record->values();
        $copy['ai_family'] = AF_INET6;
        self::assertSame($values, $record->values());
    }
}
