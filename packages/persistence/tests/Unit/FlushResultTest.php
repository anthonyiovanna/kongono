<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\FlushOutcome;
use Kongono\Persistence\FlushResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlushResultTest extends TestCase
{
    #[DataProvider('validResults')]
    public function testExactOutcomeShapesAreAccepted(FlushOutcome $outcome, int $attempted, int $applied, int $absent, int $conflicts): void
    {
        $result = new FlushResult($outcome, $attempted, $applied, $absent, $conflicts);

        self::assertSame($outcome, $result->outcome());
        self::assertSame($attempted, $result->attemptedCount());
        self::assertSame($applied, $result->appliedCount());
        self::assertSame($absent, $result->absentCount());
        self::assertSame($conflicts, $result->conflictCount());
    }

    /** @return iterable<string, array{FlushOutcome, int, int, int, int}> */
    public static function validResults(): iterable
    {
        yield 'none' => [FlushOutcome::NoChanges, 0, 0, 0, 0];
        yield 'applied minimum' => [FlushOutcome::Applied, 1, 1, 0, 0];
        yield 'applied maximum' => [FlushOutcome::Applied, 1024, 1024, 0, 0];
        yield 'absent partial' => [FlushOutcome::EntityAbsent, 4, 0, 2, 0];
        yield 'absent maximum' => [FlushOutcome::EntityAbsent, 1024, 0, 1024, 0];
        yield 'conflict partial' => [FlushOutcome::OptimisticConflict, 4, 0, 0, 2];
        yield 'conflict maximum' => [FlushOutcome::OptimisticConflict, 1024, 0, 0, 1024];
    }

    #[DataProvider('invalidResults')]
    public function testInvalidCountsAndOutcomeShapesAreRejected(FlushOutcome $outcome, int $attempted, int $applied, int $absent, int $conflicts): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        new FlushResult($outcome, $attempted, $applied, $absent, $conflicts);
    }

    /** @return iterable<string, array{FlushOutcome, int, int, int, int}> */
    public static function invalidResults(): iterable
    {
        yield 'negative' => [FlushOutcome::NoChanges, -1, 0, 0, 0];
        yield 'over bound' => [FlushOutcome::Applied, 1025, 1025, 0, 0];
        yield 'no changes attempted' => [FlushOutcome::NoChanges, 1, 0, 0, 0];
        yield 'applied empty' => [FlushOutcome::Applied, 0, 0, 0, 0];
        yield 'applied partial' => [FlushOutcome::Applied, 2, 1, 0, 0];
        yield 'applied conflict' => [FlushOutcome::Applied, 1, 1, 0, 1];
        yield 'absent empty' => [FlushOutcome::EntityAbsent, 0, 0, 0, 0];
        yield 'absent applied' => [FlushOutcome::EntityAbsent, 2, 1, 1, 0];
        yield 'absent conflict' => [FlushOutcome::EntityAbsent, 2, 0, 1, 1];
        yield 'absent excess' => [FlushOutcome::EntityAbsent, 2, 0, 3, 0];
        yield 'conflict empty' => [FlushOutcome::OptimisticConflict, 0, 0, 0, 0];
        yield 'conflict applied' => [FlushOutcome::OptimisticConflict, 2, 1, 0, 1];
        yield 'conflict absent' => [FlushOutcome::OptimisticConflict, 2, 0, 1, 1];
        yield 'conflict missing' => [FlushOutcome::OptimisticConflict, 2, 0, 0, 0];
        yield 'conflict excess' => [FlushOutcome::OptimisticConflict, 2, 0, 0, 3];
    }
}
