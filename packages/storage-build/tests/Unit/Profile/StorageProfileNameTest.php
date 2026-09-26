<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Profile;

use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Profile\StorageProfileName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageProfileNameTest extends TestCase
{
    public function testAcceptedBoundariesAreExact(): void
    {
        self::assertSame('a', (new StorageProfileName('a'))->value());
        self::assertSame(str_repeat('a', 64), (new StorageProfileName(str_repeat('a', 64)))->value());
        self::assertTrue((new StorageProfileName('object-v1'))->equals(new StorageProfileName('object-v1')));
    }

    #[DataProvider('invalidNames')]
    public function testInvalidNamesFailClosed(string $value): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        new StorageProfileName($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        foreach ([
            'empty' => '',
            'uppercase' => 'Object',
            'leading-hyphen' => '-object',
            'trailing-hyphen' => 'object-',
            'repeated-hyphen' => 'object--v1',
            'dot' => 'object.v1',
            'underscore' => 'object_v1',
            'slash' => 'object/v1',
            'backslash' => 'object\\v1',
            'space' => 'object v1',
            'control' => "object\nv1",
            'unicode' => 'objéct',
            'too-long' => str_repeat('a', 65),
        ] as $label => $value) {
            yield $label => [$value];
        }
    }
}
