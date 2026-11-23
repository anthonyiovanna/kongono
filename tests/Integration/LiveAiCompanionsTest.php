<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use PHPUnit\Framework\TestCase;

/**
 * Opt-in live LLM end-to-end verification for Oshie, Chie, and Hantei.
 *
 * Run manually against any real configured provider (OpenAI, Anthropic, Gemini, Groq, or Ollama):
 *   KONGONO_TEST_LIVE_AI=1 php vendor/bin/phpunit tests/Integration/LiveAiCompanionsTest.php
 */
final class LiveAiCompanionsTest extends TestCase
{
    public function testLiveOshieChieAndHanteiEndToEndAgainstStarterApp(): void
    {
        if ('1' !== getenv('KONGONO_TEST_LIVE_AI')) {
            self::markTestSkipped('Set KONGONO_TEST_LIVE_AI=1 with an AI provider key or OLLAMA_HOST to run live LLM companion tests.');
        }

        try {
            $ai = AiClient::fromEnv();
        } catch (AiException $e) {
            self::markTestSkipped($e->getMessage());
        }

        $starterRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'starter-app';
        $context = ProjectContext::discover($starterRoot);

        // 1. Live Oshie Q&A grounded in starter-app ApplicationGraph
        $oshie = new OshieTutor($ai);
        $oshieReply = $oshie->ask('In 2 sentences, how are routes registered in this Kongono app?', $context);
        self::assertGreaterThan(20, strlen(trim($oshieReply)));

        // 2. Live Chie build plan (preview only — never writes to starter-app)
        $chie = new ChieBuilder($ai);
        $plan = $chie->build('Add a simple GET /api/ping JSON controller returning {"pong":true}.', $context, apply: false);
        self::assertSame([], $plan['applied']);
        self::assertNotSame('', trim($plan['summary']));
        self::assertNotEmpty($plan['changes']);

        // 3. Live Hantei failure diagnosis + test generation preview (never writes to starter-app)
        $hantei = new HanteiDebugger($ai);
        $diagnosis = $hantei->diagnose('HTTP 422 validation failed on field "title": field is required', $context);
        self::assertGreaterThan(20, strlen(trim($diagnosis)));

        $gen = $hantei->generateTests('src/Http/NoteController.php', $context, apply: false);
        self::assertFalse($gen['written']);
        self::assertStringContainsString('<?php', $gen['code']);
    }
}
