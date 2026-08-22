<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi;

use RuntimeException;
use Throwable;

/** Owns the closed Phase 3 external acceptance matrix and success envelope. */
final class Phase3AcceptanceRunner
{
    private const int INVALID_INVOCATION = 64;
    private const int HOST_UNAVAILABLE = 69;
    private const int ACCEPTANCE_FAILURE = 70;
    private const string HOST = 'phase3.kongono.test';

    private string $binary = '';
    private string $root = '';
    private string $production = '';
    private string $development = '';
    private int $caseCount = 0;
    private int $requestCount = 0;
    private int $processCount = 0;
    private int $reusedRequestCount = 0;
    private int $journalRecords = 0;
    private int $disclosureFailures = 0;
    private int $orphanProcesses = 0;

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        if (!array_is_list($arguments) || 1 !== count($arguments) || !is_string($arguments[0])) {
            $this->write($stderr, "Invalid Phase 3 PHP-CGI acceptance invocation\n");

            return self::INVALID_INVOCATION;
        }
        $this->binary = $arguments[0];
        $root = realpath(dirname(__DIR__, 3));
        if (false === $root) {
            $this->write($stderr, "Phase 3 PHP-CGI acceptance failed: repository_root_unavailable\n");

            return self::ACCEPTANCE_FAILURE;
        }
        $this->root = $root;
        $this->production = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'External'
            . DIRECTORY_SEPARATOR . 'PhpCgi' . DIRECTORY_SEPARATOR . 'Fixture' . DIRECTORY_SEPARATOR . 'production.php';
        $this->development = dirname($this->production) . DIRECTORY_SEPARATOR . 'development.php';

        try {
            $this->runMatrix();
            $line = json_encode([
                'result' => 'pass',
                'profile' => 'cgi-fcgi-responder',
                'php_minor' => '8.5',
                'case_count' => $this->caseCount,
                'request_count' => $this->requestCount,
                'process_count' => $this->processCount,
                'reused_request_count' => $this->reusedRequestCount,
                'journal_records' => $this->journalRecords,
                'active_operations' => 0,
                'disclosure_failures' => $this->disclosureFailures,
                'orphan_processes' => $this->orphanProcesses,
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->write($stdout, $line . "\n");

            return 0;
        } catch (Throwable $failure) {
            $token = $failure->getMessage();
            if (1 !== preg_match('/^[a-z0-9_]+$/D', $token)) {
                $token = 'unexpected_failure';
            }
            $this->write($stderr, 'Phase 3 PHP-CGI acceptance failed: ' . $token . "\n");

            return str_starts_with($token, 'host_capability_') ? self::HOST_UNAVAILABLE : self::ACCEPTANCE_FAILURE;
        }
    }

    private function runMatrix(): void
    {
        $this->runCliRejection();
        $this->runProductionRequestMatrix();
        $this->runEarlyEofProof();
        $this->runDevelopmentTrustMatrix();
        $this->runCorrectedBoundaryMatrix();
        $this->runResponseMatrix();
        $this->runIniRejections();
        $this->runReuseProof();
        $this->runIndependentProcessProof();
        $this->runUncertainRetirementProof();
        if (0 !== $this->disclosureFailures || 0 !== $this->orphanProcesses) {
            throw new RuntimeException('final_cleanup_or_disclosure_failed');
        }
    }

    private function runCliRejection(): void
    {
        ++$this->caseCount;
        ++$this->processCount;
        PhpCgiProcess::proveCliRejection($this->root, $this->production);
    }

    private function runProductionRequestMatrix(): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            $matched = $this->request($client, $this->parameters($this->production, '/hello/alpha'));
            $this->expectStatus($matched, 200, "Kongono Phase 2 fixture\n");
            $unmatched = $this->request($client, $this->parameters($this->production, '/missing'));
            $this->expectStatus($unmatched, 404, "Not Found\n");
            $generic = $this->request($client, $this->parameters($this->production, '/fail/generic'));
            $this->expectGeneric($generic);
            $diagnostic = $this->request($client, $this->parameters($this->production, '/fail/diagnostic'));
            $this->expectGeneric($diagnostic);

            foreach ($this->requestCases() as $token => $case) {
                $response = $this->request($client, $case['parameters'], $case['body']);
                if ($case['accepted']) {
                    if (500 === $response->statusCode) {
                        throw new RuntimeException($token);
                    }
                    $this->assertResponseEnvelope($response);
                } else {
                    $this->expectGeneric($response, $token);
                }
            }
            $this->collectJournal($process);
        } finally {
            $client?->close();
            $this->retire($process);
        }
    }

    private function runDevelopmentTrustMatrix(): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            $trusted = $this->request($client, $this->parameters($this->development, '/fail/diagnostic'));
            if (500 !== $trusted->statusCode || !str_contains($trusted->body, 'KGN-KERNEL-9001')) {
                throw new RuntimeException('development_trusted_diagnostic');
            }
            foreach ([
                'forwarded_denied' => ['HTTP_FORWARDED' => 'for=127.0.0.1'],
                'x_forwarded_for_denied' => ['HTTP_X_FORWARDED_FOR' => '127.0.0.1'],
                'x_forwarded_other_denied' => ['HTTP_X_FORWARDED_PROTO' => 'https'],
                'nonloopback_denied' => ['REMOTE_ADDR' => '192.0.2.10'],
            ] as $token => $changes) {
                $parameters = array_replace($this->parameters($this->development, '/fail/diagnostic'), $changes);
                $this->expectGeneric($this->request($client, $parameters), $token);
            }
            $this->collectJournal($process);
        } finally {
            $client?->close();
            $this->retire($process);
        }
    }

    private function runEarlyEofProof(): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            $parameters = array_replace($this->parameters($this->production, '/missing'), ['CONTENT_LENGTH' => '2']);
            ++$this->caseCount;
            ++$this->requestCount;
            $client->submitAndDisconnect($parameters, 'x');
        } finally {
            $client?->close();
            $this->retire($process);
        }
    }

    private function runCorrectedBoundaryMatrix(): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            foreach ($this->boundaryCases() as $token => $case) {
                $response = $this->request($client, $case['parameters'], $case['body']);
                if ($case['accepted']) {
                    if (500 === $response->statusCode) {
                        throw new RuntimeException($token);
                    }
                    $this->assertResponseEnvelope($response);
                } else {
                    $this->expectGeneric($response, $token);
                }
            }
            $this->collectJournal($process);
        } finally {
            $client?->close();
            $this->retire($process);
        }
    }

    private function runResponseMatrix(): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            foreach ([
                'status-200' => 200,
                'status-599' => 599,
                'protocol-1-0' => 200,
                'protocol-1-1' => 200,
                'protocol-2' => 200,
                'ordered-fields' => 200,
                'fields-127' => 200,
                'fields-128' => 200,
                'values-31' => 200,
                'values-32' => 200,
                'value-8191' => 200,
                'value-8192' => 200,
                'total-65535' => 200,
                'total-65536' => 200,
                'body-1048575' => 200,
                'body-1048576' => 200,
            ] as $case => $status) {
                $response = $this->request($client, $this->parameters($this->production, '/response/' . $case));
                if ($status !== $response->statusCode) {
                    throw new RuntimeException('response_' . str_replace('-', '_', $case));
                }
                $this->assertResponseEnvelope($response);
            }
            foreach (['status-600', 'protocol-invalid'] as $case) {
                $this->expectGeneric(
                    $this->request($client, $this->parameters($this->production, '/response/' . $case)),
                    'response_' . str_replace('-', '_', $case),
                );
            }
            $head = $this->request($client, $this->parameters($this->production, '/response/body', method: 'HEAD'));
            if (200 !== $head->statusCode || '' !== $head->body || ['17'] !== $this->field($head, 'Content-Length')) {
                throw new RuntimeException('response_head_preflight');
            }
            $this->collectJournal($process);
        } finally {
            $client?->close();
            $this->retire($process);
        }

        foreach (['status-199', 'forbidden-field', 'fields-129', 'values-33', 'value-8193', 'total-65537', 'body-1048577'] as $case) {
            $this->runPrecommitFailure($case);
        }
    }

    private function runPrecommitFailure(string $case): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            ++$this->caseCount;
            ++$this->requestCount;
            try {
                $client->requestExpectingHostFailureEnvelope($this->parameters($this->production, '/response/' . $case));
            } catch (RuntimeException $failure) {
                if ('fastcgi_host_failure_envelope_invalid' === $failure->getMessage()) {
                    throw new RuntimeException('precommit_' . str_replace('-', '_', $case));
                }
                throw $failure;
            }
            $this->collectJournal($process);
        } finally {
            $client?->close();
            $this->retire($process);
        }
    }

    private function runIniRejections(): void
    {
        $violations = [
            'enable_post_data_reading' => '1',
            'output_buffering' => '4096',
            'zlib.output_compression' => '1',
            'display_errors' => '1',
            'display_startup_errors' => '1',
            'html_errors' => '1',
            'expose_php' => '1',
            'auto_prepend_file' => 'phase3-invalid',
            'auto_append_file' => 'phase3-invalid',
            'cgi.rfc2616_headers' => '1',
        ];
        foreach ($violations as $name => $value) {
            $process = $this->start([$name => $value]);
            $client = null;
            try {
                $client = new FastCgiClient($process->port());
                ++$this->caseCount;
                ++$this->requestCount;
                $client->submitAndDisconnect($this->parameters($this->production, '/hello/ini'), '');
                if ('auto_prepend_file' === $name) {
                    $deadline = microtime(true) + 0.5;
                    do {
                        if ([] !== $process->journalRecords()) {
                            throw new RuntimeException('ini_prepend_application_executed');
                        }
                        usleep(50_000);
                    } while (microtime(true) < $deadline);
                    if (!$process->isRunning()) {
                        throw new RuntimeException('ini_prepend_listener_stopped');
                    }
                    continue;
                }
                $records = $this->waitForJournalRecords($process, 1);
                $record = $records[0];
                if ('failure' !== $record['outcome'] || 'incomplete' !== $record['shutdown'] || 0 !== $record['active_operations']) {
                    throw new RuntimeException('ini_rejection_invalid');
                }
                $this->collectJournal($process);
            } finally {
                $client?->close();
                $this->retire($process);
            }
        }
    }

    private function runReuseProof(): void
    {
        $process = $this->start();
        $client = null;
        try {
            $client = new FastCgiClient($process->port());
            $responses = [];
            foreach (['/hello/alpha', '/missing', '/hello/alpha', '/fail/generic', '/hello/alpha'] as $target) {
                $responses[] = $this->request($client, $this->parameters($this->production, $target), keepConnection: true);
                ++$this->reusedRequestCount;
            }
            if (serialize($responses[0]) !== serialize($responses[2]) || serialize($responses[0]) !== serialize($responses[4])) {
                throw new RuntimeException('process_reuse_state_leakage');
            }
            $this->expectGeneric($responses[3], 'process_reuse_generic');
            $records = $process->journalRecords();
            if (5 !== count($records)) {
                throw new RuntimeException('process_reuse_journal_count');
            }
            $this->collectJournal($process);
        } finally {
            $client?->close();
            $this->retire($process);
        }
    }

    private function runIndependentProcessProof(): void
    {
        $serialized = [];
        for ($attempt = 0; $attempt < 2; ++$attempt) {
            $process = $this->start();
            $client = null;
            try {
                $client = new FastCgiClient($process->port());
                $responses = [
                    $this->request($client, $this->parameters($this->production, '/hello/alpha')),
                    $this->request($client, $this->parameters($this->production, '/missing')),
                    $this->request($client, $this->parameters($this->production, '/fail/generic')),
                ];
                $serialized[] = serialize($responses);
                $this->collectJournal($process);
            } finally {
                $client?->close();
                $this->retire($process);
            }
        }
        ++$this->caseCount;
        if ($serialized[0] !== $serialized[1]) {
            throw new RuntimeException('independent_process_nondeterministic');
        }
    }

    private function runUncertainRetirementProof(): void
    {
        $unsafe = $this->start();
        $unsafePid = $unsafe->pid();
        $client = null;
        try {
            $client = new FastCgiClient($unsafe->port());
            ++$this->caseCount;
            ++$this->requestCount;
            $client->submitAndDisconnect($this->parameters($this->production, '/response/body-1048576'), '');
        } finally {
            $client?->close();
            $this->retire($unsafe);
        }
        $replacement = $this->start();
        $client = null;
        try {
            if ($unsafePid === $replacement->pid()) {
                throw new RuntimeException('retirement_pid_not_replaced');
            }
            $client = new FastCgiClient($replacement->port());
            $this->expectStatus(
                $this->request($client, $this->parameters($this->production, '/hello/replacement')),
                200,
                "Kongono Phase 2 fixture\n",
            );
            $this->collectJournal($replacement);
        } finally {
            $client?->close();
            $this->retire($replacement);
        }
    }

    /**
     * @return array<string, array{parameters: array<string, string>, body: string, accepted: bool}>
     */
    private function requestCases(): array
    {
        $base = $this->parameters($this->production, '/missing');
        $cases = [];
        foreach (['HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3', 'HTTP/3.0'] as $protocol) {
            $cases['protocol_' . str_replace(['/', '.'], '_', $protocol)] = ['parameters' => array_replace($base, ['SERVER_PROTOCOL' => $protocol]), 'body' => '', 'accepted' => true];
        }
        foreach (['', 'HTTP/1.2', 'CGI/1.1', 'http/1.1'] as $index => $protocol) {
            $cases['protocol_rejected_' . $index] = ['parameters' => array_replace($base, ['SERVER_PROTOCOL' => $protocol]), 'body' => '', 'accepted' => false];
        }
        $mutations = [
            'gateway_missing' => ['GATEWAY_INTERFACE' => null],
            'gateway_wrong' => ['GATEWAY_INTERFACE' => 'CGI/1.0'],
            'gateway_malformed' => ['GATEWAY_INTERFACE' => 'CGI/1.1 '],
            'role_wrong' => ['FCGI_ROLE' => 'AUTHORIZER'],
            'role_malformed' => ['FCGI_ROLE' => 'RESPONDER '],
            'method_lowercase' => ['REQUEST_METHOD' => 'get'],
            'method_invalid' => ['REQUEST_METHOD' => 'GET '],
            'target_absolute' => ['REQUEST_URI' => 'https://phase3.kongono.test/missing'],
            'target_authority' => ['REQUEST_URI' => 'phase3.kongono.test:443'],
            'target_asterisk' => ['REQUEST_URI' => '*'],
            'target_fragment' => ['REQUEST_URI' => '/missing#secret'],
            'target_space' => ['REQUEST_URI' => '/mis sing'],
            'target_control' => ['REQUEST_URI' => "/mis\x01sing"],
            'target_percent' => ['REQUEST_URI' => '/missing%Q0'],
            'query_mismatch' => ['REQUEST_URI' => '/missing?a=b', 'QUERY_STRING' => 'a=c'],
            'https_invalid' => ['HTTPS' => 'yes'],
            'host_missing' => ['HTTP_HOST' => null],
            'host_invalid' => ['HTTP_HOST' => 'user@phase3.kongono.test'],
            'content_type_ambiguous' => ['CONTENT_TYPE' => 'text/plain', 'HTTP_CONTENT_TYPE' => 'text/plain'],
            'content_length_ambiguous' => ['CONTENT_LENGTH' => '0', 'HTTP_CONTENT_LENGTH' => '0'],
            'transfer_encoding' => ['HTTP_TRANSFER_ENCODING' => 'chunked'],
            'header_cr' => ['HTTP_X_PHASE3' => "phase3-secret\rvalue"],
            'header_lf' => ['HTTP_X_PHASE3' => "phase3-secret\nvalue"],
            'query_empty_field' => ['REQUEST_URI' => '/missing?a=b&', 'QUERY_STRING' => 'a=b&'],
            'query_nested' => ['REQUEST_URI' => '/missing?a[]=b', 'QUERY_STRING' => 'a[]=b'],
            'query_invalid_utf8' => ['REQUEST_URI' => "/missing?a=%FF", 'QUERY_STRING' => 'a=%FF'],
            'query_control' => ['REQUEST_URI' => '/missing?a=%01', 'QUERY_STRING' => 'a=%01'],
            'query_duplicate' => ['REQUEST_URI' => '/missing?a=b&a=c', 'QUERY_STRING' => 'a=b&a=c'],
            'cookie_missing_equals' => ['HTTP_COOKIE' => 'phase3-secret-cookie'],
            'cookie_invalid_name' => ['HTTP_COOKIE' => 'bad name=value'],
            'cookie_forbidden_value' => ['HTTP_COOKIE' => 'name=bad,value'],
            'cookie_duplicate' => ['HTTP_COOKIE' => 'name=one; name=two'],
            'content_length_sign' => ['CONTENT_LENGTH' => '+1'],
            'content_length_space' => ['CONTENT_LENGTH' => ' 1'],
            'content_length_zero' => ['CONTENT_LENGTH' => '01'],
            'multipart' => ['CONTENT_TYPE' => 'multipart/form-data; boundary=x'],
        ];
        foreach ($mutations as $token => $changes) {
            $parameters = $base;
            foreach ($changes as $name => $value) {
                if (null === $value) {
                    unset($parameters[$name]);
                } else {
                    $parameters[$name] = $value;
                }
            }
            $cases[$token] = ['parameters' => $parameters, 'body' => '', 'accepted' => false];
        }
        $roleMissing = $base;
        unset($roleMissing['FCGI_ROLE']);
        $cases['role_missing'] = ['parameters' => $roleMissing, 'body' => '', 'accepted' => true];
        foreach ([
            'target_nul' => ['REQUEST_URI' => "/mis\0sing"],
            'proxy_forbidden' => ['HTTP_PROXY' => 'phase3-secret-proxy'],
            'header_nul' => ['HTTP_X_PHASE3' => "phase3-secret\0value"],
            'method_uppercase' => ['REQUEST_METHOD' => 'CUSTOM'],
            'https_on' => ['HTTPS' => 'on'],
            'https_off' => ['HTTPS' => 'off'],
            'ordinary_header' => ['HTTP_X_PHASE3' => 'ordinary'],
            'query_plus' => ['REQUEST_URI' => '/missing?a=hello+world', 'QUERY_STRING' => 'a=hello+world'],
            'query_percent' => ['REQUEST_URI' => '/missing?a=%76', 'QUERY_STRING' => 'a=%76'],
            'cookie_space' => ['HTTP_COOKIE' => 'a=one; b=two'],
            'urlencoded_raw' => ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
        ] as $token => $changes) {
            $cases[$token] = ['parameters' => array_replace($base, $changes), 'body' => '', 'accepted' => true];
        }
        $extra = array_replace($base, ['CONTENT_LENGTH' => '1']);
        $cases['body_extra_unread'] = ['parameters' => $extra, 'body' => 'xy', 'accepted' => true];

        return $cases;
    }

    /**
     * @return array<string, array{parameters: array<string, string>, body: string, accepted: bool}>
     */
    private function boundaryCases(): array
    {
        $cases = [];
        foreach ([31 => true, 32 => true, 33 => false] as $length => $accepted) {
            $cases['method_' . $length] = ['parameters' => $this->parameters($this->production, '/missing', str_repeat('M', $length)), 'body' => '', 'accepted' => $accepted];
        }
        foreach ([8191 => true, 8192 => true, 8193 => false] as $length => $accepted) {
            $target = self::originTargetLength($length);
            $cases['target_' . $length] = ['parameters' => $this->parameters($this->production, $target), 'body' => '', 'accepted' => $accepted];
        }
        foreach ([8189 => true, 8190 => true, 8191 => false] as $length => $accepted) {
            $query = 'a=' . str_repeat('v', 4096) . '&b=' . str_repeat('v', $length - 4101);
            if ($length !== strlen($query)) {
                throw new RuntimeException('effective_query_construction_invalid');
            }
            $cases['effective_query_' . $length] = ['parameters' => $this->parameters($this->production, '/?' . $query, query: $query), 'body' => '', 'accepted' => $accepted];
        }
        foreach ([127 => true, 128 => true, 129 => false] as $count => $accepted) {
            $pairs = [];
            for ($index = 0; $index < $count; ++$index) {
                $pairs[] = 'k' . $index . '=v';
            }
            $query = implode('&', $pairs);
            $cases['query_pairs_' . $count] = ['parameters' => $this->parameters($this->production, '/missing?' . $query, query: $query), 'body' => '', 'accepted' => $accepted];
        }
        foreach ([127 => true, 128 => true, 129 => false] as $length => $accepted) {
            $query = str_repeat('k', $length) . '=v';
            $cases['query_key_' . $length] = ['parameters' => $this->parameters($this->production, '/missing?' . $query, query: $query), 'body' => '', 'accepted' => $accepted];
        }
        foreach ([4095 => true, 4096 => true, 4097 => false] as $length => $accepted) {
            $query = 'k=' . str_repeat('v', $length);
            $cases['query_value_' . $length] = ['parameters' => $this->parameters($this->production, '/missing?' . $query, query: $query), 'body' => '', 'accepted' => $accepted];
        }
        foreach ([4095 => true, 4096 => true, 4097 => false] as $length => $accepted) {
            $parameters = $this->parameters($this->production, '/missing');
            $parameters['HTTP_COOKIE'] = self::cookieLength($length);
            $cases['cookie_raw_' . $length] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([63 => true, 64 => true, 65 => false] as $count => $accepted) {
            $pairs = [];
            for ($index = 0; $index < $count; ++$index) {
                $pairs[] = 'k' . $index . '=v';
            }
            $parameters = $this->parameters($this->production, '/missing');
            $parameters['HTTP_COOKIE'] = implode(';', $pairs);
            $cases['cookie_pairs_' . $count] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([127 => true, 128 => true, 129 => false] as $length => $accepted) {
            $parameters = $this->parameters($this->production, '/missing');
            $parameters['HTTP_COOKIE'] = str_repeat('k', $length) . '=v';
            $cases['cookie_name_' . $length] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([1023 => true, 1024 => true, 1025 => false] as $length => $accepted) {
            $parameters = $this->parameters($this->production, '/missing');
            $parameters['HTTP_COOKIE'] = 'k=' . str_repeat('v', $length);
            $cases['cookie_value_' . $length] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([127 => true, 128 => true, 129 => false] as $count => $accepted) {
            $parameters = $this->parameters($this->production, '/missing');
            for ($index = 0; $index < $count - 1; ++$index) {
                $parameters['HTTP_X_PHASE3_' . sprintf('%03d', $index)] = 'v';
            }
            $cases['request_fields_' . $count] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([8191 => true, 8192 => true, 8193 => false] as $length => $accepted) {
            $parameters = $this->parameters($this->production, '/missing');
            $parameters['HTTP_X_PHASE3'] = str_repeat('v', $length);
            $cases['request_value_' . $length] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([65535 => true, 65536 => true, 65537 => false] as $bytes => $accepted) {
            $parameters = $this->requestFieldBytes($bytes);
            $cases['request_total_' . $bytes] = ['parameters' => $parameters, 'body' => '', 'accepted' => $accepted];
        }
        foreach ([1_048_575 => true, 1_048_576 => true, 1_048_577 => false] as $bytes => $accepted) {
            $body = str_repeat('b', $bytes);
            $parameters = $this->parameters($this->production, '/missing', body: $body);
            $cases['request_body_' . $bytes] = ['parameters' => $parameters, 'body' => $body, 'accepted' => $accepted];
        }

        return $cases;
    }

    /** @param array<string, string> $iniOverrides */
    private function start(array $iniOverrides = []): PhpCgiProcess
    {
        ++$this->processCount;

        return PhpCgiProcess::start($this->binary, $this->root, $iniOverrides);
    }

    private function retire(PhpCgiProcess $process): void
    {
        $process->retire();
        if ($process->isRunning()) {
            ++$this->orphanProcesses;
            throw new RuntimeException('orphan_process_detected');
        }
    }

    /** @param array<string, string> $parameters */
    private function request(
        FastCgiClient $client,
        array $parameters,
        string $body = '',
        bool $keepConnection = false,
    ): FastCgiResponse {
        ++$this->caseCount;
        ++$this->requestCount;
        $response = $client->request($parameters, $body, $keepConnection);
        $this->assertDisclosure($response);

        return $response;
    }

    private function collectJournal(PhpCgiProcess $process): void
    {
        foreach ($process->journalRecords() as $record) {
            ++$this->journalRecords;
            if (0 !== $record['active_operations']) {
                throw new RuntimeException('journal_active_operations_nonzero');
            }
            if ('complete' === $record['outcome'] && 'complete' !== $record['shutdown']) {
                throw new RuntimeException('journal_shutdown_incomplete');
            }
        }
    }

    /** @return list<array{profile: string, outcome: string, shutdown: string, active_operations: int}> */
    private function waitForJournalRecords(PhpCgiProcess $process, int $expected): array
    {
        $deadline = microtime(true) + 5.0;
        do {
            $records = $process->journalRecords();
            if (count($records) >= $expected) {
                return $records;
            }
            usleep(50_000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('journal_record_timeout');
    }

    private function expectStatus(FastCgiResponse $response, int $status, string $body): void
    {
        if ($status !== $response->statusCode || $body !== $response->body) {
            throw new RuntimeException('response_status_or_body_invalid');
        }
        $this->assertResponseEnvelope($response);
    }

    private function expectGeneric(FastCgiResponse $response, string $token = 'generic_response_invalid'): void
    {
        if (500 !== $response->statusCode || "Internal Server Error\n" !== $response->body) {
            throw new RuntimeException($token);
        }
        $this->assertResponseEnvelope($response);
    }

    private function assertResponseEnvelope(FastCgiResponse $response): void
    {
        if ('' !== $response->stderr || 0 !== $response->applicationStatus || 0 !== $response->protocolStatus) {
            throw new RuntimeException('fastcgi_response_envelope_invalid');
        }
        $lengths = $this->field($response, 'Content-Length');
        if (1 !== count($lengths) || (string) strlen($response->body) !== $lengths[0]) {
            throw new RuntimeException('response_content_length_invalid');
        }
    }

    private function assertDisclosure(FastCgiResponse $response): void
    {
        $serialized = serialize($response);
        foreach ([
            'phase3-secret-proxy',
            'phase3-secret-cookie',
            'fixture-generic-failure-sentinel',
            'C:\\Users\\',
            $this->binary,
            'KONGONO_PHASE3_ACCEPTANCE_JOURNAL',
        ] as $forbidden) {
            if ('' !== $forbidden && str_contains($serialized, $forbidden)) {
                ++$this->disclosureFailures;
                throw new RuntimeException('disclosure_sentinel_exposed');
            }
        }
    }

    /** @return list<string> */
    private function field(FastCgiResponse $response, string $name): array
    {
        foreach ($response->fields as $fieldName => $values) {
            if (0 === strcasecmp($fieldName, $name)) {
                return $values;
            }
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function parameters(
        string $script,
        string $target,
        string $method = 'GET',
        string $query = '',
        string $body = '',
    ): array {
        $parameters = [
            'SCRIPT_FILENAME' => $script,
            'SCRIPT_NAME' => '/phase3.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'FCGI_ROLE' => 'RESPONDER',
            'REQUEST_METHOD' => $method,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_URI' => $target,
            'QUERY_STRING' => $query,
            'HTTP_HOST' => self::HOST,
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => '49152',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => self::HOST,
            'SERVER_PORT' => '80',
            'SERVER_SOFTWARE' => 'Kongono-Phase3-Acceptance',
            'HTTPS' => 'off',
        ];
        if ('' !== $body) {
            $parameters['CONTENT_LENGTH'] = (string) strlen($body);
        }

        return $parameters;
    }

    /** @return array<string, string> */
    private function requestFieldBytes(int $target): array
    {
        $parameters = $this->parameters($this->production, '/missing');
        $remaining = $target - strlen('Host') - strlen(self::HOST);
        for ($index = 0; $remaining > 0; ++$index) {
            $parameter = 'HTTP_X_PAD_' . sprintf('%03d', $index);
            $projected = 'X-Pad-' . sprintf('%03d', $index);
            $valueLength = min(8192, $remaining - strlen($projected));
            if ($valueLength < 0) {
                throw new RuntimeException('request_field_total_construction_invalid');
            }
            $parameters[$parameter] = str_repeat('v', $valueLength);
            $remaining -= strlen($projected) + $valueLength;
        }

        return $parameters;
    }

    private static function cookieLength(int $target): string
    {
        $cookie = '';
        for ($index = 0; strlen($cookie) < $target; ++$index) {
            $prefix = '' === $cookie ? '' : ';';
            $name = 'k' . $index;
            $available = $target - strlen($cookie) - strlen($prefix) - strlen($name) - 1;
            $valueLength = min(1024, max(0, $available));
            $cookie .= $prefix . $name . '=' . str_repeat('v', $valueLength);
        }
        if (strlen($cookie) !== $target) {
            throw new RuntimeException('cookie_boundary_construction_invalid');
        }

        return $cookie;
    }

    private static function originTargetLength(int $length): string
    {
        if ($length < 1) {
            throw new RuntimeException('target_length_invalid');
        }
        $target = '';
        while (strlen($target) < $length) {
            $remaining = $length - strlen($target);
            if (1 === $remaining) {
                $target .= '/';
                continue;
            }
            $segmentLength = min(1024, $remaining - 1);
            $target .= '/' . str_repeat('a', $segmentLength);
        }

        return $target;
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $contents): void
    {
        for ($offset = 0, $length = strlen($contents); $offset < $length;) {
            $written = fwrite($stream, substr($contents, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('runner_output_failed');
            }
            $offset += $written;
        }
    }
}
