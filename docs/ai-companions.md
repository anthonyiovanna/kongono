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

Optional overrides:
- `KONGONO_AI_PROVIDER`, `KONGONO_AI_MODEL`, and `KONGONO_AI_FALLBACK_PROVIDERS` (e.g. `anthropic,openai,groq,ollama` — automatic retry on HTTP 429/5xx with ordered provider failover via `FallbackProvider`).
- Per-companion routing: `KONGONO_OSHIE_PROVIDER` / `KONGONO_OSHIE_MODEL`, `KONGONO_CHIE_PROVIDER` / `KONGONO_CHIE_MODEL`, and `KONGONO_HANTEI_PROVIDER` / `KONGONO_HANTEI_MODEL`.
- **`KONGONO_AI_MAX_COST_USD`** (hard per-invocation USD budget cap enforced fail-closed; every companion prints live token, latency, and estimated USD telemetry, showing `$0.00 (local)` for Ollama).

## 1. Oshie (教え) — Learn Kongono

Oshie ships with **13 built-in lessons** (including `proofs` covering `kongo prove`, route capabilities, and Incident Time-Machine), **6 deterministically graded hands-on exercises** (`--exercises` / `--exercise <id>`), offline v1.20 RAG retrieval (`KongonoKnowledgeIndex` — works even with zero AI API key!), live token streaming (`--stream` and `/_kongono/api/oshie/stream`), `PromptGuard` isolation, automatic `AGENTS.md` project grounding, persistent local progress tracking (`--progress` under gitignored `var/kongono/companions/`), and automatic awareness of recent `/_kongono` `DevInspector` slow-request and N+1 SQL traces:

```bash
php bin/kongo oshie --lessons
php bin/kongo oshie --lesson routing
php bin/kongo oshie --exercises                                          # List 6 verifiable hands-on exercises
php bin/kongo oshie --exercise routing                                   # Grade exercise against your live project
php bin/kongo oshie --progress                                           # Completed lessons, verified exercises & recommendations
php bin/kongo oshie --interactive                                        # Streaming multi-turn REPL
php bin/kongo oshie "Why is /api/notes slow?" --stream --max-cost=0.10
```

## 2. Chie (知恵) — Build with Kongono

Chie runs under a governed `AgentPrincipal` with 6 read-only inspection tools (`list_files`, `read_file` with line ranges, `search_code`, `inspect_graph`, live `inspect_schema` metadata, and `search_kongono_docs` v1.20 RAG lookup), automatically reads your project's `AGENTS.md` conventions, supports surgical search-and-replace patches (`action: "patch"`) alongside new files, previews every change as a unified diff, runs `php -l` syntax checks before writing, and supports **multi-gate self-verifying repair loops** (`--apply --verify` running syntax → PHPStan → PHPUnit → `MigrationLinter` → `SecurityAuditor`), **per-file interactive diff approval** (`--interactive`), and **iterative session continuation** (`--continue`):

```bash
php bin/kongo chie "Add a /api/stats endpoint returning note count and queue stats"
php bin/kongo chie "Now add pagination and rate limiting" --continue                     # Iterate on previous Chie session
php bin/kongo chie "Add a /api/stats endpoint" --interactive                             # [y]es / [n]o / [a]ll / [q]uit per file
php bin/kongo chie "Add a /api/stats endpoint" --apply --verify --max-repairs=2          # Multi-gate verify + auto-repair
php bin/kongo chie "Add a /api/stats endpoint" --apply --verify --rollback-on-failure    # Atomically restore snapshots if still red
```

## 3. Hantei (判定) — Debug, Review & Test

Hantei uses the same governed read-only tools (`search_code`, `read_file`, `inspect_graph`, `inspect_schema`) to open exact stack-trace lines and inspect DB tables, scrubs credentials via `SecretScrubber`, streams live diagnoses (`--stream` and `/_kongono/api/hantei/stream`), diagnoses live `/_kongono` traces (`POST /_kongono/api/hantei/trace/{id}`), diagnoses dead-lettered background jobs in CLI (`queue-failed`) and inside `/_ops` (`POST /_ops/queues/diagnose` via `DevStudioCompanions::opsJobDiagnoser()`), watches error logs/failed queues live (`watch`), hands off fixes to Chie (`fix`), reviews git diffs for Kongono invariants (`review --format=markdown|sarif`, ready-to-copy workflow in [docs/ci-hantei-review.yml](ci-hantei-review.yml)), tracks local diagnosis history (`history`), and generates syntax-checked PHPUnit 13 tests:

```bash
php bin/kongo hantei check
php bin/kongo hantei debug "KGN-VALIDATION-422 on POST /api/notes" --stream
php bin/kongo hantei fix --apply --verify                                # Diagnose failing tests -> Chie patch -> re-verify
php bin/kongo hantei review --base=main --format=sarif                   # CI-ready Kongono invariant & security diff review
php bin/kongo hantei watch --log=var/log/app.log                         # Tail local error log or failed queue live
php bin/kongo hantei history                                             # Recent scrubbed diagnoses for this project
php bin/kongo hantei queue-failed --queue=default
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

Run built-in AI red-team, companion safety, and 6-task coding-agent RAG benchmarks in CI:

```bash
php bin/kongo eval --redteam --app=examples/starter-app
php bin/kongo eval --companions --app=examples/starter-app
php bin/kongo eval --agent-bench --app=examples/starter-app
```

## Model Context Protocol (MCP) Server for AI IDEs (26 Tools)

Connect Antigravity, Cursor, Claude Code, or Codex directly to any Kongono app over stdio or HTTP (`POST /mcp` — pre-wired in `.vscode/mcp.json` on `kongo new`):

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
- **Self-Verifying Proofs & Diagnostics:** `kongono_prove_all`, `kongono_diagnose`, `kongono_policy_check`, `kongono_incident_replay`, `kongono_perf_probe`, `kongono_prove_isolation`, `kongono_impact`, `kongono_shadow_diff`, `kongono_upgrade_plan`, `kongono_audit`, `kongono_eval`, `kongono_migrate_lint`
- **Live App, Schema, UI & Docs Introspection:** `kongono_graph`, `kongono_openapi`, `kongono_sdk_generate`, `kongono_routes`, `kongono_inspect_schema`, `kongono_search_docs`, `kongono_zokeibi_registry`, `kongono_test_route`, `kongono_deploy_preview`, `kongono_list_files`, `kongono_read_file`
- **AI Companions:** `kongono_oshie_lesson`, `kongono_hantei_diagnose`, `kongono_chie_preview`
