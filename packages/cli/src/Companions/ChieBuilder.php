<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use JsonException;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Message;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Context\ProjectContext;

/**
 * Chie (知恵) — Kongono's built-in project-aware AI builder & scaffolder.
 *
 * Uses a governed AgentPrincipal to inspect the live ApplicationGraph and project files,
 * then proposes clean PHP 8.5 Kongono code changes (previewed by default; written only with --apply).
 */
final readonly class ChieBuilder
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are Chie (知恵), the built-in AI code builder for Kongono v1 (pure PHP 8.5+, zero Symfony, zero magic facades).

You can inspect the project's files and live ApplicationGraph via tools before generating code.
When you are ready with your final answer, respond ONLY with a valid JSON object matching this exact structure:
{
  "summary": "Brief description of what you built",
  "changes": [
    {
      "path": "relative/path/from/project/root.php",
      "action": "create|update",
      "explanation": "Why this file is created or updated",
      "content": "Full file contents (valid PHP 8.5 with declare(strict_types=1); or .kgn.html)"
    }
  ],
  "next_steps": ["Optional reminder such as registering a route in app/bootstrap.php"]
}

Rules:
- Follow Kongono v1 conventions strictly (Kongono\Http\Request, Kongono\Http\Response, Kongono\View\ViewEngine, Kongono\Validation\Attributes\*, final readonly classes).
- Never invent Laravel or Symfony APIs.
- Always return valid JSON with no markdown code fences around it.
PROMPT;

    public function __construct(
        private AiClient $ai,
    ) {}

    /**
     * Plan (and optionally apply) a code generation task in the target Kongono project.
     *
     * @return array{
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>,
     *     applied: list<string>,
     *     provider: string,
     *     model: string
     * }
     */
    public function build(string $instruction, ProjectContext $context, bool $apply = false): array
    {
        $principal = new AgentPrincipal(
            id: 'chie-builder',
            role: 'Kongono Project Builder',
            allowedTools: ['list_files', 'read_file', 'inspect_graph'],
            maxSteps: 6,
            maxToolCalls: 8,
        );

        $tools = [
            new ToolDefinition('list_files', 'List relative file paths in the Kongono project.'),
            new ToolDefinition('inspect_graph', 'Inspect the live ApplicationGraph JSON (routes, services, middleware, views).'),
            new ToolDefinition(
                'read_file',
                'Read a secret-scrubbed file from the project.',
                [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'Relative file path from project root'],
                    ],
                    'required' => ['path'],
                ],
            ),
        ];

        $messages = [
            Message::system(self::SYSTEM_PROMPT),
            Message::user(sprintf(
                "Current project summary:\n%s\n\nBuild task:\n%s",
                $context->promptSummary(),
                $instruction,
            )),
        ];

        $runner = new AgentRunner($this->ai, $principal);
        $receipt = $runner->run($messages, $tools, static function (ToolCall $call) use ($context): string {
            return match ($call->name) {
                'list_files' => implode("\n", $context->listFiles()),
                'inspect_graph' => null !== $context->graph ? $context->graph->toJson() : '{"graph":null}',
                'read_file' => is_string($call->arguments['path'] ?? null)
                    ? $context->readFileSafe($call->arguments['path'])
                    : 'Error: missing string "path" argument.',
                default => 'Error: unknown tool.',
            };
        });

        $plan = $this->parsePlan($receipt->finalText);
        $applied = [];

        if ($apply) {
            foreach ($plan['changes'] as $change) {
                $context->writeFileContained($change['path'], $change['content']);
                $applied[] = $change['path'];
            }
        }

        return [
            'summary' => $plan['summary'],
            'changes' => $plan['changes'],
            'next_steps' => $plan['next_steps'],
            'applied' => $applied,
            'provider' => $receipt->provider,
            'model' => $receipt->model,
        ];
    }

    /**
     * @return array{
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>
     * }
     */
    private function parsePlan(string $raw): array
    {
        $clean = trim($raw);
        if (str_starts_with($clean, '```')) {
            $stripped = preg_replace('/\A```(?:json)?\s*|\s*```\z/', '', $clean);
            if (is_string($stripped)) {
                $clean = trim($stripped);
            }
        }

        try {
            $decoded = json_decode($clean, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AiException('Chie received non-JSON build plan from model.', previous: $e);
        }

        if (!is_array($decoded)) {
            throw new AiException('Chie build plan must be a JSON object.');
        }

        $changes = [];
        if (isset($decoded['changes']) && is_array($decoded['changes'])) {
            foreach ($decoded['changes'] as $item) {
                if (!is_array($item) || !is_string($item['path'] ?? null) || !is_string($item['content'] ?? null)) {
                    continue;
                }
                $changes[] = [
                    'path' => $item['path'],
                    'action' => is_string($item['action'] ?? null) ? $item['action'] : 'create',
                    'explanation' => is_string($item['explanation'] ?? null) ? $item['explanation'] : '',
                    'content' => $item['content'],
                ];
            }
        }

        $nextSteps = [];
        if (isset($decoded['next_steps']) && is_array($decoded['next_steps'])) {
            foreach ($decoded['next_steps'] as $step) {
                if (is_string($step)) {
                    $nextSteps[] = $step;
                }
            }
        }

        return [
            'summary' => is_string($decoded['summary'] ?? null) ? $decoded['summary'] : 'Generated build plan.',
            'changes' => $changes,
            'next_steps' => $nextSteps,
        ];
    }
}
