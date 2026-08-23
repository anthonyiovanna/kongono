<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Rejects invalid Oshie values without disclosing rejected caller data. */
#[Api(ApiStatus::Internal)]
final class InvalidOshieValueException extends InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function citation(): self
    {
        return new self('Invalid Oshie citation.');
    }
    public static function lesson(): self
    {
        return new self('Invalid Oshie lesson.');
    }
    public static function exercise(): self
    {
        return new self('Invalid Oshie exercise.');
    }
    public static function attempt(): self
    {
        return new self('Invalid Oshie attempt.');
    }
    public static function response(): self
    {
        return new self('Invalid Oshie response.');
    }
    public static function diagnosticViolation(): self
    {
        return new self('Invalid Oshie diagnostic violation.');
    }
    public static function exceptionDiagnostic(): self
    {
        return new self('Invalid Oshie exception diagnostic.');
    }
    public static function errorEnvelope(): self
    {
        return new self('Invalid Oshie error.');
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', self::class, $this->getMessage());
    }
}
