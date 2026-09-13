<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Generated;

use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class GeneratedPhpEmitterTest extends TestCase
{
    public function testGeneratedPhpIsDeterministicInertAndUsesOnlyTheClosedSinkProtocol(): void
    {
        $first = BuildProbe::artifact();
        $second = BuildProbe::artifact();
        self::assertSame($first->generatedPhpBytes(), $second->generatedPhpBytes());
        $php = $first->generatedPhpBytes();
        self::assertNotEmpty(token_get_all($php, TOKEN_PARSE));
        self::assertStringContainsString('final class CompiledViewSet_' . $first->modelSha256(), $php);
        self::assertStringContainsString('match ($view->value())', $php);
        self::assertStringContainsString('$sink->literal(', $php);
        self::assertStringContainsString('$sink->value(', $php);
        self::assertStringContainsString('$sink->step(', $php);
        self::assertStringContainsString('&lt;safe &amp; static&gt;', $php);
        self::assertStringContainsString('&quot;', $php);
        foreach (['eval(', 'include ', 'require ', 'getenv(', 'file_get_contents(', 'Reflection', 'function ('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $php);
        }
    }
}
