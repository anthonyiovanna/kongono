<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Psr\Http\Message\RequestInterface;
use UnexpectedValueException;

/** Enforces origin and slash-delimited path containment before network execution. */
#[Api(ApiStatus::Internal)]
final class OperationGuard
{
    public static function validate(ServiceBinding $binding, RequestInterface $request): void
    {
        $uri = $request->getUri();
        if ('' !== $uri->getUserInfo() || '' !== $uri->getFragment()) {
            throw new UnexpectedValueException('External service request URI is not permitted.');
        }

        $origin = CanonicalOrigin::fromUri($uri);
        if (!$binding->endpoint()->origin()->equals($origin)) {
            throw new UnexpectedValueException('External service request origin is not permitted.');
        }

        $path = $uri->getPath();
        if (!self::canonicalAbsolutePath($path)) {
            throw new UnexpectedValueException('External service request path is not canonical.');
        }

        $basePath = $binding->endpoint()->basePath()->value();
        if ('' !== $basePath && $path !== $basePath && !str_starts_with($path, $basePath . '/')) {
            throw new UnexpectedValueException('External service request path is outside the binding.');
        }
    }

    private static function canonicalAbsolutePath(string $path): bool
    {
        if ('' === $path || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return false;
        }
        if (1 === preg_match('/%(?![0-9A-F]{2})/', $path)) {
            return false;
        }
        if (1 === preg_match('/[\x00-\x20\x7F]/', $path)) {
            return false;
        }
        foreach (explode('/', $path) as $position => $segment) {
            if (0 === $position) {
                continue;
            }
            if ('' === $segment && '/' !== $path || '.' === $segment || '..' === $segment) {
                return false;
            }
        }

        return true;
    }

    private function __construct() {}
}
