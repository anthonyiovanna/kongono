<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Isolates exact host-output sequencing from response validation and staging. */
#[Api(ApiStatus::Internal)]
interface CgiEmissionTarget
{
    public function headersSent(): bool;

    public function setStatus(int $status): void;

    public function emitHeader(string $name, string $value, bool $replace): void;

    public function writeBody(string $bytes): int|false;
}
