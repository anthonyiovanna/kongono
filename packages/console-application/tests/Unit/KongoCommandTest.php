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
            'application-graph:inspect',
            'application:build',
            'asset:build',
            'asset:check',
            'asset:resolve',
            'extension:inspect',
            'observability:check',
            'observability:inspect',
            'security:check',
            'security:inspect',
            'view:build',
            'view:check',
            'view:inspect',
            'cache:build',
            'cache:check',
            'cache:plan',
        ], array_column(KongoCommand::cases(), 'value'));
        self::assertSame(KongoCommand::ApplicationBuild, KongoCommand::from('application:build'));
        self::assertSame(KongoCommand::ApplicationGraphInspect, KongoCommand::from('application-graph:inspect'));
        self::assertSame(KongoCommand::AssetBuild, KongoCommand::from('asset:build'));
        self::assertSame(KongoCommand::AssetCheck, KongoCommand::from('asset:check'));
        self::assertSame(KongoCommand::AssetResolve, KongoCommand::from('asset:resolve'));
        self::assertSame(KongoCommand::CacheBuild, KongoCommand::from('cache:build'));
        self::assertSame(KongoCommand::CacheCheck, KongoCommand::from('cache:check'));
        self::assertSame(KongoCommand::CachePlan, KongoCommand::from('cache:plan'));
        self::assertSame(KongoCommand::ExtensionInspect, KongoCommand::from('extension:inspect'));
        self::assertSame(KongoCommand::ObservabilityCheck, KongoCommand::from('observability:check'));
        self::assertSame(KongoCommand::ObservabilityInspect, KongoCommand::from('observability:inspect'));
        self::assertSame(KongoCommand::SecurityCheck, KongoCommand::from('security:check'));
        self::assertSame(KongoCommand::SecurityInspect, KongoCommand::from('security:inspect'));
        self::assertSame(KongoCommand::ViewBuild, KongoCommand::from('view:build'));
        self::assertSame(KongoCommand::ViewCheck, KongoCommand::from('view:check'));
        self::assertSame(KongoCommand::ViewInspect, KongoCommand::from('view:inspect'));
    }
}
