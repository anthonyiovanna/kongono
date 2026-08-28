<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\ConsoleApplication\KongoCommand;
use PHPUnit\Framework\TestCase;

final class KongoCommandTest extends TestCase
{
    public function testCommandVocabularyIsExact(): void
    {
        self::assertSame([
            'application:build',
            'observability:check',
            'observability:inspect',
            'security:check',
            'security:inspect',
        ], array_column(KongoCommand::cases(), 'value'));
        self::assertSame(KongoCommand::ApplicationBuild, KongoCommand::from('application:build'));
        self::assertSame(KongoCommand::ObservabilityCheck, KongoCommand::from('observability:check'));
        self::assertSame(KongoCommand::ObservabilityInspect, KongoCommand::from('observability:inspect'));
        self::assertSame(KongoCommand::SecurityCheck, KongoCommand::from('security:check'));
        self::assertSame(KongoCommand::SecurityInspect, KongoCommand::from('security:inspect'));
    }
}
