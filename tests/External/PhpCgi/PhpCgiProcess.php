<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi;

use RuntimeException;
use Throwable;

/** Owns one verified operator-supplied PHP-CGI listener and its exact cleanup. */
final class PhpCgiProcess
{
    private const array INI = [
        'enable_post_data_reading' => '0',
        'output_buffering' => '0',
        'zlib.output_compression' => '0',
        'display_errors' => '0',
        'display_startup_errors' => '0',
        'log_errors' => '0',
        'default_mimetype' => '',
        'html_errors' => '0',
        'expose_php' => '0',
        'auto_prepend_file' => '',
        'auto_append_file' => '',
        'cgi.rfc2616_headers' => '0',
    ];

    /** @var null|resource */
    private mixed $process = null;
    /** @var list<resource> */
    private array $pipes = [];
    private int $port = 0;
    private int $pid = 0;
    private bool $retired = false;

    private function __construct(
        private readonly string $binary,
        private readonly string $repositoryRoot,
        private readonly string $temporaryDirectory,
        private readonly string $journal,
    ) {}

    /** @param array<string, string> $iniOverrides */
    public static function start(string $binary, string $repositoryRoot, array $iniOverrides = []): self
    {
        $verifiedBinary = self::verifiedBinary($binary, $repositoryRoot);
        $temporaryDirectory = self::createTemporaryDirectory();
        $journal = $temporaryDirectory . DIRECTORY_SEPARATOR . 'journal.jsonl';
        $journalHandle = @fopen($journal, 'xb');
        if (false === $journalHandle) {
            @rmdir($temporaryDirectory);
            throw new RuntimeException('host_capability_journal_create_failed');
        }
        fclose($journalHandle);
        $instance = new self($verifiedBinary, $repositoryRoot, $temporaryDirectory, $journal);

        try {
            $instance->startListener($iniOverrides);

            return $instance;
        } catch (Throwable $failure) {
            $instance->retire();
            throw $failure;
        }
    }

    public static function proveCliRejection(string $repositoryRoot, string $fixture): void
    {
        $binary = realpath(PHP_BINARY);
        $root = realpath($repositoryRoot);
        $script = realpath($fixture);
        if (
            false === $binary
            || false === $root
            || false === $script
            || 'php.exe' !== strtolower(basename($binary))
            || !is_file($binary)
            || !is_file($script)
            || is_link($binary)
            || is_link($script)
        ) {
            throw new RuntimeException('host_capability_cli_invalid');
        }
        $temporaryDirectory = self::createTemporaryDirectory();
        $journal = $temporaryDirectory . DIRECTORY_SEPARATOR . 'journal.jsonl';
        $journalHandle = @fopen($journal, 'xb');
        if (false === $journalHandle) {
            @rmdir($temporaryDirectory);
            throw new RuntimeException('host_capability_journal_create_failed');
        }
        fclose($journalHandle);
        $instance = new self($binary, $root, $temporaryDirectory, $journal);

        try {
            $result = self::runBounded(
                [$binary, '-d', 'display_errors=0', '-d', 'log_errors=0', $script],
                $root,
                [...self::baseEnvironment(), 'KONGONO_PHASE3_ACCEPTANCE_JOURNAL' => $journal],
            );
            $records = $instance->journalRecords();
            if (
                0 === $result['exit']
                || '' !== $result['stdout']
                || '' !== $result['stderr']
                || 1 !== count($records)
                || 'failure' !== $records[0]['outcome']
                || 'incomplete' !== $records[0]['shutdown']
                || 0 !== $records[0]['active_operations']
            ) {
                throw new RuntimeException('cli_profile_rejection_invalid');
            }
        } finally {
            $instance->removeTemporaryArtifacts();
        }
    }

    public function port(): int
    {
        $this->requireRunning();

        return $this->port;
    }

    public function pid(): int
    {
        $this->requireRunning();

        return $this->pid;
    }

    /** @return list<array{profile: string, outcome: string, shutdown: string, active_operations: int}> */
    public function journalRecords(): array
    {
        clearstatcache(true, $this->journal);
        $size = @filesize($this->journal);
        if (!is_int($size) || $size > 2_097_152) {
            throw new RuntimeException('acceptance_journal_size_invalid');
        }
        $contents = @file_get_contents($this->journal);
        if (false === $contents) {
            throw new RuntimeException('acceptance_journal_read_failed');
        }
        if ('' === $contents) {
            return [];
        }
        if (!str_ends_with($contents, "\n")) {
            throw new RuntimeException('acceptance_journal_line_incomplete');
        }
        $lines = explode("\n", substr($contents, 0, -1));
        if (count($lines) > 4096) {
            throw new RuntimeException('acceptance_journal_record_limit');
        }
        $records = [];
        foreach ($lines as $line) {
            if ('' === $line || strlen($line) > 512) {
                throw new RuntimeException('acceptance_journal_line_invalid');
            }
            $decoded = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || array_keys($decoded) !== ['profile', 'outcome', 'shutdown', 'active_operations']) {
                throw new RuntimeException('acceptance_journal_record_invalid');
            }
            if (
                !is_string($decoded['profile'])
                || !is_string($decoded['outcome'])
                || !is_string($decoded['shutdown'])
                || !is_int($decoded['active_operations'])
            ) {
                throw new RuntimeException('acceptance_journal_record_invalid');
            }
            $records[] = [
                'profile' => $decoded['profile'],
                'outcome' => $decoded['outcome'],
                'shutdown' => $decoded['shutdown'],
                'active_operations' => $decoded['active_operations'],
            ];
        }

        return $records;
    }

    /** @phpstan-impure */
    public function isRunning(): bool
    {
        if (!is_resource($this->process)) {
            return false;
        }
        $status = proc_get_status($this->process);

        return $status['running'];
    }

    public function retire(): void
    {
        if ($this->retired) {
            return;
        }
        $this->retired = true;
        $failure = null;
        try {
            $process = $this->process;
            if (is_resource($process)) {
                @proc_terminate($process);
                $deadline = microtime(true) + 5.0;
                while ($this->isRunning() && microtime(true) < $deadline) {
                    usleep(50_000);
                }
                if ($this->isRunning()) {
                    @proc_terminate($process);
                    $deadline = microtime(true) + 5.0;
                    while ($this->isRunning() && microtime(true) < $deadline) {
                        usleep(50_000);
                    }
                }
                if ($this->isRunning()) {
                    $failure = new RuntimeException('php_cgi_retirement_failed');
                }
            }
        } catch (Throwable $retirementFailure) {
            $failure = $retirementFailure;
        }

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                @fclose($pipe);
            }
        }
        $this->pipes = [];
        if (is_resource($this->process)) {
            @proc_close($this->process);
        }
        $this->process = null;
        $this->port = 0;
        $this->pid = 0;

        try {
            $this->removeTemporaryArtifacts();
        } catch (Throwable $cleanupFailure) {
            $failure ??= $cleanupFailure;
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    public function __destruct()
    {
        try {
            $this->retire();
        } catch (Throwable) {
        }
    }

    private static function verifiedBinary(string $binary, string $repositoryRoot): string
    {
        if (1 !== preg_match('/^[A-Za-z]:[\\\\\/]/D', $binary) || 'php-cgi.exe' !== strtolower(basename($binary))) {
            throw new RuntimeException('host_capability_binary_invalid');
        }
        $real = realpath($binary);
        $root = realpath($repositoryRoot);
        if (false === $real || false === $root || !is_file($real) || is_link($real)) {
            throw new RuntimeException('host_capability_binary_invalid');
        }
        $result = self::runBounded([$real, '-v'], $root, self::baseEnvironment());
        if (0 !== $result['exit'] || '' !== $result['stderr'] || 1 !== preg_match('/^PHP 8\.5\.[0-9]+ \(cgi-fcgi\).*(?:ZTS Visual C\+\+ 2022 x64)/D', trim($result['stdout']))) {
            throw new RuntimeException('host_capability_version_invalid');
        }

        return $real;
    }

    /** @param array<string, string> $iniOverrides */
    private function startListener(array $iniOverrides): void
    {
        foreach ($iniOverrides as $name => $value) {
            if (!array_key_exists($name, self::INI)) {
                throw new RuntimeException('host_capability_ini_override_invalid');
            }
        }
        $ini = array_replace(self::INI, $iniOverrides);
        for ($attempt = 0; $attempt < 8; ++$attempt) {
            $port = self::reserveEphemeralPort();
            $command = [$this->binary];
            foreach ($ini as $name => $value) {
                $command[] = '-d';
                $command[] = $name . '=' . $value;
            }
            $command[] = '-b';
            $command[] = '127.0.0.1:' . $port;
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $pipes = [];
            $process = @proc_open(
                $command,
                $descriptors,
                $pipes,
                $this->repositoryRoot,
                [...self::baseEnvironment(), 'KONGONO_PHASE3_ACCEPTANCE_JOURNAL' => $this->journal],
                ['bypass_shell' => true],
            );
            if (!is_resource($process) || 3 !== count($pipes)) {
                if (is_resource($process)) {
                    @proc_terminate($process);
                    @proc_close($process);
                }
                continue;
            }
            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $this->process = $process;
            $this->pipes = [$pipes[1], $pipes[2]];
            $this->port = $port;
            $status = proc_get_status($process);
            $this->pid = $status['pid'];
            if ($this->pid > 0 && $this->waitForListener()) {
                return;
            }
            $this->retireFailedAttempt();
            $this->retired = false;
        }
        throw new RuntimeException('host_capability_listener_unavailable');
    }

    private function waitForListener(): bool
    {
        $deadline = microtime(true) + 5.0;
        while (microtime(true) < $deadline) {
            if (!$this->isRunning()) {
                return false;
            }
            $errno = 0;
            $error = '';
            $socket = @stream_socket_client('tcp://127.0.0.1:' . $this->port, $errno, $error, 0.05, STREAM_CLIENT_CONNECT);
            if (false !== $socket) {
                fclose($socket);

                return true;
            }
            usleep(50_000);
        }

        return false;
    }

    private function retireFailedAttempt(): void
    {
        if (is_resource($this->process)) {
            @proc_terminate($this->process);
            $deadline = microtime(true) + 5.0;
            while ($this->isRunning() && microtime(true) < $deadline) {
                usleep(50_000);
            }
        }
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                @fclose($pipe);
            }
        }
        $this->pipes = [];
        if (is_resource($this->process)) {
            @proc_close($this->process);
        }
        $this->process = null;
        $this->port = 0;
        $this->pid = 0;
    }

    private function requireRunning(): void
    {
        if ($this->retired || !$this->isRunning()) {
            throw new RuntimeException('php_cgi_process_not_running');
        }
    }

    private function removeTemporaryArtifacts(): void
    {
        $base = realpath(sys_get_temp_dir());
        $directory = realpath($this->temporaryDirectory);
        if (false === $base || false === $directory || dirname($directory) !== $base) {
            throw new RuntimeException('acceptance_temp_containment_invalid');
        }
        if (is_file($this->journal) && !@unlink($this->journal)) {
            throw new RuntimeException('acceptance_journal_delete_failed');
        }
        $remaining = @scandir($directory);
        if (false === $remaining || $remaining !== ['.', '..'] || !@rmdir($directory)) {
            throw new RuntimeException('acceptance_temp_delete_failed');
        }
    }

    private static function reserveEphemeralPort(): int
    {
        $errno = 0;
        $error = '';
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if (false === $server) {
            throw new RuntimeException('host_capability_port_reservation_failed');
        }
        $name = stream_socket_get_name($server, false);
        fclose($server);
        if (false === $name || 1 !== preg_match('/^127\.0\.0\.1:([0-9]+)$/D', $name, $matches)) {
            throw new RuntimeException('host_capability_port_reservation_failed');
        }
        $port = (int) $matches[1];
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('host_capability_port_reservation_failed');
        }

        return $port;
    }

    private static function createTemporaryDirectory(): string
    {
        $base = realpath(sys_get_temp_dir());
        if (false === $base) {
            throw new RuntimeException('host_capability_temp_unavailable');
        }
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        $guid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
        $directory = $base . DIRECTORY_SEPARATOR . $guid;
        if (!@mkdir($directory, 0700) || is_link($directory)) {
            throw new RuntimeException('host_capability_temp_create_failed');
        }

        return $directory;
    }

    /** @return array{SystemRoot: string, TEMP: string, TMP: string} */
    private static function baseEnvironment(): array
    {
        $environment = [];
        foreach (['SystemRoot', 'TEMP', 'TMP'] as $name) {
            $value = getenv($name);
            if (false === $value || '' === $value) {
                throw new RuntimeException('host_capability_environment_missing');
            }
            $environment[$name] = $value;
        }

        return $environment;
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private static function runBounded(array $command, string $workingDirectory, array $environment): array
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $pipes = [];
        $process = @proc_open($command, $descriptors, $pipes, $workingDirectory, $environment, ['bypass_shell' => true]);
        if (!is_resource($process) || 3 !== count($pipes)) {
            throw new RuntimeException('host_capability_process_start_failed');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if (false === $stdout || false === $stderr || strlen($stdout) > 65_536 || strlen($stderr) > 65_536) {
            throw new RuntimeException('host_capability_process_output_invalid');
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
