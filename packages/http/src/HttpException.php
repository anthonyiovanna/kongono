<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\Diagnostic;
use Throwable;

final class HttpException extends CoreException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
        public readonly array $headers = [],
        ?Diagnostic $diagnostic = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $diagnostic, $previous);
    }
}
