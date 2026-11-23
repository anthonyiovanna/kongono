---
name: kongono-ai-and-rag
description: >-
  Workflow for integrating LLMs (OpenAI, Anthropic Claude, Google Gemini, Groq,
  Ollama), structured DTO extraction, PromptGuard canary defense, PostgreSQL FTS
  + pgvector Hybrid RRF search, governed AI agents, and AI red-team evals in
  Kongono Framework (PHP 8.5+). Use when building AI features, RAG pipelines,
  chat streaming, or agent tool loops in Kongono.
---

# Kongono AI, Hybrid RAG & Agent Governance Workflow

Build production AI features using `Kongono\Ai` with zero vendor lock-in, automatic secret scrubbing, and prompt-injection defense.

## Step 1: Configure `AiClient` (5 Native Providers)

Use `Kongono\Ai\AiClient::fromEnv()` so the app switches cleanly between providers via environment variables (optional overrides: `KONGONO_AI_PROVIDER`, `KONGONO_AI_MODEL`):
- `OPENAI_API_KEY` (+ optional `OPENAI_BASE_URL`) -> `OpenAiProvider` (`gpt-4.1-mini`)
- `ANTHROPIC_API_KEY` -> `AnthropicProvider` (`claude-sonnet-4-20250514`)
- `GEMINI_API_KEY` -> `GeminiProvider` (`gemini-2.5-flash`)
- `GROQ_API_KEY` (+ optional `GROQ_BASE_URL`) -> `GroqProvider` (`llama-3.3-70b-versatile`)
- `OLLAMA_HOST` -> `OllamaProvider` (`qwen2.5-coder:7b`)

Never instantiate raw cURL calls to LLM endpoints — `CurlTransport` enforces HTTPS, SSRF egress protection, bounded buffers, and `SecretScrubber` error redaction.

## Step 2: Pick the Right AI Primitive

- **Live Streaming:** `$ai->chatStream(new ChatRequest([...]), fn(string $delta): void => ...)` (pair with `Response::sse(...)` or Live 2.5 `kgn-stream`).
- **Typed JSON Extraction:** `$dto = $ai->structured($text, MyReadonlyDto::class)` — automatically generates schema instructions, decodes JSON, and validates through `Kongono\Validation\Validator`.
- **Untrusted User/Web Input Defense:** Always wrap external text in `Kongono\Ai\Security\UntrustedInput` and guard prompts/outputs with `PromptGuard` (structural `<untrusted_data>` isolation + cryptographic Canary Token fail-closed leak blocking).

## Step 3: Build High-Precision Hybrid RAG

1. Combine exact keyword search (`Kongono\Database\Search\PostgresFullTextSearch` — `tsvector` + `websearch_to_tsquery` + `ts_rank_cd` with XSS-safe plain-text `[[`/`]]` highlights) with semantic vector search (`Kongono\Ai\Embedding\PgVectorStore` with HNSW/GIN indexes) via `Kongono\Ai\Embedding\HybridSearch`:
   ```php
   $hybrid = new HybridSearch($fts, $vectors, rrfK: 60, keywordWeight: 1.0, vectorWeight: 1.0);
   $hits = $hybrid->search(queryText: $q, queryVector: $embedding, limit: 5, metadataFilter: ['tenant_id' => $tid]);
   ```
2. For 5-line ingestion + grounded `[1]` citation answers, use `Kongono\Ai\Embedding\KnowledgeBase` (auto-chunks and scrubs secrets before embedding).
3. In tests, inject `MemorySearchEngine` + `MemoryVectorStore` + a fake `HttpTransportInterface` (zero network calls).

## Step 4: Govern Tool-Using Agents (`AgentRunner`)

- Create an explicit `Kongono\Ai\Agent\AgentPrincipal` with minimal `allowedTools`, `maxSteps`, `maxToolCalls`, and token/USD cost budgets.
- Mark destructive or sensitive tool handlers with `#[RequiresApproval]` (`Kongono\Ai\Agent\RequiresApproval`) for human-in-the-loop approval.
- Keep `KongonoMcpFactory` read-only and never expose `.env` or secret files to agents.

## Verification Checklist

```bash
php vendor/bin/kongo eval --redteam
php vendor/bin/kongo audit --attack --strict
php vendor/bin/kongo test
```
Confirm **12/12** red-team vectors pass and unit tests cover fake-transport chat/streaming/structured DTO parsing and `HybridSearch` ranking.
