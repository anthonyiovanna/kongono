# Built-In AI Companions: Oshie, Chie & Hantei

Kongono ships with three purpose-built AI companions powered by `kongono/ai` and grounded directly in your application's live `ApplicationGraph` and secret-scrubbed diagnostics.

## Configuring an AI Provider

Set **any one** of these environment variables (or place it in `.env`):

| Provider | Environment Variable | Default Model |
| --- | --- | --- |
| OpenAI (or compatible) | `OPENAI_API_KEY` (+ optional `OPENAI_BASE_URL`) | `gpt-4.1-mini` |
| Anthropic Claude | `ANTHROPIC_API_KEY` | `claude-sonnet-4-20250514` |
| Google Gemini | `GEMINI_API_KEY` | `gemini-2.5-flash` |
| Groq (OpenAI-compatible LPU) | `GROQ_API_KEY` (+ optional `GROQ_BASE_URL`) | `llama-3.3-70b-versatile` |
| Local Ollama (free/offline) | `OLLAMA_HOST=http://127.0.0.1:11434` | `qwen2.5-coder:7b` |

Optional overrides: `KONGONO_AI_PROVIDER` and `KONGONO_AI_MODEL`.

## 1. Oshie (教え) — Learn Kongono

```bash
php bin/kongo oshie --lessons
php bin/kongo oshie --lesson routing
php bin/kongo oshie --interactive
php bin/kongo oshie "How do I add RateLimitMiddleware to my /api routes?"
```

## 2. Chie (知恵) — Build with Kongono

Chie runs under a governed `AgentPrincipal` with explicit read-only inspection tools (`list_files`, `read_file`, `inspect_graph`), previews every proposed file change first as a unified diff, and only writes inside your project root when you pass `--apply`:

```bash
php bin/kongo chie "Add a /api/stats endpoint returning note count and queue stats"
php bin/kongo chie "Add a /api/stats endpoint returning note count and queue stats" --apply
```

## 3. Hantei (判定) — Debug & Test

Hantei runs PHPUnit, scrubs credentials/secrets via `SecretScrubber`, diagnoses failures against your live app, and generates PHPUnit 13 tests:

```bash
php bin/kongo hantei check
php bin/kongo hantei debug "KGN-VALIDATION-422 on POST /api/notes"
php bin/kongo hantei generate-tests src/Http/NoteController.php --apply
```

## Using `kongono/ai` Inside Your Own App

```php
use Kongono\Ai\{AiClient, ChatRequest, Message};
use Kongono\Ai\Embedding\{HybridSearch, KnowledgeBase, PgVectorStore};
use Kongono\Database\Search\PostgresFullTextSearch;

$ai = AiClient::fromEnv();
$summary = $ai->structured('Summarize this ticket with priority and tags...', TicketSummaryDto::class);

// Live streaming supported across OpenAI, Anthropic, Gemini, and Ollama
$ai->chatStream(
    new ChatRequest([Message::user('Explain Kongono OperationScope')]),
    static function (string $delta): void {
        echo $delta;
    },
);

// Reciprocal Rank Fusion (RRF) combining PostgreSQL FTS + pgvector HNSW embeddings
$hybrid = new HybridSearch(
    new PostgresFullTextSearch($db, table: 'docs', textColumns: ['title', 'body']),
    new PgVectorStore($db),
);
$hits = $hybrid->search(queryText: 'FrankenPHP resetScope', queryVector: $queryVector, limit: 5);
```

Run built-in AI red-team and structured-output evals in CI:

```bash
php bin/kongo eval --redteam --app=examples/starter-app
```

## Model Context Protocol (MCP) Server for AI IDEs

Connect Antigravity, Cursor, Claude Code, or Codex directly to any Kongono app over stdio or HTTP (`POST /mcp`):

```bash
php bin/kongo mcp --app=examples/starter-app
```

Example MCP client config:

```json
{
  "mcpServers": {
    "kongono": {
      "command": "php",
      "args": ["bin/kongo", "mcp", "--app=examples/starter-app"]
    }
  }
}
```

Exposed read-only, `AgentPrincipal`-governed tools (all output scrubbed by `SecretScrubber`, `.env` reads explicitly blocked):
- `kongono_graph` — Live `ApplicationGraph` JSON
- `kongono_openapi` — Live OpenAPI 3.1.0 specification
- `kongono_sdk_generate` — Generate typed TypeScript, Swift 6, Kotlin, or Dart client SDKs
- `kongono_routes` — Full HTTP route table
- `kongono_audit` — Graph-driven security audit & red-team diagnostics
- `kongono_eval` — AI red-team & structured DTO eval runner
- `kongono_migrate_lint` — Zero-downtime database migration linter
- `kongono_test_route` — In-process route smoke test runner
- `kongono_deploy_preview` — Non-root FrankenPHP/Caddy deployment blueprint preview
- `kongono_list_files` — Contained project file listing
- `kongono_read_file` — Secret-scrubbed source file reader
- `kongono_oshie_lesson` — Built-in Kongono Oshie guides
