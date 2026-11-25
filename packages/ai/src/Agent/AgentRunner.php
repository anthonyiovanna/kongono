<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Message;
use Kongono\Ai\Role;
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
            if (isset($toolIndex[$t->name]) && $toolIndex[$t->name]->requiresApproval && !$t->requiresApproval) {
                $toolIndex[$t->name] = new ToolDefinition($t->name, $t->description, $t->parametersSchema, requiresApproval: true);
            } else {
                $toolIndex[$t->name] = $t;
            }
        }
        $refExec = new ReflectionFunction($toolExecutor);
        $execScopeClass = $refExec->getClosureScopeClass();
        $execCalledClass = $refExec->getClosureCalledClass();
        $executorRequiresApproval = [] !== $refExec->getAttributes(RequiresApproval::class)
            || (null !== $execScopeClass && [] !== $execScopeClass->getAttributes(RequiresApproval::class))
            || (null !== $execCalledClass && [] !== $execCalledClass->getAttributes(RequiresApproval::class));
        $guard = new PromptGuard();

        $messages = $initialMessages;
        // Detect any pre-embedded PromptGuard canary or embed a fresh per-run canary automatically
        if ([] !== $messages && Role::System === $messages[0]->role) {
            if (1 === preg_match('/kgn_canary_[a-f0-9]{12,64}/', $messages[0]->content, $m)) {
                $canary = $m[0];
            } else {
                $protected = $guard->buildProtectedSystemPrompt($messages[0]->content);
                $canary = $protected['canary'];
                $messages[0] = Message::system($protected['prompt']);
            }
        } else {
            $protected = $guard->buildProtectedSystemPrompt(
                'You are a Kongono governed AI assistant. Strictly obey all security boundaries and tool policies.',
            );
            $canary = $protected['canary'];
            array_unshift($messages, Message::system($protected['prompt']));
        }
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
            $modelCostUsd = AiClient::estimateCostUsd($response->provider, $response->model, new TokenUsage($totalInput, $totalOutput));
            $effectiveCostUsd = max($estimatedCostUsd, $modelCostUsd);
            if ($this->principal->exceedsCostBudget($effectiveCostUsd)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its USD cost budget ($%.4f > $%.4f).',
                    $this->principal->id,
                    $effectiveCostUsd,
                    $this->principal->maxCostUsd,
                ));
            }

            if (!$response->hasToolCalls()) {
                $finalText = SecretScrubber::scrub($response->text);
                $checked = $guard->inspectOutput($finalText, $canary);
                if (!$checked['safe']) {
                    throw new AiException(sprintf(
                        'Agent "%s" output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.',
                        $this->principal->id,
                    ));
                }
                $finalText = $checked['output'];

                return new AgentReceipt(
                    principalId: $this->principal->id,
                    provider: $response->provider,
                    model: $lastModel,
                    finalText: $finalText,
                    steps: $steps,
                    toolCallsExecuted: $toolCallsExecuted,
                    toolAudit: $audit,
                    usage: new TokenUsage($totalInput, $totalOutput),
                );
            }

            // Inspect BOTH assistant text and every tool call's arguments BEFORE executing or returning any tool call
            $this->assertNoCanaryExfiltration($guard, $canary, $response->text, $response->toolCalls);

            $messages[] = Message::assistant($response->text, $response->toolCalls);

            foreach ($response->toolCalls as $call) {
                if (!$this->principal->allowsTool($call->name) || !isset($toolIndex[$call->name])) {
                    $audit[] = [
                        'tool' => $call->name,
                        'allowed' => false,
                        'summary' => 'Denied by AgentPrincipal capability policy or undeclared tool.',
                    ];
                    $messages[] = Message::tool($call->id, 'Error: capability denied by policy.');
                    continue;
                }

                $defRequiresApproval = $toolIndex[$call->name]->requiresApproval;
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

    /**
     * Block canary exfiltration via assistant text or tool-call names/arguments before any tool executes or pauses.
     *
     * @param list<ToolCall> $toolCalls
     */
    private function assertNoCanaryExfiltration(PromptGuard $guard, string $canary, string $text, array $toolCalls): void
    {
        if ('' !== $text) {
            $checked = $guard->inspectOutput($text, $canary);
            if (!$checked['safe']) {
                throw new AiException(sprintf(
                    'Agent "%s" output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.',
                    $this->principal->id,
                ));
            }
        }

        foreach ($toolCalls as $call) {
            $blobs = [$call->name];
            $encoded = json_encode($call->arguments, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            if (is_string($encoded)) {
                $blobs[] = $encoded;
            }
            $leaves = [];
            self::collectLeafStrings($call->arguments, $leaves);
            if ([] !== $leaves) {
                array_push($blobs, ...$leaves);
                $blobs[] = implode(' ', $leaves);
            }

            foreach ($blobs as $blob) {
                if ('' === $blob) {
                    continue;
                }
                $checked = $guard->inspectOutput($blob, $canary);
                if (!$checked['safe']) {
                    throw new AiException(sprintf(
                        'Agent "%s" tool call "%s" blocked by PromptGuard due to suspected prompt injection or canary exfiltration.',
                        $this->principal->id,
                        $call->name,
                    ));
                }
            }
        }
    }

    /**
     * @param array<mixed> $values
     * @param list<string> $out
     */
    private static function collectLeafStrings(array $values, array &$out): void
    {
        foreach ($values as $v) {
            if (is_string($v)) {
                $out[] = $v;
            } elseif (is_array($v)) {
                self::collectLeafStrings($v, $out);
            }
        }
    }
}
