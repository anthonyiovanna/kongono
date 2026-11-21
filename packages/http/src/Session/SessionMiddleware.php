<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

use Kongono\Http\Cookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Loads, attaches, persists, and emits secure cookies for Kongono Sessions.
 * Completely persistent-runtime safe (never uses PHP session_start() or $_SESSION).
 */
final readonly class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionStoreInterface $store,
        private string $cookieName = 'kongono_session',
        private int $ttlSeconds = 7200,
        private bool $secure = false,
        private string $sameSite = 'Lax',
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();
        $incomingId = is_string($cookies[$this->cookieName] ?? null) ? $cookies[$this->cookieName] : '';
        $isNew = false;

        if ('' !== $incomingId && 1 === preg_match('/\A[a-f0-9]{32,128}\z/', $incomingId)) {
            $data = $this->store->read($incomingId);
            if (null !== $data) {
                $session = new Session($incomingId, $data);
            } else {
                $session = new Session(bin2hex(random_bytes(24)));
                $isNew = true;
            }
        } else {
            $session = new Session(bin2hex(random_bytes(24)));
            $isNew = true;
        }

        $response = $handler->handle($request->withAttribute(Session::class, $session));

        if ($session->wasRegenerated() && null !== $session->previousId()) {
            $this->store->destroy($session->previousId());
        }

        if ($isNew || $session->isDirty() || $session->wasRegenerated()) {
            $this->store->write($session->id(), $session->exportPayload(), $this->ttlSeconds);
        }

        if ($isNew || $session->wasRegenerated()) {
            $cookie = new Cookie(
                name: $this->cookieName,
                value: $session->id(),
                maxAge: $this->ttlSeconds,
                path: '/',
                secure: $this->secure,
                httpOnly: true,
                sameSite: $this->sameSite,
            );
            $response = $response->withAddedHeader('Set-Cookie', $cookie->toHeaderValue());
        }

        return $response;
    }
}
