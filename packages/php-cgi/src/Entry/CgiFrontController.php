<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Entry;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Exception\CgiEntryShutdownException;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use Throwable;

/** Runs one CGI entry attempt and one explicit application-shutdown attempt. */
#[Api(ApiStatus::Experimental)]
final class CgiFrontController
{
    private function __construct() {}

    /** @param Closure(): void $shutdown */
    public static function run(
        WebRuntimeAdapter $runtime,
        Closure $shutdown,
    ): void {
        $primaryFailure = null;

        try {
            CgiWebEntry::run($runtime);
        } catch (Throwable $failure) {
            $primaryFailure = $failure;
        }

        $shutdownFailure = null;

        try {
            $shutdown();
        } catch (Throwable $failure) {
            $shutdownFailure = $failure;
        }

        if (null === $primaryFailure) {
            if (null === $shutdownFailure) {
                return;
            }

            throw $shutdownFailure;
        }

        if (null === $shutdownFailure) {
            throw $primaryFailure;
        }

        throw new CgiEntryShutdownException($primaryFailure, $shutdownFailure);
    }
}
