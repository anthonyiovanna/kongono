<?php

declare(strict_types=1);

namespace Kongono\Tests\External\DeveloperIntelligence;

/** Runs one argument-vector process with independent bounded stream capture. */
final class ProcessRunner
{
    /**
     * @param list<string>              $command
     * @param array<string, string|null> $environment
     */
    public function run(
        array $command,
        string $workingDirectory,
        array $environment = [],
        int $timeoutSeconds = 600,
        int $maximumStreamBytes = 8_388_608,
    ): ProcessResult {
        if ([] === $command || $timeoutSeconds < 1 || $maximumStreamBytes < 1) {
            return new ProcessResult(false, null, '', '', 'invalid-process-request');
        }
        foreach ($command as $argument) {
            if (str_contains($argument, "\0")) {
                return new ProcessResult(false, null, '', '', 'invalid-process-request');
            }
        }

        $inherited = getenv();
        foreach ($environment as $name => $value) {
            if (null === $value) {
                unset($inherited[$name]);
            } else {
                $inherited[$name] = $value;
            }
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $process = @proc_open(
            $command,
            $descriptors,
            $pipes,
            $workingDirectory,
            $inherited,
            ['bypass_shell' => true, 'suppress_errors' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || 3 !== count($pipes)) {
            return new ProcessResult(false, null, '', '', 'process-start-failed');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $blocked = null;
        $observedExit = null;
        $deadline = microtime(true) + $timeoutSeconds;

        try {
            while (true) {
                $this->drain($pipes[1], $stdout);
                $this->drain($pipes[2], $stderr);
                if (strlen($stdout) > $maximumStreamBytes || strlen($stderr) > $maximumStreamBytes) {
                    $blocked = 'output-limit';
                    proc_terminate($process);
                    break;
                }

                $status = proc_get_status($process);
                if (!$status['running']) {
                    $observedExit = $status['exitcode'] >= 0 ? $status['exitcode'] : null;
                    break;
                }
                if (microtime(true) >= $deadline) {
                    $blocked = 'process-timeout';
                    proc_terminate($process);
                    break;
                }
                usleep(10_000);
            }

            if (null !== $blocked) {
                $stopDeadline = microtime(true) + 2.0;
                do {
                    $status = proc_get_status($process);
                    if (!$status['running']) {
                        break;
                    }
                    usleep(10_000);
                } while (microtime(true) < $stopDeadline);
                if ($status['running']) {
                    proc_terminate($process, 9);
                }
            }

            stream_set_blocking($pipes[1], true);
            stream_set_blocking($pipes[2], true);
            $this->drain($pipes[1], $stdout);
            $this->drain($pipes[2], $stderr);
        } finally {
            foreach ([1, 2] as $index) {
                if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                    fclose($pipes[$index]);
                }
            }
        }

        $closedExit = proc_close($process);
        $exitCode = null !== $observedExit ? $observedExit : ($closedExit >= 0 ? $closedExit : null);

        return new ProcessResult(
            true,
            $exitCode,
            substr($stdout, 0, $maximumStreamBytes),
            substr($stderr, 0, $maximumStreamBytes),
            $blocked,
        );
    }

    /** @param resource $stream */
    private function drain(mixed $stream, string &$buffer): void
    {
        while (true) {
            $chunk = fread($stream, 65_536);
            if (false === $chunk || '' === $chunk) {
                return;
            }
            $buffer .= $chunk;
        }
    }
}
