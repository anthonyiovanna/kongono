<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsolePresentation\Exception\InvalidConsolePresentationException;

/** Carries one complete immutable stdout or stderr document and its status. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandPresentation
{
    public function __construct(
        private string $stdout,
        private string $stderr,
        private CommandMediaType $mediaType,
        private CommandExitStatus $exitStatus,
    ) {
        $document = '' !== $stdout ? $stdout : $stderr;
        if (
            ('' === $stdout) === ('' === $stderr)
            || strlen($stdout) + strlen($stderr) > 1_048_576
            || 1 !== preg_match('//u', $document)
            || str_starts_with($document, "\xEF\xBB\xBF")
            || 1 === preg_match('/[\x{0000}-\x{0009}\x{000B}-\x{001F}\x{007F}-\x{009F}]/u', $document)
            || !str_ends_with($document, "\n")
            || str_ends_with($document, "\n\n")
        ) {
            throw InvalidConsolePresentationException::invalidValue();
        }
    }

    public function stdout(): string
    {
        return $this->stdout;
    }

    public function stderr(): string
    {
        return $this->stderr;
    }

    public function mediaType(): CommandMediaType
    {
        return $this->mediaType;
    }

    public function exitStatus(): CommandExitStatus
    {
        return $this->exitStatus;
    }
}
