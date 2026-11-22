<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Message;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\Security\UntrustedInput;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Core\Diagnostics\SecretScrubber;
use ReflectionFunction;

/**
 * Bounded, capability-governed agent loop.
 *
 * Enforces explicit AgentPrincipal allowlists, human-in-the-loop approval gates (#[RequiresApproval] & principal policy),
 * PromptGuard untrusted boundaries on tool outputs, finite step/tool/token/USD budgets, and secret-scrubbed receipts.
 */
final readonly class AgentRunner
{
    private const int MAX_TOOL_OUTPUT_BYTES = 32_768;

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
        /** @var array<string, ToolDefinition> $toolIndex */
        $toolIndex = [];
        foreach ($admittedTools as $t) {
            $toolIndex[$t->name] = $t;
        }
        $executorRequiresApproval = [] !== (new ReflectionFunction($toolExecutor))->getAttributes(RequiresApproval::class);
        $guard = new PromptGuard();

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
                    finalText: SecretScrubber::scrub($response->text),
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

                $defRequiresApproval = isset($toolIndex[$call->name]) && $toolIndex[$call->name]->requiresApproval;
                if ($this->principal->requiresApproval($call->name) || $defRequiresApproval || $executorRequiresApproval) {
                    $audit[] = [
                        'tool' => $call->name,
                        'allowed' => false,
                        'summary' => 'Paused awaiting explicit human approval.',
                    ];
                    /** @var array<string, mixed> $scrubbedArgs */
                    $scrubbedArgs = SecretScrubber::scrubArray($call->arguments);

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
                        pendingApproval: new ToolCall($call->id, $call->name, $scrubbedArgs),
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
                $scrubbed = substr(SecretScrubber::scrub($result), 0, self::MAX_TOOL_OUTPUT_BYTES);
                $audit[] = [
                    'tool' => $call->name,
                    'allowed' => true,
                    'summary' => substr($scrubbed, 0, 160),
                ];
                $wrappedOutput = $guard->wrapUntrusted(new UntrustedInput($scrubbed, source: 'tool:' . $call->name));
                $messages[] = Message::tool($call->id, $wrappedOutput);
            }
        }

        throw new AiException(sprintf(
            'Agent "%s" reached maximum step budget (%d) without completing.',
            $this->principal->id,
            $this->principal->maxSteps,
        ));
    }
}
