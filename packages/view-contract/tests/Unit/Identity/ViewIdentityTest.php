<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Identity;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewIdentityTest extends TestCase
{
    public function testIdentitiesPreserveExactValuesAndEquality(): void
    {
        $logical = new LogicalViewIdentity('account.profile-v1');
        self::assertSame('account.profile-v1', $logical->value());
        self::assertTrue($logical->equals(new LogicalViewIdentity('account.profile-v1')));
        self::assertFalse($logical->equals(new LogicalViewIdentity('account.profile-v2')));

        $operation = new RenderOperationIdentity(str_repeat('a', 128));
        self::assertSame(128, strlen($operation->value()));
        self::assertTrue($operation->equals(new RenderOperationIdentity(str_repeat('a', 128))));

        $compiled = new CompiledViewIdentity(str_repeat('a', 64));
        self::assertTrue($compiled->equals(new CompiledViewIdentity(str_repeat('a', 64))));
    }

    /** @return iterable<string, array{class-string, string}> */
    public static function invalidIdentities(): iterable
    {
        yield 'empty logical' => [LogicalViewIdentity::class, ''];
        yield 'leading separator' => [LogicalViewIdentity::class, '.private'];
        yield 'uppercase' => [LogicalViewIdentity::class, 'Private'];
        yield 'path separator' => [LogicalViewIdentity::class, 'private/path'];
        yield 'too long' => [RenderOperationIdentity::class, str_repeat('a', 129)];
        yield 'uppercase digest' => [CompiledViewIdentity::class, str_repeat('A', 64)];
        yield 'short digest' => [CompiledViewIdentity::class, str_repeat('a', 63)];
    }

    /** @param class-string $type */
    #[DataProvider('invalidIdentities')]
    public function testInvalidIdentitiesFailWithoutDisclosure(string $type, string $secret): void
    {
        try {
            new $type($secret);
            self::fail('Invalid identity was accepted.');
        } catch (InvalidViewContractException $failure) {
            self::assertInstanceOf(InvalidViewContractException::class, $failure);
            if ('' !== $secret) {
                self::assertStringNotContainsString($secret, $failure->getMessage());
                self::assertStringNotContainsString($secret, (string) $failure);
            }
        }
    }
}
