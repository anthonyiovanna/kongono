<?php

declare(strict_types=1);

namespace Kongono\Http\Middleware;

use Kongono\Http\Response;
use Kongono\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Contained static asset middleware serving CSS, JS, fonts, and images from a public directory
 * with ETag, Cache-Control, and 304 Not Modified support. Never executes or serves PHP files.
 */
final readonly class StaticFilesMiddleware implements MiddlewareInterface
{
    private const array MIME_TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=utf-8',
    ];

    private string $root;

    public function __construct(
        string $publicDirectory,
        private string $urlPrefix = '/assets/',
        private string $cacheControl = 'public, max-age=3600',
    ) {
        $resolved = realpath($publicDirectory);
        $this->root = false !== $resolved ? $resolved : $publicDirectory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if ('GET' !== $method && 'HEAD' !== $method) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        if (!str_starts_with($path, $this->urlPrefix)) {
            return $handler->handle($request);
        }

        $relative = ltrim(substr($path, strlen($this->urlPrefix)), '/');
        $decodedRelative = rawurldecode($relative);
        if (
            '' === $relative
            || str_contains($relative, '..')
            || str_contains($decodedRelative, '..')
            || str_contains($relative, '\\')
            || str_contains($decodedRelative, '\\')
            || str_contains($relative, "\0")
            || str_contains($decodedRelative, "\0")
        ) {
            return $handler->handle($request);
        }

        // Never expose dotfiles/hidden directories (e.g. .env, .git, .htaccess)
        $rawSegments = explode('/', $decodedRelative);
        foreach ($rawSegments as $segment) {
            if ('' === $segment || str_starts_with($segment, '.')) {
                return $handler->handle($request);
            }
        }

        // Reject any intermediate or leaf symlink before resolving
        $walk = $this->root;
        foreach ($rawSegments as $segment) {
            $walk .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($walk)) {
                return $handler->handle($request);
            }
        }

        $candidate = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $decodedRelative);
        $real = realpath($candidate);
        $prefix = rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (false === $real || !is_file($real) || !str_starts_with($real, $prefix)) {
            return $handler->handle($request);
        }

        // Re-verify dotfile exclusion on the canonical post-realpath relative path (defeats Windows 8.3 short names)
        $resolvedRelative = str_replace('\\', '/', substr($real, strlen($prefix)));
        foreach (explode('/', $resolvedRelative) as $segment) {
            if ('' === $segment || str_starts_with($segment, '.')) {
                return $handler->handle($request);
            }
        }

        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        if (!isset(self::MIME_TYPES[$ext])) {
            return $handler->handle($request);
        }

        $digest = @hash_file('xxh128', $real);
        $size = @filesize($real);
        if (false === $digest || false === $size) {
            return $handler->handle($request);
        }

        $etag = '"' . $digest . '"';
        $headers = [
            'Content-Type' => self::MIME_TYPES[$ext],
            'ETag' => $etag,
            'Cache-Control' => $this->cacheControl,
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ('svg' === $ext) {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; sandbox";
        }

        $ifNoneMatch = trim($request->getHeaderLine('If-None-Match'));
        if ('' !== $ifNoneMatch && self::matchesIfNoneMatch($ifNoneMatch, $etag)) {
            return new Response(304, $headers);
        }

        $headers['Content-Length'] = (string) $size;
        if ('HEAD' === $method) {
            return new Response(200, $headers, Stream::fromString(''));
        }

        $fp = @fopen($real, 'rb');
        if (false === $fp) {
            return $handler->handle($request);
        }

        return new Response(200, $headers, new Stream($fp));
    }

    private static function matchesIfNoneMatch(string $header, string $etag): bool
    {
        if ('*' === $header) {
            return true;
        }
        $target = str_starts_with($etag, 'W/') ? substr($etag, 2) : $etag;
        foreach (explode(',', $header) as $candidate) {
            $trimmed = trim($candidate);
            $normalized = str_starts_with($trimmed, 'W/') ? substr($trimmed, 2) : $trimmed;
            if ($normalized === $target) {
                return true;
            }
        }

        return false;
    }
}
