<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit\Internal;

use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\Internal\FrameworkSubject;
use PHPUnit\Framework\TestCase;

final class FrameworkSubjectTest extends TestCase
{
    public function testRepositorySubjectIsStableAndExcludesCallerRecordTrees(): void
    {
        $root = dirname(__DIR__, 5);
        $first = FrameworkSubject::capture($root);
        $second = FrameworkSubject::capture($root);

        self::assertSame($first->sha256(), $second->sha256());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $first->sha256());
        self::assertGreaterThan(0, $first->fileCount());
        self::assertGreaterThan(0, $first->sourceBytes());
    }

    public function testMissingRootIsRejectedWithoutDisclosure(): void
    {
        $this->expectException(InvalidHanteiRequestException::class);
        FrameworkSubject::capture(__DIR__ . '/missing-secret-root');
    }
}
