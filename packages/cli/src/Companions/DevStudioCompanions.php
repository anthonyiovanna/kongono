<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Throwable;

/**
 * One-line factory wiring Oshie, Chie (strictly read/diff-preview only in browser),
 * and Hantei into Kongono's `/_kongono` Dev Studio middleware.
 */
final readonly class DevStudioCompanions
{
    public static function middleware(
        App $app,
        DevInspector $inspector,
        string $baseDir,
        string $prefix = '/_kongono',
        ?AiClient $aiOverride = null,
    ): DevDashboardMiddleware {
        $tryAi = static function () use ($aiOverride): ?AiClient {
            if (null !== $aiOverride) {
                return $aiOverride;
            }

            try {
                return AiClient::fromEnv();
            } catch (AiException) {
                return null;
            }
        };

        return new DevDashboardMiddleware(
            app: $app,
            inspector: $inspector,
            prefix: $prefix,
            oshieHandler: static function (string $question, ?string $lesson) use ($app, $baseDir, $tryAi): string {
                $ctx = new ProjectContext($baseDir, $app->graph());
                $oshie = new OshieTutor($tryAi());

                return null !== $lesson
                    ? $oshie->teachLesson($lesson, $ctx)
                    : $oshie->ask('' !== $question ? $question : 'Explain routing in Kongono.', $ctx);
            },
            hanteiHandler: static function (string $diagnostic) use ($app, $baseDir, $tryAi): string {
                $ctx = new ProjectContext($baseDir, $app->graph());

                return (new HanteiDebugger($tryAi()))->diagnose($diagnostic, $ctx);
            },
            chieHandler: static function (string $instruction) use ($app, $baseDir, $tryAi): array {
                $ai = $tryAi();
                if (null === $ai) {
                    return [
                        'summary' => 'Chie preview requires an AI provider. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST in .env.',
                        'diffs' => [],
                        'apply_command' => sprintf('php vendor/bin/kongo chie %s --apply', escapeshellarg($instruction)),
                    ];
                }

                $ctx = new ProjectContext($baseDir, $app->graph());
                $plan = (new ChieBuilder($ai))->build($instruction, $ctx, apply: false);
                $diffs = [];
                foreach ($plan['changes'] as $change) {
                    $before = null;
                    try {
                        $before = $ctx->readFileSafe($change['path']);
                    } catch (Throwable) {
                        $before = null;
                    }
                    $diffs[] = [
                        'path' => $change['path'],
                        'action' => $change['action'],
                        'explanation' => $change['explanation'],
                        'diff' => DiffRenderer::render($change['path'], $before, $change['content']),
                    ];
                }

                return [
                    'summary' => sprintf('Chie (%s / %s): %s', $plan['provider'], $plan['model'], $plan['summary']),
                    'diffs' => $diffs,
                    'next_steps' => $plan['next_steps'],
                    'apply_command' => sprintf('php vendor/bin/kongo chie %s --apply', escapeshellarg($instruction)),
                ];
            },
        );
    }
}
