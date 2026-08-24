<?php

declare(strict_types=1);

const MAX_CGI_OUTPUT_BYTES = 1048576;

$root = str_replace('\\', '/', dirname(__DIR__));
$index = $root . '/public/index.php';
$executable = str_replace('\\', '/', dirname(PHP_BINARY)) . ('Windows' === PHP_OS_FAMILY ? '/php-cgi.exe' : '/php-cgi');
$resolvedExecutable = realpath($executable);
$resolvedIndex = realpath($index);

if (
    false === $resolvedExecutable
    || str_replace('\\', '/', $resolvedExecutable) !== $executable
    || !is_file($executable)
    || is_link($executable)
    || false === $resolvedIndex
    || str_replace('\\', '/', $resolvedIndex) !== $index
    || !is_file($index)
    || is_link($index)
) {
    fwrite(STDERR, "Application run failed.\n");
    exit(1);
}

$command = [$executable, '-n'];
foreach ([
    'cgi.force_redirect=0',
    'user_ini.filename=',
    'enable_post_data_reading=0',
    'output_buffering=0',
    'zlib.output_compression=0',
    'display_errors=0',
    'display_startup_errors=0',
    'html_errors=0',
    'expose_php=0',
    'log_errors=0',
    'auto_prepend_file=',
    'auto_append_file=',
    'cgi.rfc2616_headers=0',
] as $setting) {
    $command[] = '-d';
    $command[] = $setting;
}

$environment = [
    'GATEWAY_INTERFACE' => 'CGI/1.1',
    'FCGI_ROLE' => 'RESPONDER',
    'REQUEST_METHOD' => 'GET',
    'SERVER_PROTOCOL' => 'HTTP/1.1',
    'REQUEST_URI' => '/?kongono_proof=1',
    'QUERY_STRING' => 'kongono_proof=1',
    'HTTP_HOST' => 'localhost',
    'REMOTE_ADDR' => '127.0.0.1',
    'SCRIPT_FILENAME' => $index,
    'SCRIPT_NAME' => '/index.php',
    'REDIRECT_STATUS' => '1',
    'SERVER_ADDR' => '127.0.0.1',
    'SERVER_NAME' => 'localhost',
    'SERVER_PORT' => '80',
];
foreach (['SystemRoot', 'WINDIR', 'COMSPEC', 'TEMP', 'TMP', 'TMPDIR'] as $name) {
    $value = getenv($name);
    if (is_string($value) && '' !== $value) {
        $environment[$name] = $value;
    }
}

$pipes = [];
$process = @proc_open(
    $command,
    [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
    $pipes,
    $root,
    $environment,
    ['bypass_shell' => true],
);
if (
    !is_resource($process)
    || !isset($pipes[0], $pipes[1], $pipes[2])
    || !is_resource($pipes[0])
    || !is_resource($pipes[1])
    || !is_resource($pipes[2])
) {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    fwrite(STDERR, "Application run failed.\n");
    exit(1);
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1], MAX_CGI_OUTPUT_BYTES + 1);
$stderr = stream_get_contents($pipes[2], MAX_CGI_OUTPUT_BYTES + 1);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($process);

if (!is_string($stdout) || !is_string($stderr) || '' !== $stderr || strlen($stdout) > MAX_CGI_OUTPUT_BYTES || 0 !== $exit) {
    fwrite(STDERR, "Application run failed.\n");
    exit(1);
}

$separator = str_contains($stdout, "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
$parts = explode($separator, $stdout, 2);
if (2 !== count($parts) || !str_contains($parts[0], 'Status: 200') && !str_starts_with($parts[0], 'Content-Type:') || "Hello from Kongono.\n" !== $parts[1]) {
    fwrite(STDERR, "Application run failed.\n");
    exit(1);
}

fwrite(STDOUT, $parts[1]);
