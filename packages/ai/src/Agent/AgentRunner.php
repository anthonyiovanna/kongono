<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Message;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Bounded, capability-governed agent loop.
 *
 * Enforces explicit AgentPrincipal allowlists, human-in-the-loop approval gates,
 * finite step/tool/token budgets, and nondisclosing audit receipts on every tool execution.
 */
final readonly class AgentRunner
{
    public function __construct(
        private AiClient $client,
        private AgentPrincipal $principal,
        private float $inputCostPer1kTokens = 0.005,
        private float $outputCostPer1kTokens = 0.015,
    ) {}

    /**
     * @param list<Message>             $initialMessages
     * @param list<ToolDefinition>      $tools
     * @param Closure(ToolCall): string $toolExecutor
     */
    public function run(array $initialMessages, array $tools, Closure $toolExecutor): AgentReceipt
    {
        $admittedTools = array_values(array_filter(
            $tools,
            fn(ToolDefinition $t): bool => $this->principal->allowsTool($t->name),
        ));

        $messages = $initialMessages;
        $steps = 0;
        $toolCallsExecuted = 0;
        $audit = [];
        $totalInput = 0;
        $totalOutput = 0;
        $lastModel = $this->client->defaultModel();

        while ($steps < $this->principal->maxSteps) {
            ++$steps;
            $response = $this->client->chat(new ChatRequest($messages, tools: $admittedTools));
            $lastModel = $response->model;
            $totalInput += $response->usage->inputTokens;
            $totalOutput += $response->usage->outputTokens;

            if ($this->principal->exceedsTokenBudget($totalInput + $totalOutput)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its token budget (%d > %d).',
                    $this->principal->id,
                    $totalInput + $totalOutput,
                    $this->principal->maxTokens,
                ));
            }

            $estimatedCostUsd = (($totalInput / 1000.0) * $this->inputCostPer1kTokens)
                + (($totalOutput / 1000.0) * $this->outputCostPer1kTokens);
            if ($this->principal->exceedsCostBudget($estimatedCostUsd)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its USD cost budget ($%.4f > $%.4f).',
                    $this->principal->id,
                    $estimatedCostUsd,
                    $this->principal->maxCostUsd,
                ));
            }

            if (!$response->hasToolCalls()) {
                return new AgentReceipt(
                    principalId: $this->principal->id,
                    provider: $response->provider,
                    model: $lastModel,
                    finalText: $response->text,
                    steps: $steps,
                    toolCallsExecuted: $toolCallsExecuted,
                    toolAudit: $audit,
                    usage: new TokenUsage($totalInput, $totalOutput),
                );
            }

            $messages[] = Message::assistant($response->text, $response->toolCalls);

            foreach ($response->toolCalls as $call) {
                if (!$this->principal->allowsTool($call->name)) {
                    $audit[] = [
                        'tool' => $call->name,
                        'allowed' => false,
                        'summary' => 'Denied by AgentPrincipal capability policy.',
                    ];
                    $messages[] = Message::tool($call->id, 'Error: capability denied by policy.');
                    continue;
                }

                if ($this->principal->requiresApproval($call->name)) {
                    $audit[] = [
                        'tool' => $call->name,
                        'allowed' => false,
                        'summary' => 'Paused awaiting explicit human approval.',
                    ];

                    return new AgentReceipt(
                        principalId: $this->principal->id,
                        provider: $response->provider,
                        model: $lastModel,
                        finalText: sprintf('Paused: tool "%s" requires human approval before execution.', $call->name),
                        steps: $steps,
                        toolCallsExecuted: $toolCallsExecuted,
                        toolAudit: $audit,
                        usage: new TokenUsage($totalInput, $totalOutput),
                        status: 'awaiting_approval',
                        pendingApproval: $call,
                    );
                }

                if ($toolCallsExecuted >= $this->principal->maxToolCalls) {
                    throw new AiException(sprintf(
                        'Agent "%s" exceeded its max tool call budget (%d).',
                        $this->principal->id,
                        $this->principal->maxToolCalls,
                    ));
                }

                ++$toolCallsExecuted;
                $result = $toolExecutor($call);
                $scrubbed = SecretScrubber::scrub($result);
                $audit[] = [
                    'tool' => $call->name,
                    'allowed' => true,
                    'summary' => substr($scrubbed, 0, 160),
                ];
                $messages[] = Message::tool($call->id, $scrubbed);
            }
        }

        throw new AiException(sprintf(
            'Agent "%s" reached maximum step budget (%d) without completing.',
            $this->principal->id,
            $this->principal->maxSteps,
        ));
    }
}
