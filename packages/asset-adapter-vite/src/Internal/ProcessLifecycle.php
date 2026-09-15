<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterContract\Process\BuildProcessResult;
use Throwable;

/** Owns one bounded direct-process handle lifecycle without retained state. */
#[Api(ApiStatus::Internal)]
final class ProcessLifecycle
{
    public static function execute(BuildProcessCommand $command): BuildProcessResult
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $pipes = [];
        try {
            $process = @proc_open($command->arguments(), $descriptors, $pipes, $command->workingDirectory(), $command->environment(), ['bypass_shell' => true]);
        } catch (Throwable) {
            return new BuildProcessResult(false, null, '', '', false, false, false, false, true, true, 'start_failed');
        }
        if (!is_resource($process) || 3 !== count($pipes)) {
            return new BuildProcessResult(false, null, '', '', false, false, false, false, true, true, 'start_failed');
        }

        @fclose($pipes[0]);
        @stream_set_blocking($pipes[1], false);
        @stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $timedOut = false;
        $stdoutExceeded = false;
        $stderrExceeded = false;
        $terminated = false;
        $readFailed = false;
        $exitCode = null;
        $deadline = hrtime(true) + ($command->timeoutSeconds() * 1_000_000_000);

        while (true) {
            $status = @proc_get_status($process);
            if (!$status['running'] && $status['exitcode'] >= 0) {
                $exitCode = $status['exitcode'];
            }
            $read = [];
            if (is_resource($pipes[1]) && !feof($pipes[1])) {
                $read[] = $pipes[1];
            }
            if (is_resource($pipes[2]) && !feof($pipes[2])) {
                $read[] = $pipes[2];
            }
            if ([] !== $read) {
                $write = null;
                $except = null;
                $selected = @stream_select($read, $write, $except, 0, 50_000);
                if (false === $selected) {
                    $readFailed = true;
                    break;
                }
                foreach ($read as $stream) {
                    $chunk = @fread($stream, 8_192);
                    if (false === $chunk) {
                        $readFailed = true;
                        break 2;
                    }
                    if ($stream === $pipes[1]) {
                        $stdout .= $chunk;
                        if (strlen($stdout) > $command->stdoutBytes()) {
                            $stdoutExceeded = true;
                            break 2;
                        }
                    } else {
                        $stderr .= $chunk;
                        if (strlen($stderr) > $command->stderrBytes()) {
                            $stderrExceeded = true;
                            break 2;
                        }
                    }
                }
            }
            if (hrtime(true) >= $deadline) {
                $timedOut = true;
                break;
            }
            if (!$status['running'] && [] === $read) {
                break;
            }
        }

        if ($timedOut || $stdoutExceeded || $stderrExceeded || $readFailed) {
            $terminated = @proc_terminate($process);
        }
        foreach ([1, 2] as $index) {
            if (is_resource($pipes[$index])) {
                for ($attempt = 0; $attempt < 8; ++$attempt) {
                    $chunk = @fread($pipes[$index], 8_192);
                    if (false === $chunk || '' === $chunk) {
                        break;
                    }
                    if (1 === $index) {
                        $stdout .= $chunk;
                    } else {
                        $stderr .= $chunk;
                    }
                }
                @fclose($pipes[$index]);
            }
        }
        $stdout = substr($stdout, 0, $command->stdoutBytes());
        $stderr = substr($stderr, 0, $command->stderrBytes());
        $closedCode = @proc_close($process);
        if (null === $exitCode && $closedCode >= 0) {
            $exitCode = $closedCode;
        }
        $reason = $timedOut ? 'timeout' : ($stdoutExceeded ? 'stdout_limit' : ($stderrExceeded ? 'stderr_limit' : ($readFailed ? 'read_failed' : 'none')));

        return new BuildProcessResult(true, $exitCode, $stdout, $stderr, $timedOut, $stdoutExceeded, $stderrExceeded, $terminated, true, true, $reason);
    }

    private function __construct() {}
}
