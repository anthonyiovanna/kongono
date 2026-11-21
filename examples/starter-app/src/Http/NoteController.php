<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Http\App;
use Kongono\Http\HttpException;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Delete;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Session\Session;
use Kongono\Jobs\Queue;
use Kongono\Jobs\Worker;
use Kongono\Storage\StorageInterface;
use Kongono\View\ViewEngine;
use StarterApp\Domain\Note;
use StarterApp\Domain\NoteStore;

final readonly class NoteController
{
    public function __construct(
        private NoteStore $store,
        private ViewEngine $views,
        private StorageInterface $storage,
        private Queue $queue,
        private Worker $worker,
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
    public function apiCreate(CreateNoteInput $input): Response
    {
        $note = $this->store->create($input->title, $input->body);
        $job = $this->queue->dispatch('note.indexed', ['note_id' => $note->id]);

        return Response::json([
            ...$note->toArray(),
            'queued_job_id' => $job->id,
        ], status: 201);
    }

    #[Delete('/api/notes/{id:\d+}', name: 'api.notes.delete')]
    public function apiDelete(int $id): Response
    {
        if (!$this->store->delete($id)) {
            throw new HttpException(404, sprintf('Note #%d not found.', $id));
        }

        return Response::noContent();
    }

    #[Post('/api/upload', name: 'api.upload')]
    public function apiUpload(Request $request): Response
    {
        $file = $request->file('document');
        if (null === $file) {
            throw new HttpException(400, 'Expected multipart file field "document".');
        }

        $safeName = basename($file->getClientFilename() ?? 'upload.bin');
        $key = 'uploads/' . $safeName;
        $bytes = (string) $file->getStream();
        $result = $this->storage->put(
            $key,
            $bytes,
            $file->getClientMediaType() ?? 'application/octet-stream',
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
    public function apiDownload(string $name): Response
    {
        $key = 'uploads/' . basename($name);
        $bytes = $this->storage->get($key);
        $meta = $this->storage->meta($key);
        if (null === $bytes || null === $meta) {
            throw new HttpException(404, sprintf('File "%s" not found.', $name));
        }

        return Response::text($bytes, 200, [
            'Content-Type' => $meta->contentType,
            'ETag' => '"' . $meta->version . '"',
        ]);
    }

    #[Post('/api/jobs/run', name: 'api.jobs.run')]
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
    public function apiGraph(): Response
    {
        return Response::json($this->app->graph()->toArray());
    }
}
