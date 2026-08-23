<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Rejects invalid Chie values without presenting rejected caller data or native traces. */
#[Api(ApiStatus::Internal)]
final class InvalidChieValueException extends InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function citation(): self
    {
        return new self('Invalid Chie citation.');
    }

    public static function claim(): self
    {
        return new self('Invalid Chie claim.');
    }

    public static function response(): self
    {
        return new self('Invalid Chie response.');
    }

    public static function diagnosticViolation(): self
    {
        return new self('Invalid Chie diagnostic violation.');
    }

    public static function exceptionDiagnostic(): self
    {
        return new self('Invalid Chie exception diagnostic.');
    }

    public static function errorEnvelope(): self
    {
        return new self('Invalid Chie error.');
    }

    public static function candidateText(): self
    {
        return new self('Invalid Chie candidate text.');
    }

    public static function proposal(): self
    {
        return new self('Invalid Chie proposal.');
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', self::class, $this->getMessage());
    }
}
