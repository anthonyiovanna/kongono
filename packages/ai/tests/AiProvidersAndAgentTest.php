<?php

declare(strict_types=1);

namespace Kongono\Ai\Tests;

use Closure;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Embedding\KnowledgeBase;
use Kongono\Ai\Embedding\MemoryVectorStore;
use Kongono\Ai\Embedding\PgVectorStore;
use Kongono\Ai\Embedding\TextChunker;
use Kongono\Ai\Embedding\VectorDocument;
use Kongono\Ai\Mcp\McpServer;
use Kongono\Ai\Message;
use Kongono\Ai\Provider\AnthropicProvider;
use Kongono\Ai\Provider\GeminiProvider;
use Kongono\Ai\Provider\OllamaProvider;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Ai\Transport\StreamingHttpTransportInterface;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
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

    public function testOpenAiProviderStreamsSseTokenDeltas(): void
    {
        $streamingTransport = new class implements StreamingHttpTransportInterface {
            public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
            {
                return new HttpResponse(200, '{}');
            }

            public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
            {
                $onChunk("data: {\"model\":\"gpt-4.1-mini\",\"choices\":[{\"delta\":{\"content\":\"Hello \"}}]}\n\n");
                $onChunk("data: {\"model\":\"gpt-4.1-mini\",\"choices\":[{\"delta\":{\"content\":\"Kongono!\"},\"finish_reason\":\"stop\"}]}\n\ndata: [DONE]\n\n");

                return 200;
            }
        };

        $client = new AiClient(new OpenAiProvider('sk-test', transport: $streamingTransport));
        $deltas = [];
        $res = $client->chatStream(
            new ChatRequest([Message::user('Greet')]),
            static function (string $delta) use (&$deltas): void {
                $deltas[] = $delta;
            },
        );

        self::assertSame(['Hello ', 'Kongono!'], $deltas);
        self::assertSame('Hello Kongono!', $res->text);
        self::assertSame('stop', $res->finishReason);

        // Verify Ollama native NDJSON streaming as well
        $ollamaStream = new class implements StreamingHttpTransportInterface {
            public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
            {
                return new HttpResponse(200, '{}');
            }

            public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
            {
                $onChunk("{\"model\":\"qwen2.5-coder:7b\",\"message\":{\"content\":\"Local \"}}\n");
                $onChunk("{\"model\":\"qwen2.5-coder:7b\",\"message\":{\"content\":\"AI!\"},\"done\":true,\"done_reason\":\"stop\"}\n");

                return 200;
            }
        };

        $ollamaClient = new AiClient(new OllamaProvider(transport: $ollamaStream));
        $oDeltas = [];
        $oRes = $ollamaClient->chatStream(new ChatRequest([Message::user('Hi')]), static function (string $d) use (&$oDeltas): void {
            $oDeltas[] = $d;
        });
        self::assertSame(['Local ', 'AI!'], $oDeltas);
        self::assertSame('Local AI!', $oRes->text);
    }

    public function testAnthropicAndGeminiStreamSseTokenDeltas(): void
    {
        $anthropicStream = new class implements StreamingHttpTransportInterface {
            public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
            {
                return new HttpResponse(200, '{}');
            }

            public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
            {
                $onChunk("event: content_block_delta\ndata: {\"type\":\"content_block_delta\",\"delta\":{\"type\":\"text_delta\",\"text\":\"Claude \"}}\n\n");
                $onChunk("event: content_block_delta\ndata: {\"type\":\"content_block_delta\",\"delta\":{\"type\":\"text_delta\",\"text\":\"streams!\"}}\n\n");
                $onChunk("event: message_delta\ndata: {\"type\":\"message_delta\",\"delta\":{\"stop_reason\":\"end_turn\"}}\n\n");

                return 200;
            }
        };

        $anthropicClient = new AiClient(new AnthropicProvider('sk-ant-test', transport: $anthropicStream));
        $aDeltas = [];
        $aRes = $anthropicClient->chatStream(
            new ChatRequest([Message::system('Be concise'), Message::user('Hi')]),
            static function (string $d) use (&$aDeltas): void {
                $aDeltas[] = $d;
            },
        );
        self::assertSame(['Claude ', 'streams!'], $aDeltas);
        self::assertSame('Claude streams!', $aRes->text);
        self::assertSame('end_turn', $aRes->finishReason);

        $geminiStream = new class implements StreamingHttpTransportInterface {
            public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
            {
                return new HttpResponse(200, '{}');
            }

            public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
            {
                $onChunk("data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"Gemini \"}]}}]}\n\n");
                $onChunk("data: {\"candidates\":[{\"finishReason\":\"STOP\",\"content\":{\"parts\":[{\"text\":\"streams!\"}]}}]}\n\n");

                return 200;
            }
        };

        $geminiClient = new AiClient(new GeminiProvider('AIzaTestKey', transport: $geminiStream));
        $gDeltas = [];
        $gRes = $geminiClient->chatStream(
            new ChatRequest([Message::system('Rule'), Message::user('Hi')]),
            static function (string $d) use (&$gDeltas): void {
                $gDeltas[] = $d;
            },
        );
        self::assertSame(['Gemini ', 'streams!'], $gDeltas);
        self::assertSame('Gemini streams!', $gRes->text);
        self::assertSame('STOP', $gRes->finishReason);
    }

    public function testMcpServerJsonRpcGovernanceSecretScrubbingAndStdio(): void
    {
        $principal = new AgentPrincipal('ide-agent', 'mcp-client', allowedTools: ['safe_echo']);
        $server = (new McpServer('test-mcp', '1.0.0', $principal))
            ->registerTool(
                new ToolDefinition('safe_echo', 'Echoes input safely'),
                static fn(array $args): string => 'Echo: ' . (is_string($args['msg'] ?? null) ? $args['msg'] : '') . ' sk-proj-1234567890abcdef1234',
            )
            ->registerTool(
                new ToolDefinition('blocked_rm', 'Should be filtered out by AgentPrincipal'),
                static fn(): string => 'never runs',
            );

        // initialize (also verifies UTF-8 BOM tolerance for Windows stdio hosts)
        $initRaw = $server->handleJson("\xEF\xBB\xBF" . '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}');
        self::assertNotNull($initRaw);
        $init = json_decode($initRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($init);
        self::assertSame(McpServer::PROTOCOL_VERSION, $init['result']['protocolVersion']);

        // notification returns null
        self::assertNull($server->handleJson('{"jsonrpc":"2.0","method":"notifications/initialized"}'));

        // tools/list only exposes allowlisted tool
        $listRaw = $server->handleJson('{"jsonrpc":"2.0","id":2,"method":"tools/list"}');
        self::assertNotNull($listRaw);
        $list = json_decode($listRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        self::assertCount(1, $list['result']['tools']);
        self::assertSame('safe_echo', $list['result']['tools'][0]['name']);

        // tools/call executes & scrubs secrets
        $callRaw = $server->handleJson('{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"safe_echo","arguments":{"msg":"hello"}}}');
        self::assertNotNull($callRaw);
        $call = json_decode($callRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($call);
        self::assertFalse($call['result']['isError']);
        self::assertStringContainsString('Echo: hello', $call['result']['content'][0]['text']);
        self::assertStringNotContainsString('sk-proj-1234567890abcdef1234', $call['result']['content'][0]['text']);
        self::assertStringContainsString('[REDACTED]', $call['result']['content'][0]['text']);

        // Blocked tool returns -32602
        $blockedRaw = $server->handleJson('{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"blocked_rm"}}');
        self::assertNotNull($blockedRaw);
        $blocked = json_decode($blockedRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($blocked);
        self::assertSame(-32602, $blocked['error']['code']);

        // Unknown method returns -32601 & invalid JSON returns -32700
        $unknown = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":5,"method":"nope"}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($unknown);
        self::assertSame(-32601, $unknown['error']['code']);

        $parseErr = json_decode((string) $server->handleJson('{bad json'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($parseErr);
        self::assertSame(-32700, $parseErr['error']['code']);

        // Stdio framing loop
        $in = fopen('php://memory', 'r+');
        $out = fopen('php://memory', 'r+');
        self::assertIsResource($in);
        self::assertIsResource($out);
        fwrite($in, "{\"jsonrpc\":\"2.0\",\"id\":\"p1\",\"method\":\"ping\"}\n");
        rewind($in);
        $server->serveStdio($in, $out);
        rewind($out);
        $stdioOut = stream_get_contents($out);
        fclose($in);
        fclose($out);
        self::assertIsString($stdioOut);
        self::assertStringContainsString('"id":"p1"', $stdioOut);
    }

    public function testEmbeddingsProvidersChunkerVectorStoresAndRagKnowledgeBase(): void
    {
        // 1. OpenAI, Gemini & Ollama native embedding wire protocols
        $openai = new OpenAiProvider('sk-test', transport: new FakeTransport(
            new HttpResponse(200, json_encode(['data' => [['embedding' => [1.0, 0.0]], ['embedding' => [0.0, 1.0]]]], JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode(['data' => [['embedding' => [0.95, 0.05]]]], JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => ['content' => 'Kongono runs on PHP 8.5 [1].']]],
            ], JSON_THROW_ON_ERROR)),
        ));

        $gemini = new GeminiProvider('AIzaTest', transport: new FakeTransport(
            new HttpResponse(200, json_encode(['embeddings' => [['values' => [0.5, 0.5]]]], JSON_THROW_ON_ERROR)),
        ));
        self::assertSame([[0.5, 0.5]], $gemini->embed(['hello']));

        $ollama = new OllamaProvider(transport: new FakeTransport(
            new HttpResponse(200, json_encode(['embeddings' => [[0.25, 0.75]]], JSON_THROW_ON_ERROR)),
        ));
        self::assertSame([[0.25, 0.75]], $ollama->embed(['local']));

        // 2. TextChunker overlapping split
        $chunker = new TextChunker(chunkSize: 30, overlap: 10);
        $chunks = $chunker->split('Kongono is a modern zero-legacy PHP 8.5 framework with built-in AI companions.');
        self::assertGreaterThan(1, count($chunks));

        // 3. MemoryVectorStore + KnowledgeBase RAG with citations & secret scrubbing
        $store = new MemoryVectorStore();
        $kb = new KnowledgeBase($openai, $store, new TextChunker(chunkSize: 35, overlap: 0));
        $indexed = $kb->ingest('doc-1', 'Kongono runs on PHP 8.5 with zero Symfony and sk-proj-1234567890abcdef1234', ['topic' => 'core']);
        self::assertSame(2, $indexed);

        $rag = $kb->ask('What PHP version does Kongono use?', new AiClient($openai), topK: 1, metadataFilter: ['topic' => 'core']);
        self::assertStringContainsString('PHP 8.5', $rag['answer']);
        self::assertCount(1, $rag['sources']);
        self::assertStringNotContainsString('sk-proj-1234567890abcdef1234', $rag['sources'][0]['excerpt']);

        // 4. PgVectorStore SQL verification
        $sqlLog = [];
        $fakePg = new class ($sqlLog) implements ConnectionInterface {
            /** @param list<string> $sqlLog */
            public function __construct(public array &$sqlLog) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->sqlLog[] = $sql;

                return [[
                    'id' => 'pg-1',
                    'content' => 'pgvector doc',
                    'embedding' => '[1,0]',
                    'metadata' => '{"env":"prod"}',
                    'score' => 0.99,
                ]];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->sqlLog[] = $sql;

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };

        $pgStore = new PgVectorStore($fakePg);
        $pgStore->ensureTable(2);
        $pgStore->upsert(new VectorDocument('pg-1', 'pgvector doc', [1.0, 0.0], ['env' => 'prod']));
        $pgHits = $pgStore->search([1.0, 0.0], limit: 3, metadataFilter: ['env' => 'prod']);
        self::assertCount(1, $pgHits);
        self::assertSame('pg-1', $pgHits[0]->document->id);
        self::assertSame(0.99, $pgHits[0]->score);
        self::assertStringContainsString('CREATE EXTENSION IF NOT EXISTS vector', $sqlLog[0]);
        self::assertStringContainsString('ON CONFLICT (id) DO UPDATE', $sqlLog[2]);
        self::assertStringContainsString('metadata @> ?::jsonb', $sqlLog[3]);
    }

    public function testAgentRunnerPausesForHumanApprovalAndEnforcesTokenBudget(): void
    {
        $transport = new FakeTransport(new HttpResponse(200, json_encode([
            'model' => 'gpt-4.1-mini',
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => '',
                    'tool_calls' => [[
                        'id' => 'call_refund_1',
                        'type' => 'function',
                        'function' => [
                            'name' => 'billing_refund',
                            'arguments' => '{"invoice_id":42,"amount_cents":50000}',
                        ],
                    ]],
                ],
            ]],
            'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 20],
        ], JSON_THROW_ON_ERROR)));

        $client = new AiClient(new OpenAiProvider('sk-test', transport: $transport));
        $principal = new AgentPrincipal(
            id: 'billing-agent',
            role: 'support',
            allowedTools: ['billing_refund'],
            approvalRequiredTools: ['billing_refund'],
            maxTokens: 1000,
            maxCostUsd: 0.25,
        );

        $executed = false;
        $runner = new AgentRunner($client, $principal);
        $receipt = $runner->run(
            [Message::user('Refund invoice 42')],
            [new ToolDefinition('billing_refund', 'Refund an invoice', ['type' => 'object'])],
            static function () use (&$executed): string {
                $executed = true;

                return 'refunded';
            },
        );

        self::assertFalse($executed);
        self::assertTrue($receipt->isAwaitingApproval());
        self::assertSame('awaiting_approval', $receipt->status);
        self::assertNotNull($receipt->pendingApproval);
        self::assertSame('billing_refund', $receipt->pendingApproval->name);

        // Enforce token budget fail-closed
        $tightPrincipal = new AgentPrincipal(id: 'tight-agent', role: 'support', allowedTools: ['billing_refund'], maxTokens: 10);
        $tightTransport = new FakeTransport(new HttpResponse(200, json_encode([
            'model' => 'gpt-4.1-mini',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Done']]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 20],
        ], JSON_THROW_ON_ERROR)));

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('exceeded its token budget');
        (new AgentRunner(new AiClient(new OpenAiProvider('sk-test', transport: $tightTransport)), $tightPrincipal))
            ->run([Message::user('Hi')], [], static fn(): string => '');
    }
}
