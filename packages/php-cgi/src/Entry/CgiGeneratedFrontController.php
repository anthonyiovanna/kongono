<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Entry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Runtime\GeneratedWebRuntimeApplication;

/** Runs the existing CGI front controller for one generated web application. */
#[Api(ApiStatus::Experimental)]
final class CgiGeneratedFrontController
{
    private function __construct() {}

    public static function run(GeneratedWebRuntimeApplication $application): void
    {
        CgiFrontController::run(
            $application->runtime(),
            static function () use ($application): void {
                $application->shutdown();
            },
        );
    }
}
