<?php

declare(strict_types=1);

namespace Kongono\Hantei\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Executes one argument-vector process with independent stream and time bounds. */
#[Api(ApiStatus::Internal)]
final class ProcessRunner
{
    /** @param non-empty-list<string> $arguments */
    public function run(array $arguments, string $workingDirectory, int $timeoutSeconds, int $streamLimit): ProcessResult
    {
        if ($timeoutSeconds < 1 || $timeoutSeconds > 600 || $streamLimit < 1 || $streamLimit > 8_388_608) {
            return new ProcessResult(false, null, '', '', 'process-start-failed');
        }

        $pipes = [];
        try {
            $process = proc_open(
                $arguments,
                [
                    0 => ['pipe', 'r'],
                    1 => ['socket'],
                    2 => ['socket'],
                ],
                $pipes,
                $workingDirectory,
                null,
                ['bypass_shell' => true, 'suppress_errors' => true],
            );
        } catch (Throwable) {
            return new ProcessResult(false, null, '', '', 'process-start-failed');
        }
        if (false === $process || 3 !== count($pipes)) {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            return new ProcessResult(false, null, '', '', 'process-start-failed');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $blockedReason = null;
        $exitCode = null;
        $startedAt = hrtime(true);

        while (true) {
            if (!$this->readAvailable($pipes[1], $pipes[2], $stdout, $stderr, $streamLimit, $blockedReason, 10_000)) {
                proc_terminate($process);
                $exitCode = 1;
                break;
            }
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = $status['exitcode'];
                break;
            }
            if (null !== $blockedReason) {
                proc_terminate($process);
                $exitCode = 1;
                break;
            }
            if ((hrtime(true) - $startedAt) / 1_000_000_000 > $timeoutSeconds) {
                $blockedReason = 'process-timeout';
                proc_terminate($process);
                $exitCode = 1;
                break;
            }
        }

        for ($attempt = 0; $attempt < 10; ++$attempt) {
            if (!$this->readAvailable($pipes[1], $pipes[2], $stdout, $stderr, $streamLimit, $blockedReason, 10_000)) {
                $blockedReason = 'invalid-evidence';
                break;
            }
            if (feof($pipes[1]) && feof($pipes[2])) {
                break;
            }
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return new ProcessResult(true, $exitCode, $stdout, $stderr, $blockedReason);
    }

    /**
     * @param resource $stdoutStream
     * @param resource $stderrStream
     */
    private function readAvailable($stdoutStream, $stderrStream, string &$stdout, string &$stderr, int $limit, ?string &$blockedReason, int $waitMicroseconds): bool
    {
        $read = [$stdoutStream, $stderrStream];
        $write = null;
        $except = null;
        $selected = stream_select($read, $write, $except, 0, $waitMicroseconds);
        if (false === $selected) {
            $blockedReason = 'invalid-evidence';

            return false;
        }
        foreach ($read as $stream) {
            $chunk = fread($stream, 65_536);
            if (false === $chunk || '' === $chunk) {
                continue;
            }
            $buffer = $stream === $stdoutStream ? $stdout : $stderr;
            $remaining = $limit - strlen($buffer);
            if (strlen($chunk) > $remaining) {
                if ($remaining > 0) {
                    $buffer .= substr($chunk, 0, $remaining);
                }
                $blockedReason = 'output-limit';
            } else {
                $buffer .= $chunk;
            }
            if ($stream === $stdoutStream) {
                $stdout = $buffer;
            } else {
                $stderr = $buffer;
            }
        }

        return true;
    }
}
