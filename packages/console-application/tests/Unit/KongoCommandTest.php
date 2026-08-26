<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\ConsoleApplication\KongoCommand;
use PHPUnit\Framework\TestCase;

final class KongoCommandTest extends TestCase
{
    public function testCommandVocabularyIsExact(): void
    {
        self::assertSame(['application:build'], array_column(KongoCommand::cases(), 'value'));
        self::assertSame(KongoCommand::ApplicationBuild, KongoCommand::from('application:build'));
    }
}
