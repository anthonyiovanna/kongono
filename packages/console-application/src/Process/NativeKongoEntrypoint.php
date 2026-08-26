<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleApplication\KongoApplication;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandExitStatus;
use Throwable;

/** Adapts explicit arguments and streams to one application call without global access. */
#[Api(ApiStatus::Experimental)]
final readonly class NativeKongoEntrypoint
{
    public function __construct(private KongoApplication $application) {}

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        try {
            return $this->application->run(
                new KongoProcessRequest($arguments),
                new NativeStreamOutputPort($stdout),
                new NativeStreamOutputPort($stderr),
            );
        } catch (Throwable) {
            return CommandExitStatus::Infrastructure->value;
        }
    }
}
