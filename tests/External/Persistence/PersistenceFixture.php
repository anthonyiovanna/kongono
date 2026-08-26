<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Persistence;

use FilesystemIterator;
use Kongono\DatabaseApplication\Workflow\DataAccessBuildWorkflow;
use Kongono\DatabasePdoPgsql\PdoPgsqlConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConnector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

/** Owns the private PostgreSQL 18.6 process and temporary application evidence. */
final class PersistenceFixture
{
    private const PREFIX = 'kongono-phase7h-';
    private const ROLE = 'kongono_phase7h_acceptance';
    private const MAX_OUTPUT = 1_048_576;
    /** @var array<string, array{int,string}> */
    private const BINARY_IDENTITIES = [
        'postgres.exe' => [10_266_624, 'AF5B897CB69C9CE692A4A15ECD022B540DB85DB1ADD0F66D2B9F0697BE2451A0'],
        'initdb.exe' => [246_784, '68195F0C6F22694660BA86D914AE8C74BCD38E71EB342F98E065B1962311142E'],
        'pg_ctl.exe' => [133_120, '552049183DF455921657C8E498E9745E8508BF77D2C2E5CB9C21B2CBDC798822'],
        'libpq.dll' => [384_512, '3EA95C110FA0C24BFA52206BC6CDB3C23B9AE70C6416E67E22AC1D6ED5EA8690'],
    ];

    private ?string $workspace = null;
    private ?string $data = null;
    private ?string $bin = null;
    private ?int $port = null;
    private bool $started = false;

    public function start(string $binaryDirectory): void
    {
        $this->bin = $this->validateBinaries($binaryDirectory);
        $this->workspace = $this->createWorkspace();
        $this->data = $this->workspace . '/data';
        $this->process([$this->executable('initdb.exe'), '-D', $this->data, '--auth=trust', '--no-sync', '--no-locale', '--username=postgres'], true);
        $port = $this->availablePort();
        $this->port = $port;
        if ($this->listening($port)) {
            throw new RuntimeException('selected_port_in_use');
        }
        $this->startProvider();
        if (!$this->listening($port)) {
            throw new RuntimeException('provider_not_listening');
        }
        $this->process([
            $this->executable('psql.exe'), '-h', '127.0.0.1', '-p', (string) $this->port,
            '-U', 'postgres', '-d', 'postgres', '-v', 'ON_ERROR_STOP=1', '-c',
            'CREATE ROLE ' . self::ROLE . ' LOGIN; GRANT CREATE ON SCHEMA public TO ' . self::ROLE,
        ], true);
    }

    public function connector(): PdoPgsqlConnector
    {
        if (null === $this->port) {
            throw new RuntimeException('provider_port_unavailable');
        }

        return new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', $this->port, 'postgres', self::ROLE, ''));
    }

    /**
     * @return array{
     *   case_count:int,
     *   performance:array<string, array{samples:int,min_ns:int,p50_ns:int,p95_ns:int,max_ns:int}>
     * }
     */
    public function runRuntimeChild(string $application): array
    {
        if (null === $this->workspace || null === $this->port) {
            throw new RuntimeException('runtime_child_state_unavailable');
        }
        $host = $this->workspace . '/persistence-runtime-host.php';
        $this->writeTemporary($host, $this->runtimeHostBytes());
        $repository = str_replace('\\', '/', dirname(__DIR__, 3));
        try {
            $result = $this->process([
                PHP_BINARY,
                $host,
                $application,
                (string) $this->port,
                $repository,
            ], false, $application);
        } finally {
            if (is_file($host) && !unlink($host)) {
                throw new RuntimeException('runtime_host_cleanup_failed');
            }
        }
        if (0 !== $result['exit']) {
            $failure = trim($result['stderr']);
            $token = 1 === preg_match('/^persistence_runtime_failed_([a-z0-9_]+)$/D', $failure, $match)
                ? $match[1]
                : 'failed';
            throw new RuntimeException('runtime_child_' . $token);
        }
        if ('' !== $result['stderr'] || !str_ends_with($result['stdout'], "\n") || str_contains(rtrim($result['stdout'], "\n"), "\n")) {
            throw new RuntimeException('runtime_child_output_invalid');
        }
        $decoded = json_decode($result['stdout'], true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || ['case_count', 'performance'] !== array_keys($decoded) || !is_int($decoded['case_count'] ?? null) || !is_array($decoded['performance'] ?? null)) {
            throw new RuntimeException('runtime_child_output_invalid');
        }
        $performance = [];
        foreach ($decoded['performance'] as $name => $summary) {
            $samples = is_array($summary) ? ($summary['samples'] ?? null) : null;
            $minimum = is_array($summary) ? ($summary['min_ns'] ?? null) : null;
            $median = is_array($summary) ? ($summary['p50_ns'] ?? null) : null;
            $ninetyFifth = is_array($summary) ? ($summary['p95_ns'] ?? null) : null;
            $maximum = is_array($summary) ? ($summary['max_ns'] ?? null) : null;
            if (
                !is_string($name)
                || !is_array($summary)
                || ['samples', 'min_ns', 'p50_ns', 'p95_ns', 'max_ns'] !== array_keys($summary)
                || !is_int($samples) || !is_int($minimum) || !is_int($median) || !is_int($ninetyFifth) || !is_int($maximum)
                || $samples < 1 || $minimum < 1 || $median < 1 || $ninetyFifth < 1 || $maximum < 1
            ) {
                throw new RuntimeException('runtime_child_output_invalid');
            }
            $performance[$name] = [
                'samples' => $samples,
                'min_ns' => $minimum,
                'p50_ns' => $median,
                'p95_ns' => $ninetyFifth,
                'max_ns' => $maximum,
            ];
        }

        return ['case_count' => $decoded['case_count'], 'performance' => $performance];
    }

    /**
     * @return array{
     *   application:string,
     *   identities:array{mapping:string,data:string,persistence:string,state:string,repositories:string,composition:string,wiring:string},
     *   outputs:array{mapping:string,data:string,persistence:string,state:string,repositories:string,composition:string,wiring:string}
     * }
     */
    public function prepareApplication(): array
    {
        if (null === $this->workspace) {
            throw new RuntimeException('workspace_unavailable');
        }
        $application = $this->workspace . '/skeletons/persistence-minimal';
        $packages = $this->workspace . '/packages';
        $this->copySkeleton($application);
        $this->mirrorPackages($application, $packages);
        $install = $this->process(
            [...$this->composerCommand(), 'install', '--no-interaction', '--no-ansi', '--no-plugins'],
            false,
            $application,
            ['COMPOSER_DISABLE_NETWORK' => '1'],
        );
        if (0 !== $install['exit'] || !is_file($application . '/vendor/autoload.php')) {
            throw new RuntimeException('application_install_failed');
        }

        $build = $this->composerScript($application, 'app:build', false);
        if (0 !== $build['exit']) {
            $diagnostic = 1 === preg_match('/Persistence application build failed: ([a-z0-9_]+)\./D', trim($build['stderr']), $match)
                ? $match[1]
                : 'failed';
            throw new RuntimeException('application_build_' . $diagnostic);
        }
        $identities = $this->buildIdentities($build['stdout']);
        $buildDirectory = $application . '/var/kongono/build';
        $before = $this->directoryIdentity($buildDirectory);
        $check = $this->composerScript($application, 'app:check', false);
        if (0 !== $check['exit']) {
            throw new RuntimeException('application_check_failed');
        }
        if ($identities !== $this->buildIdentities($check['stdout']) || $before !== $this->directoryIdentity($buildDirectory)) {
            throw new RuntimeException('application_check_changed_state');
        }
        $repeat = $this->composerScript($application, 'app:build', false);
        if (0 !== $repeat['exit']) {
            throw new RuntimeException('application_repeat_build_failed');
        }
        if ($build['stdout'] !== $repeat['stdout'] || $identities !== $this->buildIdentities($repeat['stdout'])) {
            throw new RuntimeException('application_build_not_deterministic');
        }

        $outputs = [
            'mapping' => $buildDirectory . '/kongono-data-mapping-v1-' . $identities['mapping'] . '.json',
            'data' => $buildDirectory . '/kongono-data-access-v1-' . $identities['data'] . '.php',
            'persistence' => $buildDirectory . '/persistence-artifacts/v1/kongono-persistence-v1-' . $identities['persistence'] . '.json',
            'state' => $buildDirectory . '/kongono-persistence-state-v1-' . $identities['state'] . '.php',
            'repositories' => $buildDirectory . '/kongono-persistence-repositories-v1-' . $identities['repositories'] . '.php',
            'composition' => $buildDirectory . '/kongono-composition-v1-' . $identities['composition'] . '.json',
            'wiring' => $buildDirectory . '/kongono-wiring-v1-' . $identities['wiring'] . '.php',
        ];
        foreach ($outputs as $path) {
            $this->readOrdinary($path);
        }
        $this->validateGeneratedPhp($application, $outputs);

        return ['application' => $application, 'identities' => $identities, 'outputs' => $outputs];
    }

    /**
     * @param array{mapping:string,data:string,persistence:string,state:string,repositories:string,composition:string,wiring:string} $outputs
     */
    public function proveEvidenceFailures(string $application, array $outputs): int
    {
        $cases = 0;
        foreach ($outputs as $name => $path) {
            $renamed = $path . '.renamed';
            if (!rename($path, $renamed)) {
                throw new RuntimeException('evidence_rename_failed');
            }
            $missing = $this->composerScript($application, 'app:check', false);
            if (0 === $missing['exit']) {
                throw new RuntimeException('missing_evidence_accepted_' . $name);
            }
            ++$cases;
            if (!rename($renamed, $path)) {
                throw new RuntimeException('evidence_restore_failed');
            }

            $bytes = $this->readOrdinary($path);
            $this->writeTemporary($path, $bytes . "\n");
            $tampered = $this->composerScript($application, 'app:check', false);
            if (0 === $tampered['exit']) {
                throw new RuntimeException('tampered_evidence_accepted_' . $name);
            }
            ++$cases;
            $this->writeTemporary($path, $bytes);
        }

        foreach ([['mapping', 'persistence'], ['data', 'state'], ['repositories', 'wiring']] as [$left, $right]) {
            $leftBytes = $this->readOrdinary($outputs[$left]);
            $rightBytes = $this->readOrdinary($outputs[$right]);
            $this->writeTemporary($outputs[$left], $rightBytes);
            $this->writeTemporary($outputs[$right], $leftBytes);
            if (0 === $this->composerScript($application, 'app:check', false)['exit']) {
                throw new RuntimeException('crossed_evidence_accepted');
            }
            ++$cases;
            $this->writeTemporary($outputs[$left], $leftBytes);
            $this->writeTemporary($outputs[$right], $rightBytes);
        }

        foreach (['kongono.data-mapping.json', 'kongono.persistence.json', 'kongono.persistence-repositories.json', 'kongono.composition.json'] as $source) {
            $path = $application . '/' . $source;
            $bytes = $this->readOrdinary($path);
            $this->writeTemporary($path, rtrim($bytes, "\n") . " \n");
            if (0 === $this->composerScript($application, 'app:check', false)['exit']) {
                throw new RuntimeException('stale_evidence_accepted');
            }
            ++$cases;
            $this->writeTemporary($path, "{\n");
            if (0 === $this->composerScript($application, 'app:check', false)['exit']) {
                throw new RuntimeException('malformed_source_accepted');
            }
            ++$cases;
            $this->writeTemporary($path, $bytes);
        }
        if (0 !== $this->composerScript($application, 'app:check', true)['exit']) {
            throw new RuntimeException('evidence_restore_failed');
        }

        return $cases;
    }

    public function proveChangedDuringRead(string $application): void
    {
        require_once $application . '/vendor/autoload.php';
        $workflow = new DataAccessBuildWorkflow();
        $prepared = $workflow->prepareCheck(
            $application,
            'kongono.data-mapping.json',
            $application . '/var/kongono/build',
        );
        $source = $application . '/kongono.data-mapping.json';
        $bytes = $this->readOrdinary($source);
        $this->writeTemporary($source, rtrim($bytes, "\n") . " \n");
        try {
            $workflow->loadPrepared($prepared);
        } catch (Throwable) {
            $this->writeTemporary($source, $bytes);

            return;
        }
        $this->writeTemporary($source, $bytes);

        throw new RuntimeException('changed_snapshot_accepted');
    }

    public function cleanup(): void
    {
        $failure = null;
        if ($this->started && null !== $this->data) {
            try {
                if (null !== $this->port) {
                    $this->process([
                        $this->executable('psql.exe'), '-h', '127.0.0.1', '-p', (string) $this->port,
                        '-U', 'postgres', '-d', 'postgres', '-v', 'ON_ERROR_STOP=1', '-c',
                        'DROP OWNED BY ' . self::ROLE . '; DROP ROLE IF EXISTS ' . self::ROLE,
                    ], false);
                }
                $stop = $this->process([$this->executable('pg_ctl.exe'), 'stop', '-D', $this->data, '-w', '-t', '30', '-m', 'fast'], false);
                if (0 !== $stop['exit']) {
                    $this->process([$this->executable('pg_ctl.exe'), 'stop', '-D', $this->data, '-w', '-t', '30', '-m', 'immediate'], true);
                }
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            $this->started = false;
        }
        if (null !== $this->port && $this->listening($this->port)) {
            $failure ??= new RuntimeException('provider_process_remains');
        }
        if (null !== $this->workspace && is_dir($this->workspace)) {
            try {
                $this->removeWorkspace($this->workspace);
            } catch (Throwable $exception) {
                $failure ??= $exception;
            }
        }
        if (null !== $this->workspace && file_exists($this->workspace)) {
            $failure ??= new RuntimeException('temporary_tree_remains');
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    private function startProvider(): void
    {
        if (null === $this->data || null === $this->port || null === $this->workspace) {
            throw new RuntimeException('provider_state_unavailable');
        }
        $command = [
            $this->executable('pg_ctl.exe'), 'start', '-D', $this->data, '-l', $this->workspace . '/postgres.log',
            '-w', '-t', '30', '-o', '-h 127.0.0.1 -p ' . $this->port . ' -c listen_addresses=127.0.0.1',
        ];
        $pipes = [];
        $process = proc_open(
            $command,
            [['pipe', 'r'], ['file', $this->workspace . '/pgctl.stdout', 'ab'], ['file', $this->workspace . '/pgctl.stderr', 'ab']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || !isset($pipes[0])) {
            throw new RuntimeException('provider_process_start_failed');
        }
        fclose($pipes[0]);
        $exit = $this->waitForProcess($process);
        if (0 !== $exit) {
            throw new RuntimeException('provider_process_failed');
        }
        $this->started = true;
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     * @return array{exit:int,stdout:string,stderr:string}
     */
    private function process(array $command, bool $requireSuccess, ?string $workingDirectory = null, array $environment = []): array
    {
        $pipes = [];
        $currentEnvironment = getenv();
        $process = proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            [] === $environment ? null : array_merge($currentEnvironment, $environment),
            ['bypass_shell' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process)) {
            throw new RuntimeException('process_start_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $observedExit = null;
        $deadline = microtime(true) + 120;
        while (true) {
            $this->drain($pipes[1], $stdout);
            $this->drain($pipes[2], $stderr);
            if (strlen($stdout) > self::MAX_OUTPUT || strlen($stderr) > self::MAX_OUTPUT) {
                proc_terminate($process);
                break;
            }
            $status = proc_get_status($process);
            if (!$status['running']) {
                $observedExit = $status['exitcode'] >= 0 ? $status['exitcode'] : null;
                break;
            }
            if (microtime(true) >= $deadline) {
                proc_terminate($process);
                break;
            }
            usleep(10_000);
        }
        $this->drain($pipes[1], $stdout);
        $this->drain($pipes[2], $stderr);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closedExit = proc_close($process);
        $exit = null !== $observedExit ? $observedExit : $closedExit;
        if (strlen($stdout) > self::MAX_OUTPUT || strlen($stderr) > self::MAX_OUTPUT || $requireSuccess && 0 !== $exit) {
            throw new RuntimeException('process_failed');
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /** @param resource $process */
    private function waitForProcess(mixed $process): int
    {
        $observed = null;
        $deadline = microtime(true) + 60;
        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $observed = $status['exitcode'];
                break;
            }
            usleep(10_000);
        }
        if (null === $observed) {
            proc_terminate($process);
        }
        $closed = proc_close($process);

        return null !== $observed && $observed >= 0 ? $observed : $closed;
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

    private function validateBinaries(string $directory): string
    {
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved) || is_link($directory)) {
            throw new RuntimeException('binary_directory_invalid');
        }
        $resolved = str_replace('\\', '/', $resolved);
        foreach (self::BINARY_IDENTITIES as $name => [$bytes, $sha256]) {
            $path = $resolved . '/' . $name;
            $stat = @lstat($path);
            $observedSha256 = hash_file('sha256', $path);
            if (false === $stat || false === $observedSha256 || is_link($path) || !is_file($path) || (($stat['mode'] & 0170000) !== 0100000) || $bytes !== $stat['size'] || !hash_equals($sha256, strtoupper($observedSha256))) {
                throw new RuntimeException('binary_identity_invalid');
            }
            if ('libpq.dll' === $name) {
                continue;
            }
            $version = $this->process([$path, '--version'], true);
            if (1 !== preg_match('/\b18\.6\b/', $version['stdout'])) {
                throw new RuntimeException('binary_version_invalid');
            }
        }
        foreach (['psql.exe'] as $name) {
            $path = $resolved . '/' . $name;
            if (!is_file($path) || is_link($path) || !is_executable($path)) {
                throw new RuntimeException('required_binary_invalid');
            }
        }

        return $resolved;
    }

    private function executable(string $name): string
    {
        if (null === $this->bin) {
            throw new RuntimeException('binary_directory_unavailable');
        }

        return $this->bin . '/' . $name;
    }

    private function createWorkspace(): string
    {
        $temporary = realpath(sys_get_temp_dir());
        $repository = realpath(dirname(__DIR__, 3));
        if (false === $temporary || false === $repository || is_link($temporary) || is_link($repository)) {
            throw new RuntimeException('temporary_root_invalid');
        }
        $candidate = str_replace('\\', '/', $temporary) . '/' . self::PREFIX . bin2hex(random_bytes(16));
        if (file_exists($candidate) || is_link($candidate) || !mkdir($candidate, 0700)) {
            throw new RuntimeException('temporary_create_failed');
        }
        $resolved = realpath($candidate);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $candidate || str_starts_with(strtolower($candidate) . '/', strtolower(str_replace('\\', '/', $repository)) . '/')) {
            throw new RuntimeException('temporary_identity_invalid');
        }

        return $candidate;
    }

    private function copySkeleton(string $application): void
    {
        $skeleton = str_replace('\\', '/', dirname(__DIR__, 3)) . '/skeletons/persistence-minimal';
        $manifest = json_decode($this->readOrdinary($skeleton . '/kongono.skeleton.json'), true, 16, JSON_THROW_ON_ERROR);
        $paths = is_array($manifest) ? ($manifest['template_paths'] ?? null) : null;
        if (!is_array($paths) || [] === $paths) {
            throw new RuntimeException('skeleton_manifest_invalid');
        }
        foreach ($paths as $relative) {
            if (!is_string($relative)) {
                throw new RuntimeException('skeleton_manifest_invalid');
            }
            $this->writeTemporary($application . '/' . $relative, $this->readOrdinary($skeleton . '/' . $relative));
        }
    }

    private function mirrorPackages(string $application, string $packages): void
    {
        $lock = json_decode($this->readOrdinary($application . '/composer.lock'), true, 64, JSON_THROW_ON_ERROR);
        $locked = is_array($lock) ? ($lock['packages'] ?? null) : null;
        if (!is_array($locked) || [] === $locked) {
            throw new RuntimeException('package_mirror_invalid');
        }
        $repositoryPackages = str_replace('\\', '/', dirname(__DIR__, 3)) . '/packages';
        foreach ($locked as $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null) || !str_starts_with($package['name'], 'kongono/')) {
                throw new RuntimeException('package_mirror_invalid');
            }
            $name = substr($package['name'], strlen('kongono/'));
            $this->copyTree($repositoryPackages . '/' . $name, $packages . '/' . $name);
        }
    }

    /** @return array{mapping:string,data:string,persistence:string,state:string,repositories:string,composition:string,wiring:string} */
    private function buildIdentities(string $stdout): array
    {
        $patterns = [
            'mapping' => '/mapping_artifact_sha256=([a-f0-9]{64})/',
            'data' => '/generated_data_access_sha256=([a-f0-9]{64})/',
            'persistence' => '/persistence_artifact_sha256=([a-f0-9]{64})/',
            'state' => '/generated_state_sha256=([a-f0-9]{64})/',
            'repositories' => '/generated_aggregate_repository_sha256=([a-f0-9]{64})/',
            'composition' => '/composition_artifact_sha256=([a-f0-9]{64})/',
            'wiring' => '/generated_wiring_sha256=([a-f0-9]{64})/',
        ];
        $identities = [];
        foreach ($patterns as $name => $pattern) {
            if (1 !== preg_match($pattern, $stdout, $match)) {
                throw new RuntimeException('build_identity_invalid');
            }
            $identities[$name] = $match[1];
        }

        return $identities;
    }

    /** @return array{exit:int,stdout:string,stderr:string} */
    private function composerScript(string $application, string $script, bool $requireSuccess): array
    {
        return $this->process(
            [...$this->composerCommand(), '--no-interaction', '--no-ansi', $script],
            $requireSuccess,
            $application,
            ['COMPOSER_DISABLE_NETWORK' => '1'],
        );
    }

    /** @return list<string> */
    private function composerCommand(): array
    {
        $configured = getenv('COMPOSER_BINARY');
        if (false === $configured || '' === $configured) {
            throw new RuntimeException('composer_binary_unavailable');
        }

        $resolved = realpath($configured);
        if (false === $resolved || !is_file($resolved) || is_link($configured)) {
            throw new RuntimeException('composer_binary_unavailable');
        }

        return [PHP_BINARY, str_replace('\\', '/', $resolved)];
    }

    private function runtimeHostBytes(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

if (4 !== $argc || !isset($argv[1], $argv[2], $argv[3]) || !ctype_digit($argv[2])) {
    fwrite(STDERR, "persistence_runtime_failed_invocation_invalid\n");
    exit(2);
}

require $argv[1] . '/vendor/autoload.php';
require $argv[3] . '/tests/External/Persistence/PersistencePerformance.php';
require $argv[3] . '/tests/External/Persistence/PersistenceAcceptance.php';

$runner = new Kongono\Tests\External\Persistence\PersistenceAcceptance();
exit($runner->runRuntimeChild($argv[1], (int) $argv[2], STDOUT, STDERR));
PHP . "\n";
    }

    /** @param array{mapping:string,data:string,persistence:string,state:string,repositories:string,composition:string,wiring:string} $outputs */
    private function validateGeneratedPhp(string $application, array $outputs): void
    {
        $php = [$outputs['data'], $outputs['state'], $outputs['repositories'], $outputs['wiring']];
        foreach ($php as $path) {
            if (0 !== $this->process([PHP_BINARY, '-l', $path], false, $application)['exit']) {
                throw new RuntimeException('generated_php_lint_failed');
            }
        }
        $repository = str_replace('\\', '/', dirname(__DIR__, 3));
        $phpstan = $repository . '/vendor/bin/phpstan';
        $analysis = $this->process([
            PHP_BINARY,
            $phpstan,
            'analyse',
            '--configuration=' . $repository . '/phpstan.neon.dist',
            '--level=max',
            '--no-progress',
            '--debug',
            '--autoload-file=' . $application . '/vendor/autoload.php',
            $application . '/src',
            ...$php,
        ], false, $application);
        if (0 !== $analysis['exit']) {
            throw new RuntimeException('generated_php_analysis_failed');
        }
    }

    private function directoryIdentity(string $directory): string
    {
        $root = rtrim(str_replace('\\', '/', $directory), '/');
        $records = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || $entry->isLink()) {
                throw new RuntimeException('directory_snapshot_invalid');
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            $records[substr($path, strlen($root) + 1)] = hash('sha256', $this->readOrdinary($path));
        }
        ksort($records, SORT_STRING);

        return hash('sha256', json_encode($records, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function copyTree(string $source, string $destination): void
    {
        $this->createDirectory($destination);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink()) {
                throw new RuntimeException('copy_source_invalid');
            }
            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($source) + 1));
            if ($entry->isDir()) {
                $this->createDirectory($destination . '/' . $relative);
            } elseif ($entry->isFile()) {
                $this->writeTemporary($destination . '/' . $relative, $this->readOrdinary($entry->getPathname()));
            } else {
                throw new RuntimeException('copy_special_file_detected');
            }
        }
    }

    private function writeTemporary(string $path, string $bytes): void
    {
        $this->createDirectory(dirname($path));
        $written = file_put_contents($path, $bytes);
        if (strlen($bytes) !== $written) {
            throw new RuntimeException('temporary_write_failed');
        }
    }

    private function readOrdinary(string $path): string
    {
        $stat = @lstat($path);
        if (false === $stat || is_link($path) || !is_file($path) || (($stat['mode'] & 0170000) !== 0100000)) {
            throw new RuntimeException('ordinary_file_required');
        }
        $bytes = file_get_contents($path);
        if (false === $bytes || strlen($bytes) !== $stat['size']) {
            throw new RuntimeException('ordinary_file_read_failed');
        }

        return $bytes;
    }

    private function createDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        if (file_exists($path) || is_link($path) || !mkdir($path, 0700, true)) {
            throw new RuntimeException('directory_create_failed');
        }
    }

    private function removeWorkspace(string $workspace): void
    {
        $temporary = rtrim(str_replace('\\', '/', (string) realpath(sys_get_temp_dir())), '/');
        $resolved = realpath($workspace);
        if (false === $resolved || dirname(str_replace('\\', '/', $resolved)) !== $temporary || !str_starts_with(basename($resolved), self::PREFIX)) {
            throw new RuntimeException('cleanup_scope_invalid');
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink()) {
                throw new RuntimeException('cleanup_reparse_detected');
            }
            $removed = $entry->isDir() ? $this->removeWithRetry($entry->getPathname(), true) : ($entry->isFile() && $this->removeWithRetry($entry->getPathname(), false));
            if (!$removed) {
                throw new RuntimeException('cleanup_failed');
            }
        }
        if (!$this->removeWithRetry($resolved, true)) {
            throw new RuntimeException('cleanup_failed');
        }
    }

    private function removeWithRetry(string $path, bool $directory): bool
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            if (($directory ? @rmdir($path) : @unlink($path)) || !file_exists($path)) {
                return true;
            }
            usleep(20_000);
        }

        return false;
    }

    private function availablePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if (!is_resource($server)) {
            throw new RuntimeException('port_selection_failed');
        }
        $name = stream_socket_get_name($server, false);
        fclose($server);
        if (!is_string($name) || 1 !== preg_match('/:(\d+)$/D', $name, $match)) {
            throw new RuntimeException('port_selection_failed');
        }
        $port = (int) $match[1];
        if ($port < 49_152 || $port > 65_535) {
            throw new RuntimeException('port_selection_failed');
        }

        return $port;
    }

    private function listening(int $port): bool
    {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $errorCode, $errorMessage, 0.2);
        if (!is_resource($socket)) {
            return false;
        }
        fclose($socket);

        return true;
    }
}
