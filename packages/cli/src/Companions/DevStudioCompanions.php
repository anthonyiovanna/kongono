<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Throwable;

/**
 * One-line factory wiring Oshie (trace-aware + SSE), Chie (strictly read/diff-preview only in browser),
 * and Hantei (SSE + live trace diagnosis + regression test export) into Kongono's `/_kongono` Dev Studio.
 */
final readonly class DevStudioCompanions
{
    public static function middleware(
        App $app,
        DevInspector $inspector,
        string $baseDir,
        string $prefix = '/_kongono',
        ?AiClient $aiOverride = null,
        ?float $maxCostUsd = null,
    ): DevDashboardMiddleware {
        $tryAi = static function (string $companion) use ($aiOverride): ?AiClient {
            if (null !== $aiOverride) {
                return $aiOverride;
            }

            try {
                return AiClient::fromEnvForCompanion($companion);
            } catch (AiException) {
                return null;
            }
        };

        return new DevDashboardMiddleware(
            app: $app,
            inspector: $inspector,
            prefix: $prefix,
            oshieHandler: static function (string $question, ?string $lesson) use ($app, $inspector, $baseDir, $tryAi, $maxCostUsd): string {
                $ctx = new ProjectContext($baseDir, $app->graph(), $app);
                $oshie = new OshieTutor($tryAi('oshie'));

                if (null !== $lesson) {
                    return $oshie->teachLesson($lesson, $ctx);
                }

                $res = $oshie->askWithTelemetry(
                    '' !== $question ? $question : 'Explain routing in Kongono.',
                    $ctx,
                    $inspector->traces(),
                    $maxCostUsd,
                );

                return null !== $res['telemetry']
                    ? $res['reply'] . "\n\n" . $res['telemetry']->formatFooter()
                    : $res['reply'];
            },
            hanteiHandler: static function (string $diagnostic) use ($app, $baseDir, $tryAi, $maxCostUsd): string {
                $ctx = new ProjectContext($baseDir, $app->graph(), $app);
                $res = (new HanteiDebugger($tryAi('hantei')))->diagnoseWithTelemetry($diagnostic, $ctx, $maxCostUsd);

                return null !== $res['telemetry']
                    ? $res['diagnosis'] . "\n\n" . $res['telemetry']->formatFooter()
                    : $res['diagnosis'];
            },
            chieHandler: static function (string $instruction) use ($app, $baseDir, $tryAi, $maxCostUsd): array {
                $ai = $tryAi('chie');
                if (null === $ai) {
                    return [
                        'summary' => 'Chie preview requires an AI provider. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST in .env.',
                        'diffs' => [],
                        'apply_command' => sprintf('php vendor/bin/kongo chie %s --apply --verify', escapeshellarg($instruction)),
                    ];
                }

                $ctx = new ProjectContext($baseDir, $app->graph(), $app);
                $plan = (new ChieBuilder($ai))->build($instruction, $ctx, apply: false, maxCostUsd: $maxCostUsd);
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
                    'telemetry' => $plan['telemetry']->toArray(),
                    'apply_command' => sprintf('php vendor/bin/kongo chie %s --apply --verify', escapeshellarg($instruction)),
                ];
            },
            oshieStreamHandler: static function (string $question, ?string $lesson, Closure $onDelta) use ($app, $inspector, $baseDir, $tryAi, $maxCostUsd): array {
                $ctx = new ProjectContext($baseDir, $app->graph(), $app);
                $oshie = new OshieTutor($tryAi('oshie'));

                if (null !== $lesson) {
                    $reply = $oshie->teachLesson($lesson, $ctx);
                    $onDelta($reply);

                    return ['reply' => $reply, 'telemetry' => null];
                }

                $res = $oshie->askStream(
                    '' !== $question ? $question : 'Explain routing in Kongono.',
                    $ctx,
                    $onDelta,
                    $inspector->traces(),
                    $maxCostUsd,
                );

                return [
                    'reply' => $res['reply'],
                    'telemetry' => $res['telemetry']?->toArray(),
                ];
            },
            hanteiStreamHandler: static function (string $diagnostic, Closure $onDelta) use ($app, $baseDir, $tryAi, $maxCostUsd): array {
                $ctx = new ProjectContext($baseDir, $app->graph(), $app);
                $res = (new HanteiDebugger($tryAi('hantei')))->diagnoseStream($diagnostic, $ctx, $onDelta, $maxCostUsd);

                return [
                    'diagnosis' => $res['diagnosis'],
                    'telemetry' => $res['telemetry']?->toArray(),
                ];
            },
            hanteiTraceHandler: static function (array $trace) use ($app, $baseDir, $tryAi, $maxCostUsd): array {
                $ctx = new ProjectContext($baseDir, $app->graph(), $app);
                $res = (new HanteiDebugger($tryAi('hantei')))->diagnoseTrace($trace, $ctx, $maxCostUsd);

                return [
                    'trace_id' => $res['trace_id'],
                    'diagnosis' => $res['diagnosis'],
                    'regression_test' => $res['regression_test'],
                    'telemetry' => $res['telemetry']?->toArray(),
                ];
            },
        );
    }
}
