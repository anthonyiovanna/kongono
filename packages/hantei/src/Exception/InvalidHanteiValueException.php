<?php

declare(strict_types=1);

namespace Kongono\Hantei\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Rejects invalid Hantei values without disclosing rejected data in string form. */
#[Api(ApiStatus::Internal)]
final class InvalidHanteiValueException extends InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function evidence(): self
    {
        return new self('Invalid Hantei evidence.');
    }

    public static function report(): self
    {
        return new self('Invalid Hantei report.');
    }

    public static function diagnosticViolation(): self
    {
        return new self('Invalid Hantei diagnostic violation.');
    }

    public static function exceptionDiagnostic(): self
    {
        return new self('Invalid Hantei exception diagnostic.');
    }

    public static function process(): self
    {
        return new self('Invalid Hantei process result.');
    }

    public static function errorEnvelope(): self
    {
        return new self('Invalid Hantei error.');
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', self::class, $this->getMessage());
    }
}
