# Kongono AI (`kongono/ai`)

> **Read-only subtree split** of [`kongono/framework`](https://github.com/kongono/framework). Open issues and pull requests in the main monorepo.

Standalone, zero-Symfony PHP 8.5.13+ multi-provider AI SDK, prompt-injection defense, RAG engine, eval harness, and Model Context Protocol (MCP) server usable in **any** PHP application:

- **5 Native Providers + Failover & Live Streaming:** OpenAI, Anthropic Claude, Google Gemini, Groq, and local Ollama (`AiClient::fromEnv()`, `FallbackProvider`) over `ext-curl` with zero heavy HTTP clients.
- **Typed Structured Outputs:** Extract validated PHP 8.5 readonly DTOs directly via `$ai->structured(MyDto::class, $prompt)`.
- **Prompt Injection Defense & Red-Team Evals:** `PromptGuard` + `UntrustedInput` structural isolation, canary exfiltration blocking, and `EvalRunner`.
- **Governed Agents, RAG & MCP:** `AgentPrincipal` / `AgentRunner` with `#[RequiresApproval]` and hard USD/token caps, 5-line `KnowledgeBase` RAG (`MemoryVectorStore` or optional `PgVectorStore` + `HybridSearch`), and native JSON-RPC 2.0 `McpServer`.

## Installation

```bash
composer require kongono/ai
```

## Example

```php
<?php

declare(strict_types=1);

use Kongono\Ai\{AiClient, Message};
use Kongono\Ai\Security\{PromptGuard, UntrustedInput};

$ai = AiClient::fromEnv(); // OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST
$guard = new PromptGuard();
$protected = $guard->buildProtectedSystemPrompt('Summarize the user ticket concisely.');

$reply = $ai->chat([
    Message::system($protected['prompt']),
    Message::user($guard->wrapUntrusted(new UntrustedInput($rawTicket, 'ticket'))),
]);
```

## Documentation & License

- Full documentation: https://kongono.com/docs.html#ai-rag
- Monorepo & issues: https://github.com/kongono/framework
- License: MIT
