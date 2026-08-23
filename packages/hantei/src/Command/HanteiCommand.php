<?php

declare(strict_types=1);

namespace Kongono\Hantei\Command;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use Kongono\Hantei\Hantei;
use Kongono\Hantei\HanteiVerdict;
use Kongono\Hantei\Internal\CanonicalJson;
use Throwable;

/** Implements the exact non-interactive verify command grammar. */
#[Api(ApiStatus::Internal)]
final class HanteiCommand
{
    /**
     * @param list<string>  $arguments
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function run(array $arguments, $stdout = null, $stderr = null): int
    {
        $stdout ??= STDOUT;
        $stderr ??= STDERR;
        if (2 !== count($arguments) || 'verify' !== $arguments[0]) {
            fwrite($stderr, CanonicalJson::encodeError(HanteiDiagnostic::InvalidRequest, 'invalid-command-usage'));

            return 64;
        }

        try {
            $report = (new Hantei())->verify($arguments[1]);
            fwrite($stdout, CanonicalJson::encodeReport($report));

            return match ($report->verdict()) {
                HanteiVerdict::Pass => 0,
                HanteiVerdict::Fail => 1,
                HanteiVerdict::Blocked => 2,
            };
        } catch (Throwable) {
            fwrite($stderr, CanonicalJson::encodeError(HanteiDiagnostic::UnexpectedCommandFailure, 'unexpected-failure'));

            return 70;
        }
    }
}
