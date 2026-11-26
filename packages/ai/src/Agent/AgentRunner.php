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
     * Execute the agent loop with per-tool callable handlers so method- and class-level `#[RequiresApproval]`
     * attributes on individual dispatcher targets are automatically detected and enforced.
     *
     * @param list<Message>                      $initialMessages
     * @param list<ToolDefinition>               $tools
     * @param array<string, callable(ToolCall): string> $handlers Map of tool name => target callable
     */
    public function runWithHandlers(array $initialMessages, array $tools, array $handlers): AgentReceipt
    {
        /** @var list<ToolDefinition> $enriched */
        $enriched = [];
        foreach ($tools as $tool) {
            $handler = $handlers[$tool->name] ?? null;
            $requiresApproval = $tool->requiresApproval
                || (null !== $handler && ToolDefinition::callableRequiresApproval($handler));
            $enriched[] = $requiresApproval === $tool->requiresApproval
                ? $tool
                : new ToolDefinition($tool->name, $tool->description, $tool->parametersSchema, requiresApproval: true);
        }

        return $this->run(
            $initialMessages,
            $enriched,
            static function (ToolCall $call) use ($handlers): string {
                $handler = $handlers[$call->name] ?? null;
                if (null === $handler) {
                    return 'Error: no handler registered for tool "' . $call->name . '".';
                }

                return $handler($call);
            },
        );
    }

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
        // Detect ONLY a trusted PromptGuard canary (never spoofed inside <untrusted_data>) or embed a fresh canary
        if ([] !== $messages && Role::System === $messages[0]->role) {
            $extractedCanary = $guard->extractTrustedCanary($messages[0]->content);
            if (null !== $extractedCanary) {
                $canary = $extractedCanary;
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
        $cumulativeCostUsd = 0.0;
        $lastModel = $this->client->defaultModel();

        while ($steps < $this->principal->maxSteps) {
            ++$steps;
            $preFlightInputTokens = $this->estimateInputTokens($messages, $admittedTools);
            if ($this->principal->exceedsTokenBudget($totalInput + $totalOutput + $preFlightInputTokens)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its token budget before dispatch (%d > %d).',
                    $this->principal->id,
                    $totalInput + $totalOutput + $preFlightInputTokens,
                    $this->principal->maxTokens,
                ));
            }
            $preFlightInputCost = ($preFlightInputTokens / 1000.0) * $this->inputCostPer1kTokens;
            if ($this->principal->exceedsCostBudget($cumulativeCostUsd + $preFlightInputCost)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its USD cost budget before dispatch ($%.4f > $%.4f).',
                    $this->principal->id,
                    $cumulativeCostUsd + $preFlightInputCost,
                    $this->principal->maxCostUsd,
                ));
            }
            $remainingTokens = $this->principal->maxTokens > 0
                ? max(1, $this->principal->maxTokens - ($totalInput + $totalOutput + $preFlightInputTokens))
                : 2048;
            $req = new ChatRequest($messages, maxTokens: min(2048, $remainingTokens), tools: $admittedTools);
            $response = $this->client->chat($req);
            $lastModel = $response->model;
            $stepUsage = $this->effectiveStepUsage($req, $response->text, $response->toolCalls, $response->usage);
            $totalInput += $stepUsage->inputTokens;
            $totalOutput += $stepUsage->outputTokens;

            if ($this->principal->exceedsTokenBudget($totalInput + $totalOutput)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its token budget (%d > %d).',
                    $this->principal->id,
                    $totalInput + $totalOutput,
                    $this->principal->maxTokens,
                ));
            }

            $stepEstimatedCost = (($stepUsage->inputTokens / 1000.0) * $this->inputCostPer1kTokens)
                + (($stepUsage->outputTokens / 1000.0) * $this->outputCostPer1kTokens);
            $stepModelCost = AiClient::estimateCostUsd($response->provider, $response->model, $stepUsage);
            $cumulativeCostUsd += max($stepEstimatedCost, $stepModelCost);
            if ($this->principal->exceedsCostBudget($cumulativeCostUsd)) {
                throw new AiException(sprintf(
                    'Agent "%s" exceeded its USD cost budget ($%.4f > $%.4f).',
                    $this->principal->id,
                    $cumulativeCostUsd,
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

            // Pre-scan all allowed tool calls for approval gates BEFORE executing ANY tool in this turn
            foreach ($response->toolCalls as $candidateCall) {
                if (!$this->principal->allowsTool($candidateCall->name) || !isset($toolIndex[$candidateCall->name])) {
                    continue;
                }
                $defRequiresApproval = $toolIndex[$candidateCall->name]->requiresApproval;
                if ($this->principal->requiresApproval($candidateCall->name) || $defRequiresApproval || $executorRequiresApproval) {
                    $audit[] = [
                        'tool' => $candidateCall->name,
                        'allowed' => false,
                        'summary' => 'Paused awaiting explicit human approval.',
                    ];
                    /** @var array<string, mixed> $scrubbedArgs */
                    $scrubbedArgs = SecretScrubber::scrubArray($candidateCall->arguments);

                    return new AgentReceipt(
                        principalId: $this->principal->id,
                        provider: $response->provider,
                        model: $lastModel,
                        finalText: sprintf('Paused: tool "%s" requires human approval before execution.', $candidateCall->name),
                        steps: $steps,
                        toolCallsExecuted: $toolCallsExecuted,
                        toolAudit: $audit,
                        usage: new TokenUsage($totalInput, $totalOutput),
                        status: 'awaiting_approval',
                        pendingApproval: new ToolCall($candidateCall->id, $candidateCall->name, $scrubbedArgs),
                    );
                }
            }

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
     * Conservative pre-flight input token estimate (messages + tool schemas) enforced before dispatching to an LLM.
     *
     * @param list<Message>        $messages
     * @param list<ToolDefinition> $tools
     */
    private function estimateInputTokens(array $messages, array $tools): int
    {
        $bytes = 0;
        foreach ($messages as $msg) {
            $bytes += strlen($msg->content);
        }
        foreach ($tools as $tool) {
            $bytes += strlen($tool->name) + strlen($tool->description);
            $schemaJson = json_encode($tool->parametersSchema, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            if (is_string($schemaJson)) {
                $bytes += strlen($schemaJson);
            }
        }

        return $bytes > 0 ? max(1, (int) ceil($bytes / 4)) : 0;
    }

    /**
     * Fall back to conservative byte-length token estimation when a provider omits usage telemetry.
     *
     * @param list<ToolCall> $toolCalls
     */
    private function effectiveStepUsage(ChatRequest $request, string $text, array $toolCalls, TokenUsage $reported): TokenUsage
    {
        if ($reported->totalTokens() > 0) {
            return $reported;
        }
        $inputBytes = 0;
        foreach ($request->messages as $msg) {
            $inputBytes += strlen($msg->content);
        }
        $outputBytes = strlen($text);
        foreach ($toolCalls as $call) {
            $outputBytes += strlen($call->name);
            $enc = json_encode($call->arguments, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            if (is_string($enc)) {
                $outputBytes += strlen($enc);
            }
        }
        if (0 === $inputBytes && 0 === $outputBytes) {
            return $reported;
        }

        return new TokenUsage(
            inputTokens: $inputBytes > 0 ? max(1, (int) ceil($inputBytes / 4)) : 0,
            outputTokens: $outputBytes > 0 ? max(1, (int) ceil($outputBytes / 4)) : 0,
        );
    }

    /**
     * Block canary exfiltration via assistant text or tool-call names/arguments (including across split parallel calls)
     * before any tool executes or pauses.
     *
     * @param list<ToolCall> $toolCalls
     */
    private function assertNoCanaryExfiltration(PromptGuard $guard, string $canary, string $text, array $toolCalls): void
    {
        /** @var list<string> $combinedParts */
        $combinedParts = [];
        if ('' !== $text) {
            $combinedParts[] = $text;
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
                $joinedLeaves = implode(' ', $leaves);
                $blobs[] = $joinedLeaves;
                $combinedParts[] = $joinedLeaves;
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

        // Also inspect combined assistant text + all parallel tool-call leaf arguments to catch split-canary exfiltration
        if (count($combinedParts) > 1) {
            $combinedCheck = $guard->inspectOutput(implode('', $combinedParts), $canary);
            if (!$combinedCheck['safe']) {
                throw new AiException(sprintf(
                    'Agent "%s" combined output blocked by PromptGuard due to suspected split canary exfiltration.',
                    $this->principal->id,
                ));
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
