<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Core\Health\HealthCheck;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\App;
use Kongono\Http\HttpException;
use Kongono\Http\OpenApi\ApiDocsRenderer;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Http\Realtime\EventBus;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\CacheControl;
use Kongono\Http\Routing\Delete;
use Kongono\Http\Routing\Feature;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Idempotent;
use Kongono\Http\Routing\Middleware;
use Kongono\Http\Routing\Post;
use Kongono\Http\Session\Session;
use Kongono\Http\SseEvent;
use Kongono\Jobs\Queue;
use Kongono\Jobs\Worker;
use Kongono\Security\Attributes\Can;
use Kongono\Security\Middleware\BearerAuthMiddleware;
use Kongono\Security\Principal;
use Kongono\Security\TokenSigner;
use Kongono\Storage\StorageInterface;
use Kongono\View\ViewEngine;
use StarterApp\Domain\Note;
use StarterApp\Domain\NoteStore;

final readonly class NoteController
{
    private const int MAX_UPLOAD_BYTES = 5_242_880;

    /** @var array<string, list<string>> */
    private const array ALLOWED_UPLOAD_TYPES = [
        'txt' => ['text/plain'],
        'md' => ['text/plain', 'text/markdown'],
        'csv' => ['text/plain', 'text/csv', 'application/csv'],
        'json' => ['application/json', 'text/plain'],
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'zip' => ['application/zip'],
    ];

    public function __construct(
        private NoteStore $store,
        private ViewEngine $views,
        private StorageInterface $storage,
        private Queue $queue,
        private Worker $worker,
        private Metrics $metrics,
        private HealthCheck $health,
        private EventBus $events,
        private TokenSigner $signer,
        private App $app,
    ) {}

    #[Get('/', name: 'home')]
    public function home(Session $session): Response
    {
        $flash = $session->getFlash('notice');

        return Response::html($this->views->render('home', [
            'title' => 'Kongono v1 Starter',
            'notes' => $this->store->all(),
            'flash' => is_string($flash) ? $flash : null,
            'csrfToken' => $this->signer->generateCsrfToken($session->id()),
            'routeCount' => count($this->app->graph()->routes),
            'phpVersion' => PHP_VERSION,
        ]));
    }

    #[Post('/notes', name: 'notes.store')]
    public function storeForm(CreateNoteInput $input, Session $session): Response
    {
        $note = $this->store->create($input->title, $input->body);
        $this->queue->dispatch('note.indexed', ['note_id' => $note->id]);
        $session->flash('notice', sprintf('Saved note "%s"!', $note->title));

        return Response::redirect('/');
    }

    #[Get('/api/notes', name: 'api.notes.index')]
    public function apiList(): Response
    {
        return Response::json([
            'notes' => array_map(static fn(Note $n): array => $n->toArray(), $this->store->all()),
            'queue' => $this->queue->stats(),
        ]);
    }

    #[Get('/api/notes/{id:\d+}', name: 'api.notes.show')]
    public function apiShow(int $id): Response
    {
        $note = $this->store->find($id);
        if (null === $note) {
            throw new HttpException(404, sprintf('Note #%d not found.', $id));
        }

        return Response::json($note->toArray());
    }

    #[Post('/api/notes', name: 'api.notes.create')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('notes.write')]
    #[Idempotent(ttlSeconds: 3600)]
    public function apiCreate(CreateNoteInput $input, Principal $principal): Response
    {
        $note = $this->store->create($input->title, $input->body, $principal->id);
        $job = $this->queue->dispatch('note.indexed', ['note_id' => $note->id]);
        $this->events->publish('notes', 'note.created', $note->toArray());

        return Response::json([
            ...$note->toArray(),
            'queued_job_id' => $job->id,
        ], status: 201);
    }

    #[Get('/api/insights', name: 'api.insights')]
    #[Feature('beta_insights')]
    public function apiInsights(): Response
    {
        $notes = $this->store->all();

        return Response::json([
            'feature' => 'beta_insights',
            'total_notes' => count($notes),
            'avg_body_length' => [] === $notes
                ? 0
                : (int) round(array_sum(array_map(static fn(Note $n): int => mb_strlen($n->body), $notes)) / count($notes)),
        ]);
    }

    #[Delete('/api/notes/{id:\d+}', name: 'api.notes.delete')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('notes.write')]
    public function apiDelete(int $id, Principal $principal): Response
    {
        $existing = $this->store->find($id);
        if (null === $existing) {
            throw new HttpException(404, sprintf('Note #%d not found.', $id));
        }
        $isOpsAdmin = in_array('ops.manage', $principal->capabilities, true);
        if (!$isOpsAdmin && (null === $existing->ownerId || $existing->ownerId !== $principal->id)) {
            throw new HttpException(403, 'You do not have permission to delete this note.');
        }
        $this->store->delete($id);
        $this->events->publish('notes', 'note.deleted', ['id' => $id]);

        return Response::noContent();
    }

    #[Post('/api/upload', name: 'api.upload')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('notes.write')]
    public function apiUpload(Request $request, Principal $principal): Response
    {
        $file = $request->file('document');
        if (null === $file) {
            throw new HttpException(400, 'Expected multipart file field "document".');
        }
        if (UPLOAD_ERR_OK !== $file->getError()) {
            throw new HttpException(400, 'File upload failed or was incomplete.');
        }

        $declaredSize = $file->getSize();
        if (null !== $declaredSize && $declaredSize > self::MAX_UPLOAD_BYTES) {
            throw new HttpException(413, 'Uploaded file exceeds maximum allowed size (5 MiB).');
        }

        $rawName = basename($file->getClientFilename() ?? 'upload.txt');
        $safeName = (string) preg_replace('/[^a-zA-Z0-9._-]/', '_', $rawName);
        if ('' === $safeName || str_starts_with($safeName, '.') || str_contains($safeName, '..')) {
            $safeName = 'upload.txt';
        }
        $lowerExt = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_UPLOAD_TYPES[$lowerExt])) {
            throw new HttpException(415, 'Unsupported file extension; only allowlisted document and image formats are accepted.');
        }

        $stream = $file->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $bytes = '';
        while (!$stream->eof()) {
            $chunk = $stream->read(65536);
            if ('' === $chunk) {
                break;
            }
            $bytes .= $chunk;
            if (strlen($bytes) > self::MAX_UPLOAD_BYTES) {
                throw new HttpException(413, 'Uploaded file exceeds maximum allowed size (5 MiB).');
            }
        }

        $finfoMime = class_exists(\finfo::class) ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) : false;
        $detectedMime = is_string($finfoMime) && '' !== $finfoMime ? strtolower($finfoMime) : 'application/octet-stream';
        if (!in_array($detectedMime, self::ALLOWED_UPLOAD_TYPES[$lowerExt], true)) {
            throw new HttpException(415, 'Detected content type does not match the allowlisted file extension.');
        }

        $ownerScope = (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $principal->id);
        $key = 'uploads/' . $ownerScope . '/' . $safeName;
        $result = $this->storage->put(
            $key,
            $bytes,
            $detectedMime,
        );

        return Response::json([
            'key' => $key,
            'outcome' => $result->outcome->value,
            'filename' => $safeName,
            'media_type' => $result->meta?->contentType,
            'size' => $result->meta?->size,
            'sha256' => $result->meta?->sha256,
            'version' => $result->meta?->version,
        ], status: 201);
    }

    #[Get('/api/files/{name}', name: 'api.files.download')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('notes.read')]
    public function apiDownload(string $name, Principal $principal): Response
    {
        $safeName = (string) preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
        if ('' === $safeName || '.' === $safeName || '..' === $safeName || str_starts_with($safeName, '.') || str_contains($safeName, '..')) {
            throw new HttpException(404, 'File not found.');
        }
        $ownerScope = (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $principal->id);
        $key = 'uploads/' . $ownerScope . '/' . $safeName;
        $meta = $this->storage->meta($key);
        $stream = $this->storage->getStream($key);
        if (null === $meta || !is_resource($stream)) {
            throw new HttpException(404, sprintf('File "%s" not found.', $safeName));
        }

        return new Response(200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $safeName),
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'ETag' => '"' . $meta->version . '"',
            'Content-Length' => (string) $meta->size,
        ], new \Kongono\Http\Stream($stream));
    }

    #[Post('/api/jobs/run', name: 'api.jobs.run')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('ops.manage')]
    public function apiRunJobs(): Response
    {
        $report = $this->worker->run();

        return Response::json([
            'processed' => $report->processed,
            'completed' => $report->completed,
            'retried' => $report->retried,
            'failed' => $report->failed,
            'uncertain' => $report->uncertain,
            'stale' => $report->stale,
        ]);
    }

    #[Get('/api/graph', name: 'api.graph')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('ops.view')]
    public function apiGraph(): Response
    {
        return Response::json($this->app->graph()->toArray());
    }

    #[Get('/metrics', name: 'metrics')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('ops.view')]
    public function metrics(): Response
    {
        return Response::text($this->metrics->toPrometheus(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    #[Get('/openapi.json', name: 'openapi')]
    #[CacheControl(maxAge: 300, public: true, staleWhileRevalidate: 60)]
    public function openApi(): Response
    {
        return Response::json((new OpenApiGenerator())->generate($this->app));
    }

    #[Get('/docs', name: 'api.docs')]
    public function apiDocs(): Response
    {
        return Response::html((new ApiDocsRenderer())->render($this->app, scriptSrc: '/docs/explorer.js'));
    }

    #[Get('/docs/explorer.js', name: 'api.docs.script')]
    #[CacheControl(maxAge: 3600, public: true)]
    public function apiDocsScript(): Response
    {
        return Response::text(ApiDocsRenderer::clientScript(), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
        ]);
    }

    #[Get('/health', name: 'health')]
    public function health(): Response
    {
        $report = $this->health->run();

        return Response::json($report, status: $report['healthy'] ? 200 : 503);
    }

    #[Post('/mcp', name: 'mcp')]
    #[Middleware(BearerAuthMiddleware::class)]
    #[Can('mcp.access')]
    public function mcp(Request $request): Response
    {
        $server = KongonoMcpFactory::forApp($this->app->basePath(), $this->app);
        $reply = $server->handleJson((string) $request->getBody());
        if (null === $reply) {
            return Response::noContent();
        }

        return Response::text($reply, 200, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    #[Get('/api/events', name: 'api.events')]
    public function events(Request $request): Response
    {
        $noteCount = count($this->store->all());
        $lastId = $request->getHeaderLine('Last-Event-ID');
        $replayed = $this->events->since('notes', '' !== $lastId ? $lastId : null);

        return Response::sse(static function () use ($noteCount, $replayed): iterable {
            yield SseEvent::json(['status' => 'connected', 'notes' => $noteCount], event: 'ready', id: '0');
            foreach ($replayed as $ev) {
                yield $ev;
            }
            yield SseEvent::json(['framework' => 'Kongono'], event: 'info');
        });
    }
}
