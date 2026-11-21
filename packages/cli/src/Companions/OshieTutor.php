<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Ai\AiClient;
use Kongono\Cli\Context\ProjectContext;

/**
 * Oshie (教え) — Kongono's built-in AI tutor.
 *
 * Teaches Kongono v1 concepts using built-in interactive lessons AND live AI Q&A
 * grounded directly in the developer's real ApplicationGraph and project files.
 */
final readonly class OshieTutor
{
    /** @var array<string, array{title: string, summary: string, guide: string}> */
    private const array LESSONS = [
        'routing' => [
            'title' => 'Routing & Controllers',
            'summary' => 'Fluent routes, route groups, PHP 8.5 #[Get]/#[Post] attributes, and parameter binding.',
            'guide' => <<<'MD'
# Lesson: Routing & Controllers in Kongono v1

Kongono supports both fluent routing and PHP 8.5 controller attributes:

```php
return App::create(dirname(__DIR__))
    ->routes(static function (Router $r): void {
        $r->get('/', [HomeController::class, 'index'])->name('home');
        $r->group('/api', static function (Router $api): void {
            $api->get('/notes', [NoteController::class, 'apiList']);
            $api->post('/notes', [NoteController::class, 'apiCreate']);
        });
    });
```

Or decorate controller methods with `#[Get('/notes/{id:\d+}')]` and register `$app->controller(NoteController::class)`.
Kongono injects `Request`, typed route parameters (`int $id`), Container services, and validated DTOs automatically—with zero magic globals.
MD,
        ],
        'views' => [
            'title' => 'Compiled .kgn.html Views & Components',
            'summary' => 'Auto-escaped HTML templates, layouts (@extends/@section/@yield), loops, and <x-component> tags.',
            'guide' => <<<'MD'
# Lesson: Compiled Views (.kgn.html)

Kongono compiles `.kgn.html` templates once to fast opcache-friendly PHP:

- `{{ $title }}` — automatically HTML-escaped against XSS
- `{!! $trustedHtml !!}` — explicit raw HTML opt-in
- `@extends('layouts/app')`, `@section('content') ... @endsection`, `@yield('content')`
- `@if`, `@elseif`, `@else`, `@endif`, `@foreach`, `@forelse ... @empty ... @endforelse`
- Components: `<x-card :title="$title">Slot body</x-card>` maps to `views/components/card.kgn.html`
MD,
        ],
        'validation' => [
            'title' => 'Typed PHP 8.5 DTO Validation',
            'summary' => 'Use #[Required], #[Min], #[Max], #[Email], #[In] attributes on readonly DTOs.',
            'guide' => <<<'MD'
# Lesson: Typed DTO Validation

Define a `final readonly class` DTO with attributes:

```php
final readonly class CreateNoteInput
{
    public function __construct(
        #[Required, Min(3), Max(120)]
        public string $title,
        #[Required, Min(1), Max(5000)]
        public string $body,
    ) {}
}
```

Type-hint `CreateNoteInput $input` in any route handler—Kongono validates JSON or form data automatically and returns a structured 422 response on failure.
MD,
        ],
        'database' => [
            'title' => 'Parameterized QueryBuilder & Transactions',
            'summary' => 'JOINs, WHERE/IN/NULL, GROUP BY, HAVING, RETURNING, and strict PDO transactions.',
            'guide' => <<<'MD'
# Lesson: Database & QueryBuilder

```php
$rows = $db->table('posts')
    ->select('posts.id', 'posts.title', 'users.name AS author')
    ->leftJoin('users', 'users.id', '=', 'posts.user_id')
    ->where('posts.published', '=', true)
    ->orderBy('posts.created_at', 'DESC')
    ->limit(20)
    ->get();
```

Every value is bound via PDO prepared statements; unscoped `UPDATE`/`DELETE` fails closed unless explicitly allowed.
MD,
        ],
        'ai' => [
            'title' => 'Multi-Provider AI & Governed Agents',
            'summary' => 'Unified OpenAI, Anthropic, Gemini, and local Ollama client plus capability-scoped AgentPrincipal.',
            'guide' => <<<'MD'
# Lesson: Kongono AI & Governed Agents

```php
$ai = AiClient::fromEnv(); // Auto-detects OpenAI, Anthropic, Gemini, or local Ollama
$answer = $ai->ask('Summarize this note in one sentence.');
```

For tool-using agents, wrap execution with `AgentPrincipal` (explicit `allowedTools`, `maxSteps`, `maxToolCalls`) and `AgentRunner` for auditable, secret-scrubbed receipts.
MD,
        ],
        'security' => [
            'title' => 'Principals, Gate, Middleware & SSRF-Safe Egress',
            'summary' => 'Capability-based Principal (Human/Service/Agent), Gate, Argon2id, TokenSigner, CSRF/CORS, and SafeHttpClient.',
            'guide' => <<<'MD'
# Lesson: Security & SSRF-Hardened Egress

Kongono models Humans, Services, Agents, and Systems as explicit `Principal` objects whose identity grants zero implicit authority:

```php
$gate->authorize($principal, 'notes.delete'); // throws 401 or 403 fail-closed
$safeHttp = new SafeHttpClient(allowedHosts: ['api.github.com']); // pins DNS IP & blocks private/metadata IPs
```
MD,
        ],
        'storage' => [
            'title' => 'Contained Atomic Object Storage',
            'summary' => 'Root-contained LocalStorage with SHA-256 digests and optimistic concurrency (putIfAbsent / replaceIfVersion).',
            'guide' => <<<'MD'
# Lesson: Contained Object Storage

```php
$storage = new LocalStorage(__DIR__ . '/var/storage');
$written = $storage->putIfAbsent('docs/welcome.txt', 'Hello Kongono!', 'text/plain');
$updated = $storage->replaceIfVersion('docs/welcome.txt', $written->meta->version, 'Updated!');
```
MD,
        ],
        'jobs' => [
            'title' => 'Background Jobs, Fencing Tokens & Persistent Workers',
            'summary' => 'Atomic PostgresQueueDriver/MemoryQueueDriver with lease expiry, fencing tokens, and Uncertain handling.',
            'guide' => <<<'MD'
# Lesson: Background Jobs & Workers

```php
$queue->dispatch('note.indexed', ['note_id' => 42]);
$worker->register('note.indexed', static fn(Job $job): JobOutcome => JobOutcome::Completed);
$report = $worker->run();
```

Run workers from CLI with `php bin/kongo work`.
MD,
        ],
    ];

    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are Oshie (教え), the built-in AI tutor for Kongono v1 — a modern, zero-legacy PHP 8.5+ web framework with no Symfony dependency and no global magic facades.

Kongono v1 packages (10 cohesive packages):
- Kongono\Core (Kernel, Container, Config, Diagnostics, EventDispatcher, ApplicationGraph)
- Kongono\Http (App, Router, Request, Response, MiddlewarePipeline, #[Get]/#[Post]/#[Put]/#[Patch]/#[Delete])
- Kongono\View (ViewEngine compiling `.kgn.html` templates with {{ }}, @extends/@section/@yield, @if/@foreach/@forelse, <x-component>)
- Kongono\Validation (Validator + #[Required], #[Min], #[Max], #[Email], #[Url], #[In], #[Regex] on readonly DTOs)
- Kongono\Database (PdoConnection, QueryBuilder with JOIN/WHERE/GROUP BY/HAVING/RETURNING, Migrator)
- Kongono\Security (Principal, Gate, PasswordHasher, TokenSigner, SecurityHeaders/CORS/CSRF/BearerAuth middleware, SafeHttpClient)
- Kongono\Storage (LocalStorage, MemoryStorage with atomic versioned writes & containment)
- Kongono\Jobs (Queue, Worker, PostgresQueueDriver, MemoryQueueDriver with fencing tokens & Uncertain outcomes)
- Kongono\Ai (AiClient supporting OpenAI, Anthropic, Gemini, Ollama + AgentPrincipal/AgentRunner)
- Kongono\Cli (kongo CLI: new, serve, work, routes, graph, oshie, chie, hantei)

Teaching style:
- Be concise, practical, and encouraging.
- Always show idiomatic PHP 8.5 Kongono v1 code (strict_types=1, final readonly classes, zero Symfony/Laravel APIs).
- Reference the developer's actual routes, views, and services from their ApplicationGraph when relevant.
- End with one short hands-on next step they can try in their project.
PROMPT;

    public function __construct(
        private ?AiClient $ai = null,
    ) {}

    /** @return array<string, array{title: string, summary: string}> */
    public function listLessons(): array
    {
        $out = [];
        foreach (self::LESSONS as $id => $lesson) {
            $out[$id] = ['title' => $lesson['title'], 'summary' => $lesson['summary']];
        }

        return $out;
    }

    /** Deliver a lesson (offline guide, or AI-personalized to the user's app when an AI client is available). */
    public function teachLesson(string $lessonId, ProjectContext $context): string
    {
        if (!isset(self::LESSONS[$lessonId])) {
            return sprintf(
                "Unknown lesson \"%s\". Available lessons: %s.\n",
                $lessonId,
                implode(', ', array_keys(self::LESSONS)),
            );
        }

        $lesson = self::LESSONS[$lessonId];
        if (null === $this->ai) {
            return $lesson['guide'] . "\n\n[Tip: Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST to enable interactive AI tutoring with Oshie.]\n";
        }

        $prompt = sprintf(
            "Teach the developer the \"%s\" lesson tailored to their current Kongono project.\n\nReference guide:\n%s\n\nDeveloper's project:\n%s",
            $lesson['title'],
            $lesson['guide'],
            $context->promptSummary(),
        );

        return $this->ai->ask($prompt, self::SYSTEM_PROMPT, temperature: 0.3);
    }

    /** Answer any question about Kongono using the live AI provider and the user's real project context. */
    public function ask(string $question, ProjectContext $context): string
    {
        if (null === $this->ai) {
            return "Oshie AI Q&A requires an AI provider.\n"
                . "Configure one of: OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST.\n"
                . "Meanwhile, try built-in offline lessons with: kongo oshie --lessons\n";
        }

        $prompt = sprintf(
            "Developer's project context:\n%s\n\nDeveloper's question:\n%s",
            $context->promptSummary(),
            $question,
        );

        return $this->ai->ask($prompt, self::SYSTEM_PROMPT, temperature: 0.3);
    }
}
