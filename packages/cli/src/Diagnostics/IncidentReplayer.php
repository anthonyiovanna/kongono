<?php

declare(strict_types=1);

namespace Kongono\Cli\Diagnostics;

use Kongono\Core\Diagnostics\FileIncidentStore;
use Kongono\Core\Diagnostics\IncidentRecord;
use Kongono\Core\Diagnostics\IncidentStoreInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\App;
use Kongono\Http\Request;

/**
 * Production Incident Time-Machine Replayer (`kongo incident:replay <KGN-...>` & MCP `kongono_incident_replay`).
 *
 * Reconstructs a sanitized HTTP request from a recorded `KGN-YYYYMMDD-XXXXXXXX` incident and
 * replays it in-process against the local Kongono `App` to verify whether the fix resolved the failure.
 */
final readonly class IncidentReplayer
{
    public function resolveStore(string $appRoot, ?App $app = null): IncidentStoreInterface
    {
        if (null !== $app && $app->container()->hasBinding(IncidentStoreInterface::class)) {
            $bound = $app->container()->get(IncidentStoreInterface::class);
            if ($bound instanceof IncidentStoreInterface) {
                return $bound;
            }
        }

        return new FileIncidentStore(rtrim($appRoot, '\\/') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'incidents');
    }

    /**
     * Replay a recorded incident in-process against `$app` and report whether the error is now resolved.
     *
     * @return array{
     *     reference: string,
     *     method: string,
     *     path: string,
     *     original_status: int,
     *     replayed_status: int,
     *     resolved: bool,
     *     elapsed_ms: float,
     *     new_reference: string,
     *     response_preview: string,
     *     incident: array<string, mixed>
     * }
     */
    public function replay(App $app, IncidentRecord $incident): array
    {
        $request = $this->buildRequest($incident);
        $start = hrtime(true);
        $response = $app->handle($request);
        $elapsedMs = round((hrtime(true) - $start) / 1_000_000, 2);

        $replayedStatus = $response->getStatusCode();
        $resolved = $incident->statusCode >= 500
            ? $replayedStatus < 500
            : $replayedStatus < 400;

        $rawBody = (string) $response->getBody();
        $preview = SecretScrubber::scrub(substr($rawBody, 0, 400));

        return [
            'reference' => $incident->reference,
            'method' => $incident->method,
            'path' => $incident->path,
            'original_status' => $incident->statusCode,
            'replayed_status' => $replayedStatus,
            'resolved' => $resolved,
            'elapsed_ms' => $elapsedMs,
            'new_reference' => $response->getHeaderLine('X-Kongono-Error-Id'),
            'response_preview' => $preview,
            'incident' => $incident->toArray(),
        ];
    }

    public function buildRequest(IncidentRecord $incident): Request
    {
        $headers = $incident->headers;
        $uri = $incident->path;
        if ([] !== $incident->query) {
            $qs = http_build_query($incident->query);
            if ('' !== $qs) {
                $uri .= '?' . $qs;
            }
        }

        $isJson = str_contains(strtolower($incident->contentType), 'application/json')
            || str_contains(strtolower($headers['Accept'] ?? $headers['accept'] ?? ''), 'application/json');

        if ($isJson && [] !== $incident->body) {
            return Request::json($incident->method, $uri, $incident->body, $headers);
        }

        $parsedBody = [] !== $incident->body ? $incident->body : null;
        $rawBody = null !== $parsedBody ? http_build_query($parsedBody) : '';

        return Request::create(
            method: $incident->method,
            uri: $uri,
            headers: $headers,
            body: $rawBody,
            query: $incident->query,
            parsedBody: $parsedBody,
        );
    }
}
