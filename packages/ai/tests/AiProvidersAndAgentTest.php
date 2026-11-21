<?php

declare(strict_types=1);

namespace Kongono\Ai\Tests;

use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Message;
use Kongono\Ai\Provider\AnthropicProvider;
use Kongono\Ai\Provider\GeminiProvider;
use Kongono\Ai\Provider\OllamaProvider;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Required;
use PHPUnit\Framework\TestCase;

final readonly class SentimentDto
{
    public function __construct(
        #[Required, In(['positive', 'neutral', 'negative'])]
        public string $sentiment,
        #[Required]
        public string $summary,
    ) {}
}

final class FakeTransport implements HttpTransportInterface
{
    /** @var list<array{url: string, headers: array<string, string>, body: string}> */
    public array $recorded = [];

    /** @var list<HttpResponse> */
    private array $queue;

    public function __construct(HttpResponse ...$responses)
    {
        $this->queue = array_values($responses);
    }

    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        $this->recorded[] = ['url' => $url, 'headers' => $headers, 'body' => $jsonBody];
        $next = array_shift($this->queue);

        return $next ?? new HttpResponse(500, '{"error":"empty queue"}');
    }
}

final class AiProvidersAndAgentTest extends TestCase
{
    public function testOpenAiProviderHandlesRealForwardCompatiblePayload(): void
    {
        $transport = new FakeTransport(new HttpResponse(200, json_encode([
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1760000000,
            'model' => 'gpt-4.1-mini-2026-04-01',
            'service_tier' => 'default',
            'system_fingerprint' => 'fp_abc',
            'choices' => [[
                'index' => 0,
                'finish_reason' => 'stop',
                'logprobs' => null,
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Hello from OpenAI!',
                    'refusal' => null,
                    'annotations' => [['type' => 'url_citation']],
                ],
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
        ], JSON_THROW_ON_ERROR)));

        $client = new AiClient(new OpenAiProvider('sk-test-secret-12345678901234', transport: $transport));
        $reply = $client->ask('Hi', 'Be brief');

        self::assertSame('Hello from OpenAI!', $reply);
        self::assertStringEndsWith('/chat/completions', $transport->recorded[0]['url']);
        self::assertSame('Bearer sk-test-secret-12345678901234', $transport->recorded[0]['headers']['Authorization']);
    }

    public function testAnthropicGeminiAndOllamaProvidersSpeakNativeWireProtocols(): void
    {
        $anthropicTransport = new FakeTransport(new HttpResponse(200, json_encode([
            'id' => 'msg_01',
            'model' => 'claude-sonnet-4-20250514',
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Hello from Claude!']],
            'usage' => ['input_tokens' => 8, 'output_tokens' => 4],
        ], JSON_THROW_ON_ERROR)));
        $anthropic = new AiClient(new AnthropicProvider('sk-ant-test-key', transport: $anthropicTransport));
        self::assertSame('Hello from Claude!', $anthropic->ask('Hi', 'System rule'));
        self::assertSame('sk-ant-test-key', $anthropicTransport->recorded[0]['headers']['x-api-key']);

        $geminiTransport = new FakeTransport(new HttpResponse(200, json_encode([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['role' => 'model', 'parts' => [['text' => 'Hello from Gemini!']]],
            ]],
            'usageMetadata' => ['promptTokenCount' => 6, 'candidatesTokenCount' => 3],
        ], JSON_THROW_ON_ERROR)));
        $gemini = new AiClient(new GeminiProvider('AIzaTestKey', transport: $geminiTransport));
        self::assertSame('Hello from Gemini!', $gemini->ask('Hi'));
        self::assertStringContainsString(':generateContent', $geminiTransport->recorded[0]['url']);

        $ollamaTransport = new FakeTransport(new HttpResponse(200, json_encode([
            'model' => 'qwen2.5-coder:7b',
            'done_reason' => 'stop',
            'message' => ['role' => 'assistant', 'content' => 'Hello from local Ollama!'],
            'prompt_eval_count' => 12,
            'eval_count' => 7,
        ], JSON_THROW_ON_ERROR)));
        $ollama = new AiClient(new OllamaProvider(transport: $ollamaTransport));
        self::assertSame('Hello from local Ollama!', $ollama->ask('Hi'));
        self::assertStringEndsWith('/api/chat', $ollamaTransport->recorded[0]['url']);
    }

    public function testGovernedAgentRunnerEnforcesToolAllowlistAndEmitsReceipt(): void
    {
        $transport = new FakeTransport(
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [[
                    'finish_reason' => 'tool_calls',
                    'message' => [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [
                            ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'read_graph', 'arguments' => '{}']],
                            ['id' => 'c2', 'type' => 'function', 'function' => ['name' => 'delete_prod_db', 'arguments' => '{}']],
                        ],
                    ],
                ]],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 10],
            ], JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['role' => 'assistant', 'content' => 'Done safely!'],
                ]],
                'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 5],
            ], JSON_THROW_ON_ERROR)),
        );

        $client = new AiClient(new OpenAiProvider('sk-test', transport: $transport));
        $principal = new AgentPrincipal('chie', 'builder', allowedTools: ['read_graph'], maxSteps: 4, maxToolCalls: 4);
        $runner = new AgentRunner($client, $principal);

        $receipt = $runner->run(
            [Message::user('Inspect app')],
            [
                new ToolDefinition('read_graph', 'Read app graph'),
                new ToolDefinition('delete_prod_db', 'Dangerous tool'),
            ],
            static fn(ToolCall $c): string => 'graph: {"routes":2}',
        );

        self::assertSame('Done safely!', $receipt->finalText);
        self::assertSame(1, $receipt->toolCallsExecuted);
        self::assertCount(2, $receipt->toolAudit);
        self::assertTrue($receipt->toolAudit[0]['allowed']);
        self::assertFalse($receipt->toolAudit[1]['allowed']);
        self::assertSame(65, $receipt->usage->totalTokens());
    }

    public function testProviderScrubsApiKeyInHttpErrors(): void
    {
        $transport = new FakeTransport(new HttpResponse(401, 'Invalid key sk-secret-1234567890abcdef1234'));
        $client = new AiClient(new OpenAiProvider('sk-secret-1234567890abcdef1234', transport: $transport));

        try {
            $client->ask('hi');
            self::fail('Expected AiException.');
        } catch (AiException $e) {
            self::assertStringNotContainsString('sk-secret-1234567890abcdef1234', $e->getMessage());
            self::assertStringContainsString('[REDACTED]', $e->getMessage());
        }
    }

    public function testStructuredAiExtractsAndValidatesTypedDto(): void
    {
        $transport = new FakeTransport(new HttpResponse(200, json_encode([
            'model' => 'gpt-4.1-mini',
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => '{"sentiment":"positive","summary":"Developers love zero-legacy PHP 8.5 DX."}',
                ],
            ]],
        ], JSON_THROW_ON_ERROR)));

        $client = new AiClient(new OpenAiProvider('sk-test', transport: $transport));
        $result = $client->structured('Analyze this review', SentimentDto::class);

        self::assertSame('positive', $result->sentiment);
        self::assertSame('Developers love zero-legacy PHP 8.5 DX.', $result->summary);
    }
}
