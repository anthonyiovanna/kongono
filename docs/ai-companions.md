# Built-In AI Companions: Oshie, Chie & Hantei

Kongono ships with three purpose-built AI companions powered by `kongono/ai` and grounded directly in your application's live `ApplicationGraph` and secret-scrubbed diagnostics.

## Configuring an AI Provider

Set **any one** of these environment variables (or place it in `.env`):

| Provider | Environment Variable | Default Model |
| --- | --- | --- |
| OpenAI (or compatible) | `OPENAI_API_KEY` (+ optional `OPENAI_BASE_URL`) | `gpt-4.1-mini` |
| Anthropic Claude | `ANTHROPIC_API_KEY` | `claude-sonnet-4-20250514` |
| Google Gemini | `GEMINI_API_KEY` | `gemini-2.5-flash` |
| Local Ollama (free/offline) | `OLLAMA_HOST=http://127.0.0.1:11434` | `qwen2.5-coder:7b` |

Optional overrides: `KONGONO_AI_PROVIDER` and `KONGONO_AI_MODEL`.

## 1. Oshie (教え) — Learn Kongono

```bash
php bin/kongo oshie --lessons
php bin/kongo oshie --lesson routing
php bin/kongo oshie "How do I add RateLimitMiddleware to my /api routes?"
```

## 2. Chie (知恵) — Build with Kongono

Chie runs under a governed `AgentPrincipal` with explicit read-only inspection tools (`list_files`, `read_file`, `inspect_graph`), previews every proposed file change first, and only writes inside your project root when you pass `--apply`:

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
use Kongono\Ai\AiClient;

$ai = AiClient::fromEnv();
$summary = $ai->structured('Summarize this ticket with priority and tags...', TicketSummaryDto::class);
```
