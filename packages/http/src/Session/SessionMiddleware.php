<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

use Kongono\Http\Cookie;
use Kongono\Http\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Loads, attaches, persists, and emits secure cookies for Kongono Sessions.
 * Completely persistent-runtime safe (never uses PHP session_start() or $_SESSION).
 */
final readonly class SessionMiddleware implements MiddlewareInterface
{
    /** Internal in-process request attribute set by read-only proof tools to avoid persisting probe sessions. */
    public const string ATTR_SKIP_PERSISTENCE = 'kongono.session.skip_persistence';

    public function __construct(
        private SessionStoreInterface $store,
        private string $cookieName = 'kongono_session',
        private int $ttlSeconds = 7200,
        private bool $secure = false,
        private string $sameSite = 'Lax',
        private int $absoluteMaxLifetimeSeconds = 0,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();
        $incomingId = is_string($cookies[$this->cookieName] ?? null) ? $cookies[$this->cookieName] : '';
        $isNew = false;
        $now = time();
        $originalCreatedAt = null;

        if ('' !== $incomingId && 1 === preg_match('/\A[a-f0-9]{32,128}\z/', $incomingId)) {
            $data = $this->store->read($incomingId);
            if (null !== $data) {
                $createdAt = is_int($data['_created_at'] ?? null) ? $data['_created_at'] : null;
                if ($this->absoluteMaxLifetimeSeconds > 0 && null !== $createdAt && ($now - $createdAt) >= $this->absoluteMaxLifetimeSeconds) {
                    $this->store->destroy($incomingId);
                    $session = new Session(bin2hex(random_bytes(24)), persisted: false);
                    $isNew = true;
                } else {
                    $originalCreatedAt = $createdAt;
                    $session = new Session($incomingId, $data, persisted: true);
                }
            } else {
                $session = new Session(bin2hex(random_bytes(24)), persisted: false);
                $isNew = true;
            }
        } else {
            $session = new Session(bin2hex(random_bytes(24)), persisted: false);
            $isNew = true;
        }

        $isHttps = 'https' === strtolower($request->getUri()->getScheme());
        $skipPersistence = true === $request->getAttribute(self::ATTR_SKIP_PERSISTENCE);

        try {
            $response = $handler->handle($request->withAttribute(Session::class, $session));
        } catch (Throwable $e) {
            $cookie = $this->finalizeSession($session, $isNew, $now, $originalCreatedAt, $isHttps, persistMutations: !$skipPersistence && $e instanceof HttpException);
            if ($e instanceof HttpException && null !== $cookie) {
                $enriched = $e->withAddedHeader('Set-Cookie', $cookie->toHeaderValue());
                $cc = strtolower($enriched->headerLine('Cache-Control'));
                if ('' === $cc || (!str_contains($cc, 'private') && !str_contains($cc, 'no-store'))) {
                    $enriched = $enriched->withHeader('Cache-Control', 'private, no-cache="Set-Cookie"');
                }

                throw $enriched;
            }

            throw $e;
        }

        $cookie = $this->finalizeSession($session, $isNew, $now, $originalCreatedAt, $isHttps, persistMutations: !$skipPersistence);
        if (null !== $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', $cookie->toHeaderValue());
            $response = $this->protectSetCookieCaching($response);
        }

        return $response;
    }

    private function finalizeSession(
        Session $session,
        bool $isNew,
        int $now,
        ?int $originalCreatedAt,
        bool $isHttps,
        bool $persistMutations,
    ): ?Cookie {
        foreach (array_unique($session->previousIds()) as $oldId) {
            $this->store->destroy($oldId);
        }

        if ($session->isInvalidated()) {
            $this->store->destroy($session->id());

            return new Cookie(
                name: $this->cookieName,
                value: '',
                maxAge: 0,
                path: '/',
                secure: $this->secure || $isHttps,
                httpOnly: true,
                sameSite: $this->sameSite,
            );
        }

        if (!$persistMutations) {
            return null;
        }

        // Lazy persistence: only store & emit Set-Cookie when the session was written, rotated, or its ID was accessed (e.g. CSRF)
        $shouldPersist = $session->isDirty() || $session->wasRegenerated() || ($isNew && $session->wasIdAccessed());
        $persisted = false;
        $effectiveTtl = max(1, $this->ttlSeconds);
        if ($shouldPersist) {
            // Prevent concurrent-request resurrection of a session destroyed/rotated while this request was in flight
            $canPersist = $isNew || $session->wasRegenerated() || null !== $this->store->read($session->id());
            if ($canPersist) {
                $payload = $session->exportPayload();
                if ($this->absoluteMaxLifetimeSeconds > 0) {
                    // Keep _created_at immutable across an existing session's lifetime so app code cannot bypass absolute expiry
                    $anchor = ($isNew || null === $originalCreatedAt) ? $now : $originalCreatedAt;
                    $payload['_created_at'] = $anchor;
                    $remainingAbsolute = $this->absoluteMaxLifetimeSeconds - max(0, $now - $anchor);
                    $effectiveTtl = max(1, min($effectiveTtl, max(1, $remainingAbsolute)));
                }
                $this->store->write($session->id(), $payload, $effectiveTtl);
                $persisted = true;
            }
        }

        if (($persisted && ($isNew || $session->isDirty())) || $session->wasRegenerated()) {
            return new Cookie(
                name: $this->cookieName,
                value: $session->id(),
                maxAge: $effectiveTtl,
                path: '/',
                secure: $this->secure || $isHttps,
                httpOnly: true,
                sameSite: $this->sameSite,
            );
        }

        return null;
    }

    private function protectSetCookieCaching(ResponseInterface $response): ResponseInterface
    {
        $cc = strtolower($response->getHeaderLine('Cache-Control'));
        if ('' === $cc || (!str_contains($cc, 'private') && !str_contains($cc, 'no-store'))) {
            return $response->withHeader('Cache-Control', 'private, no-cache="Set-Cookie"');
        }

        return $response;
    }
}
