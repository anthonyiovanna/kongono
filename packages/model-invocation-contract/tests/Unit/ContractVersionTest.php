<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\TestCase;

final class ContractVersionTest extends TestCase
{
    public function testVersionAndAllIdentityWrappersAreExact(): void
    {
        self::assertSame([1], array_column(ContractVersion::cases(), 'value'));
        $classes = [ProviderIdentity::class, ModelIdentity::class, ProfileIdentity::class, InvocationIdentity::class, ResultIdentity::class, ActionName::class, UsageMetricName::class, UsageUnitName::class, ReceiptIdentity::class];
        foreach ($classes as $class) {
            $value = new $class('a' . str_repeat('b', 127));
            self::assertSame(128, strlen($value->value()));
            self::assertTrue($value->equals(new $class($value->value())));
            self::assertFalse($value->equals(new $class('different')));
        }
    }

    public function testRejectedIdentityNeverAppearsInFailure(): void
    {
        foreach (['RAW-SECRET', 'a' . str_repeat('b', 128)] as $raw) {
            try {
                new ProviderIdentity($raw);
                self::fail('Expected invalid identity.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertSame('KGN-MODEL-CONTRACT-1401', $exception->diagnosticId());
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
