<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Application;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

/** Executes the closed 28-scenario Phase 5 application-acceptance matrix. */
final class ApplicationAcceptance
{
    private const int INVALID_INVOCATION = 64;
    private const int ACCEPTANCE_FAILURE = 1;
    private const int UNEXPECTED_FAILURE = 70;
    private const int MAX_STREAM_BYTES = 8_388_608;
    private const string TEMPORARY_PREFIX = 'kongono-phase5h-';
    private const string INVALID_INVOCATION_BYTES = "Phase 5 acceptance invocation is invalid.\n";
    private const string ACCEPTANCE_FAILURE_BYTES = "Phase 5 acceptance failed.\n";
    private const string UNEXPECTED_FAILURE_BYTES = "Phase 5 acceptance could not complete.\n";

    /** @var list<string> */
    private const array SKELETON_PATHS = [
        '.gitignore',
        'README.md',
        'bin/build.php',
        'bin/check.php',
        'bin/run.php',
        'composer.json',
        'composer.lock',
        'kongono.composition.json',
        'kongono.skeleton.json',
        'public/index.php',
        'src/Bootstrap.php',
        'src/Http/HomeHandler.php',
        'src/Http/NotFoundHandler.php',
        'src/Runtime.php',
        'var/kongono/build/.gitignore',
    ];

    private string $repositoryRoot = '';

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource                $stdout
     * @param resource                $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        if (!array_is_list($arguments) || [] !== $arguments) {
            $this->writeStream($stderr, self::INVALID_INVOCATION_BYTES);

            return self::INVALID_INVOCATION;
        }

        $root = realpath(dirname(__DIR__, 3));
        if (false === $root || is_link($root) || !is_dir($root)) {
            $this->writeStream($stderr, self::UNEXPECTED_FAILURE_BYTES);

            return self::UNEXPECTED_FAILURE;
        }
        $this->repositoryRoot = str_replace('\\', '/', $root);
        $workspace = null;
        $identities = null;
        $failure = null;

        try {
            $before = $this->repositoryIdentity();
            $workspace = $this->createWorkspace();
            $identities = $this->accept($workspace);
            $this->require($before === $this->repositoryIdentity(), 'repository_mutated');
        } catch (RuntimeException) {
            $failure = self::ACCEPTANCE_FAILURE;
        } catch (Throwable) {
            $failure = self::UNEXPECTED_FAILURE;
        }

        if (null !== $workspace) {
            try {
                $this->removeWorkspace($workspace);
                $this->require(!file_exists($workspace) && !is_link($workspace), 'temporary_cleanup_failed');
            } catch (RuntimeException) {
                $failure = self::ACCEPTANCE_FAILURE;
            } catch (Throwable) {
                $failure = self::UNEXPECTED_FAILURE;
            }
        }

        if (null !== $failure || null === $identities) {
            $unexpected = self::UNEXPECTED_FAILURE === $failure;
            $this->writeStream($stderr, $unexpected ? self::UNEXPECTED_FAILURE_BYTES : self::ACCEPTANCE_FAILURE_BYTES);

            return $unexpected ? self::UNEXPECTED_FAILURE : self::ACCEPTANCE_FAILURE;
        }

        $this->writeStream($stdout, (new AcceptanceResult(
            $identities['composition'],
            $identities['wiring'],
            $identities['skeleton'],
        ))->canonicalBytes());

        return 0;
    }

    /** @return array{composition: string, wiring: string, skeleton: string} */
    private function accept(string $workspace): array
    {
        $application = $workspace . '/application';
        $packages = $workspace . '/packages';
        $this->createDirectory($application);
        $this->createDirectory($packages);
        $skeletonSha256 = $this->copySkeleton($application);
        $this->mirrorPackages($application, $packages);

        $install = $this->process(
            [...$this->composerCommand(), 'install', '--no-interaction', '--no-ansi', '--no-plugins'],
            $application,
            ['COMPOSER_DISABLE_NETWORK' => '1'],
        );
        $this->expectSuccess($install);
        $this->assertNoLinks($application . '/vendor');

        $sourcePath = $application . '/kongono.composition.json';
        $buildDirectory = $application . '/var/kongono/build';
        $sourceBytes = $this->readOrdinary($sourcePath);

        $firstBuild = $this->successfulComposerScript($application, 'app:build');
        $identities = $this->buildIdentities($firstBuild->stdout());
        $artifactPath = $buildDirectory . '/kongono-composition-v1-' . $identities['composition'] . '.json';
        $wiringPath = $buildDirectory . '/kongono-wiring-v1-' . $identities['wiring'] . '.php';
        $artifactBytes = $this->readOrdinary($artifactPath);
        $wiringBytes = $this->readOrdinary($wiringPath);
        $this->require($this->generatedFiles($buildDirectory) === [basename($artifactPath), basename($wiringPath)], 'build_pair_invalid');

        $beforeCheck = $this->treeIdentity($application, ['vendor']);
        $check = $this->successfulComposerScript($application, 'app:check');
        $this->require($identities === $this->buildIdentities($check->stdout()), 'check_identity_mismatch');
        $this->require($beforeCheck === $this->treeIdentity($application, ['vendor']), 'check_mutated_application');

        $beforeRun = $this->treeIdentity($application, ['vendor']);
        $home = $this->successfulComposerScript($application, 'app:run');
        $this->require("Hello from Kongono.\n" === $home->stdout(), 'home_response_invalid');
        $this->require($beforeRun === $this->treeIdentity($application, ['vendor']), 'run_mutated_application');

        [$missingStatus, $missingBody] = $this->cgiRequest($application, '/missing');
        $this->require(404 === $missingStatus && "Not Found.\n" === $missingBody, 'unmatched_response_invalid');
        $this->require($beforeRun === $this->treeIdentity($application, ['vendor']), 'unmatched_run_mutated_application');

        $repeatBuild = $this->successfulComposerScript($application, 'app:build');
        $this->require($identities === $this->buildIdentities($repeatBuild->stdout()), 'repeat_build_identity_mismatch');
        $this->require($beforeRun === $this->treeIdentity($application, ['vendor']), 'repeat_build_bytes_changed');
        $repeatCheck = $this->successfulComposerScript($application, 'app:check');
        $this->require($identities === $this->buildIdentities($repeatCheck->stdout()), 'repeat_check_identity_mismatch');
        $this->require($beforeRun === $this->treeIdentity($application, ['vendor']), 'repeat_check_mutated_application');
        $repeatRun = $this->successfulComposerScript($application, 'app:run');
        $this->require($home->stdout() === $repeatRun->stdout(), 'repeat_run_bytes_changed');
        $this->require($beforeRun === $this->treeIdentity($application, ['vendor']), 'repeat_run_mutated_application');

        $this->removeFile($sourcePath);
        $this->expectFailure($this->composerScript($application, 'app:build'));
        $this->writeFile($sourcePath, $sourceBytes);
        $this->writeFile($sourcePath, "{\n");
        $this->expectFailure($this->composerScript($application, 'app:build'));
        $this->writeFile($sourcePath, $sourceBytes);

        $staleBytes = rtrim($sourceBytes, "\n") . " \n";
        $this->writeFile($sourcePath, $staleBytes);
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->expectFailure($this->composerScript($application, 'app:run'));
        $this->writeFile($sourcePath, $sourceBytes);

        $this->removeFile($artifactPath);
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->expectFailure($this->composerScript($application, 'app:run'));
        $this->writeFile($artifactPath, $artifactBytes);
        $this->writeFile($artifactPath, $artifactBytes . " ");
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->expectFailure($this->composerScript($application, 'app:run'));
        $this->writeFile($artifactPath, $artifactBytes);

        $this->removeFile($wiringPath);
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->expectFailure($this->composerScript($application, 'app:run'));
        $this->writeFile($wiringPath, $wiringBytes);
        $this->writeFile($wiringPath, $wiringBytes . "\n");
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->expectFailure($this->composerScript($application, 'app:run'));
        $this->writeFile($wiringPath, $wiringBytes);

        $this->writeFile($sourcePath, $staleBytes);
        $peerBuild = $this->successfulComposerScript($application, 'app:build');
        $peer = $this->buildIdentities($peerBuild->stdout());
        $this->require($peer !== $identities, 'peer_build_identity_unchanged');
        $peerWiring = $this->readOrdinary($buildDirectory . '/kongono-wiring-v1-' . $peer['wiring'] . '.php');
        $this->writeFile($sourcePath, $sourceBytes);
        $this->writeFile($wiringPath, $peerWiring);
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->writeFile($wiringPath, $wiringBytes);

        $renamedArtifact = $buildDirectory . '/renamed-output.json';
        $this->moveFile($artifactPath, $renamedArtifact);
        $this->expectFailure($this->composerScript($application, 'app:check'));
        $this->moveFile($renamedArtifact, $artifactPath);

        $beforeFailedBuild = $this->treeIdentity($buildDirectory);
        $this->writeFile($sourcePath, "{\n");
        $this->expectFailure($this->composerScript($application, 'app:build'));
        $this->require($beforeFailedBuild === $this->treeIdentity($buildDirectory), 'failed_build_published_partial');
        $this->writeFile($sourcePath, $sourceBytes);
        $this->require($identities === $this->buildIdentities($this->successfulComposerScript($application, 'app:check')->stdout()), 'valid_pair_not_preserved');

        $secondApplication = $workspace . '/application-second';
        $this->copyTree($application, $secondApplication);
        foreach ($this->generatedFiles($secondApplication . '/var/kongono/build') as $file) {
            $this->removeFile($secondApplication . '/var/kongono/build/' . $file);
        }
        $secondBuild = $this->buildIdentities($this->successfulComposerScript($secondApplication, 'app:build')->stdout());
        $this->require($identities === $secondBuild, 'sequential_root_identity_mismatch');
        $this->require($secondApplication !== $application, 'sequential_root_alias');
        $this->require($secondBuild === $this->buildIdentities($this->successfulComposerScript($secondApplication, 'app:check')->stdout()), 'sequential_root_check_failed');
        $this->require("Hello from Kongono.\n" === $this->successfulComposerScript($secondApplication, 'app:run')->stdout(), 'sequential_root_run_failed');

        $retirement = $this->process([PHP_BINARY, '-r', $this->operationRetirementProbe()], $application);
        $this->expectSuccess($retirement);
        $this->require("operation-retirement-pass\n" === $retirement->stdout(), 'operation_retirement_invalid');

        $secret = 'KONGONO_PHASE5_SECRET_' . bin2hex(random_bytes(16));
        $offlineEnvironment = [
            'COMPOSER_DISABLE_NETWORK' => '1',
            'HTTP_PROXY' => 'http://127.0.0.1:9',
            'HTTPS_PROXY' => 'http://127.0.0.1:9',
            'ALL_PROXY' => 'http://127.0.0.1:9',
            'NO_PROXY' => '',
            'KONGONO_PHASE5_UNRELATED_SECRET' => $secret,
        ];
        $offlineCheck = $this->composerScript($application, 'app:check', $offlineEnvironment);
        $this->expectSuccess($offlineCheck);
        $this->require($identities === $this->buildIdentities($offlineCheck->stdout()), 'offline_check_identity_mismatch');
        $offlineRun = $this->composerScript($application, 'app:run', $offlineEnvironment);
        $this->expectSuccess($offlineRun);
        $this->require("Hello from Kongono.\n" === $offlineRun->stdout(), 'offline_run_invalid');
        $this->assertAbsent($secret, $offlineCheck->stdout() . $offlineCheck->stderr() . $offlineRun->stdout() . $offlineRun->stderr());

        $markers = [
            'Phase5SecretMarkerAa9',
            'C:/private/Phase5PathMarkerBb8',
            'Phase5SourceMarkerCc7',
            'Phase5NativeErrorMarkerDd6',
        ];
        $this->writeFile($sourcePath, '{"phase5Rejected":"' . implode('|', $markers) . '"}' . "\n");
        $disclosure = $this->composerScript($application, 'app:build');
        $this->expectFailure($disclosure);
        foreach ($markers as $marker) {
            $this->assertAbsent($marker, $disclosure->stdout() . $disclosure->stderr());
        }
        $this->writeFile($sourcePath, $sourceBytes);
        $this->require($beforeRun === $this->treeIdentity($application, ['vendor'], ['var/kongono/build/kongono-composition-v1-' . $peer['composition'] . '.json', 'var/kongono/build/kongono-wiring-v1-' . $peer['wiring'] . '.php']), 'accepted_application_changed');

        return [
            'composition' => $identities['composition'],
            'wiring' => $identities['wiring'],
            'skeleton' => $skeletonSha256,
        ];
    }

    private function copySkeleton(string $application): string
    {
        $skeleton = $this->repositoryRoot . '/skeletons/minimal';
        $ownership = json_decode($this->readOrdinary($skeleton . '/kongono.skeleton.json'), true, 16, JSON_THROW_ON_ERROR);
        $this->require(is_array($ownership) && self::SKELETON_PATHS === ($ownership['template_paths'] ?? null), 'skeleton_inventory_invalid');
        $records = [];
        foreach (self::SKELETON_PATHS as $relative) {
            $bytes = $this->readOrdinary($skeleton . '/' . $relative);
            $this->writeFile($application . '/' . $relative, $bytes);
            $records[] = $relative . "\0" . strlen($bytes) . "\0" . hash('sha256', $bytes);
        }
        $this->require(self::SKELETON_PATHS === $this->relativeFiles($application), 'skeleton_copy_invalid');

        $composerPath = $application . '/composer.json';
        $composer = str_replace('"url": "../../packages/*"', '"url": "../packages/*"', $this->readOrdinary($composerPath), $count);
        $this->require(1 === $count, 'skeleton_repository_rewrite_invalid');
        $this->writeFile($composerPath, $composer);
        $lockPath = $application . '/composer.lock';
        $lock = json_decode($this->readOrdinary($lockPath), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($lock) || !is_array($lock['packages'] ?? null)) {
            throw new RuntimeException('skeleton_lock_invalid');
        }
        $packages = $lock['packages'];
        $localized = 0;
        foreach ($packages as $index => $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null) || !is_array($package['dist'] ?? null)) {
                throw new RuntimeException('skeleton_lock_invalid');
            }
            $name = $package['name'];
            if (str_starts_with($name, 'kongono/')) {
                $package['dist']['url'] = '../packages/' . substr($name, strlen('kongono/'));
            } elseif (str_starts_with($name, 'psr/')) {
                unset($package['source']);
                $package['dist'] = [
                    'type' => 'path',
                    'url' => '../packages/psr-' . substr($name, strlen('psr/')),
                    'reference' => $package['dist']['reference'] ?? null,
                    'shasum' => null,
                ];
                $package['transport-options'] = ['symlink' => false, 'relative' => true];
            } else {
                throw new RuntimeException('skeleton_lock_package_invalid');
            }
            $packages[$index] = $package;
            ++$localized;
        }
        $this->require(19 === $localized, 'skeleton_lock_rewrite_invalid');
        $lock['packages'] = $packages;
        $this->writeFile($lockPath, json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 64) . "\n");

        return hash('sha256', implode("\n", $records));
    }

    private function mirrorPackages(string $application, string $destination): void
    {
        $lock = json_decode($this->readOrdinary($application . '/composer.lock'), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($lock) || !is_array($lock['packages'] ?? null)) {
            throw new RuntimeException('skeleton_lock_invalid');
        }
        $lockedPackages = $lock['packages'];
        $names = [];
        $kongonoCount = 0;
        $psrCount = 0;
        foreach ($lockedPackages as $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null)) {
                throw new RuntimeException('package_mirror_identity_invalid');
            }
            $packageName = $package['name'];
            if (str_starts_with($packageName, 'kongono/')) {
                $name = substr($packageName, strlen('kongono/'));
                $source = $this->repositoryRoot . '/packages/' . $name;
                $destinationName = $name;
                ++$kongonoCount;
            } elseif (str_starts_with($packageName, 'psr/')) {
                $name = substr($packageName, strlen('psr/'));
                $source = $this->repositoryRoot . '/vendor/psr/' . $name;
                $destinationName = 'psr-' . $name;
                ++$psrCount;
            } else {
                throw new RuntimeException('package_mirror_identity_invalid');
            }
            $names[] = $packageName;
            $manifest = json_decode($this->readOrdinary($source . '/composer.json'), true, 16, JSON_THROW_ON_ERROR);
            $this->require(is_array($manifest) && $packageName === ($manifest['name'] ?? null), 'package_mirror_identity_invalid');
            $this->copyTree($source, $destination . '/' . $destinationName);
            $this->require($this->treeIdentity($source) === $this->treeIdentity($destination . '/' . $destinationName), 'package_mirror_bytes_invalid');
        }
        sort($names, SORT_STRING);
        $this->require(19 === count($names) && 19 === count(array_unique($names)) && 15 === $kongonoCount && 4 === $psrCount, 'package_mirror_count_invalid');
    }

    private function successfulComposerScript(string $application, string $script): ProcessResult
    {
        $result = $this->composerScript($application, $script);
        $this->expectSuccess($result);

        return $result;
    }

    /** @param array<string, string|null> $environment */
    private function composerScript(string $application, string $script, array $environment = []): ProcessResult
    {
        return $this->process([...$this->composerCommand(), '--no-interaction', '--no-ansi', $script], $application, $environment);
    }

    /** @return list<string> */
    private function composerCommand(): array
    {
        $candidates = [];
        $configured = getenv('COMPOSER_BINARY');
        if (is_string($configured) && '' !== $configured) {
            $candidates[] = $configured;
        }
        $path = getenv('PATH');
        if (is_string($path)) {
            foreach (explode(PATH_SEPARATOR, $path) as $directory) {
                if ('' !== $directory) {
                    $candidates[] = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'composer.phar';
                }
            }
        }
        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if (false !== $resolved && !is_link($candidate) && is_file($resolved) && is_readable($resolved)) {
                return [PHP_BINARY, str_replace('\\', '/', $resolved)];
            }
        }

        throw new RuntimeException('composer_binary_unavailable');
    }

    /** @return array{composition: string, wiring: string} */
    private function buildIdentities(string $stdout): array
    {
        if (1 !== preg_match('/composition_artifact_sha256=([a-f0-9]{64})/', $stdout, $composition)) {
            throw new RuntimeException('composition_identity_missing');
        }
        if (1 !== preg_match('/generated_wiring_sha256=([a-f0-9]{64})/', $stdout, $wiring)) {
            throw new RuntimeException('wiring_identity_missing');
        }

        return ['composition' => $composition[1], 'wiring' => $wiring[1]];
    }

    /** @return array{0: int, 1: string} */
    private function cgiRequest(string $application, string $uri): array
    {
        $executable = str_replace('\\', '/', dirname(PHP_BINARY)) . ('Windows' === PHP_OS_FAMILY ? '/php-cgi.exe' : '/php-cgi');
        $resolved = realpath($executable);
        $index = $application . '/public/index.php';
        $this->require(false !== $resolved && str_replace('\\', '/', $resolved) === $executable, 'cgi_binary_invalid');
        $command = [$executable, '-n'];
        foreach ([
            'cgi.force_redirect=0', 'user_ini.filename=', 'enable_post_data_reading=0', 'output_buffering=0',
            'zlib.output_compression=0', 'display_errors=0', 'display_startup_errors=0', 'html_errors=0',
            'expose_php=0', 'log_errors=0', 'auto_prepend_file=', 'auto_append_file=', 'cgi.rfc2616_headers=0',
        ] as $setting) {
            $command[] = '-d';
            $command[] = $setting;
        }
        $result = $this->process($command, $application, [
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'FCGI_ROLE' => 'RESPONDER',
            'REQUEST_METHOD' => 'GET',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_URI' => $uri . '?kongono_proof=1',
            'QUERY_STRING' => 'kongono_proof=1',
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_FILENAME' => $index,
            'SCRIPT_NAME' => '/index.php',
            'REDIRECT_STATUS' => '1',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
        ]);
        $this->expectSuccess($result);
        $separator = str_contains($result->stdout(), "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
        $parts = explode($separator, $result->stdout(), 2);
        $this->require(2 === count($parts), 'cgi_response_invalid');
        $status = 200;
        if (1 === preg_match('/(?:^|\r?\n)Status: ([0-9]{3})(?: [^\r\n]*)?(?:\r?\n|$)/', $parts[0], $match)) {
            $status = (int) $match[1];
        }

        return [$status, $parts[1]];
    }

    private function operationRetirementProbe(): string
    {
        return <<<'PHP'
require 'vendor/autoload.php';
$kernel = new Kongono\Kernel\Kernel();
$application = new Kongono\Web\Application\WebApplication($kernel, App\Bootstrap::pipeline());
$home = $application->handle(new Kongono\Http\Message\Message\ServerRequest('GET', '/'));
$missing = $application->handle(new Kongono\Http\Message\Message\ServerRequest('GET', '/missing'));
if (200 !== $home->getStatusCode() || 404 !== $missing->getStatusCode() || 0 !== $kernel->activeOperationCount()) { exit(2); }
$application->shutdown();
if (Kongono\Kernel\Lifecycle\KernelState::Shutdown !== $kernel->state()) { exit(3); }
try { $application->handle(new Kongono\Http\Message\Message\ServerRequest('GET', '/')); exit(4); } catch (Throwable) {}
fwrite(STDOUT, "operation-retirement-pass\n");
PHP;
    }

    /**
     * @param list<string>               $command
     * @param array<string, string|null> $environment
     */
    private function process(array $command, string $workingDirectory, array $environment = [], int $timeoutSeconds = 600): ProcessResult
    {
        if ([] === $command || $timeoutSeconds < 1) {
            return new ProcessResult(false, null, '', '', 'invalid-process-request');
        }
        $inherited = getenv();
        foreach ($environment as $name => $value) {
            if (null === $value) {
                unset($inherited[$name]);
            } else {
                $inherited[$name] = $value;
            }
        }
        $pipes = [];
        $process = @proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
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
                if (strlen($stdout) > self::MAX_STREAM_BYTES || strlen($stderr) > self::MAX_STREAM_BYTES) {
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

        return new ProcessResult(true, $exitCode, substr($stdout, 0, self::MAX_STREAM_BYTES), substr($stderr, 0, self::MAX_STREAM_BYTES), $blocked);
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

    private function expectSuccess(ProcessResult $result): void
    {
        $this->require($result->started() && null === $result->blockedReason() && 0 === $result->exitCode(), 'process_failed');
    }

    private function expectFailure(ProcessResult $result): void
    {
        $this->require($result->started() && null === $result->blockedReason() && is_int($result->exitCode()) && 0 !== $result->exitCode(), 'failure_process_invalid');
        $this->require(strlen($result->stdout()) <= 65_536 && strlen($result->stderr()) <= 65_536, 'failure_output_unbounded');
        $this->require('' !== $result->stdout() || '' !== $result->stderr(), 'failure_not_visible');
    }

    private function createWorkspace(): string
    {
        $temporaryRoot = realpath(sys_get_temp_dir());
        $repository = realpath($this->repositoryRoot);
        if (false === $temporaryRoot || false === $repository || is_link($temporaryRoot) || is_link($repository)) {
            throw new RuntimeException('temporary_root_unavailable');
        }
        $candidate = str_replace('\\', '/', $temporaryRoot) . '/' . self::TEMPORARY_PREFIX . $this->guid();
        $this->require(!file_exists($candidate) && !is_link($candidate) && mkdir($candidate, 0700), 'temporary_create_failed');
        $resolved = realpath($candidate);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $candidate) {
            throw new RuntimeException('temporary_resolution_failed');
        }
        $normalizedRepository = strtolower(str_replace('\\', '/', $repository));
        $normalizedCandidate = strtolower($candidate);
        $this->require(!str_starts_with($normalizedRepository . '/', $normalizedCandidate . '/') && !str_starts_with($normalizedCandidate . '/', $normalizedRepository . '/'), 'temporary_repository_overlap');

        return $candidate;
    }

    private function guid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    private function removeWorkspace(string $workspace): void
    {
        $resolved = realpath($workspace);
        $temporary = realpath(sys_get_temp_dir());
        if (false === $resolved || false === $temporary) {
            throw new RuntimeException('temporary_cleanup_identity_failed');
        }
        $resolved = str_replace('\\', '/', $resolved);
        $temporary = rtrim(str_replace('\\', '/', $temporary), '/');
        $this->require(dirname($resolved) === $temporary && str_starts_with(basename($resolved), self::TEMPORARY_PREFIX), 'temporary_cleanup_scope_invalid');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $path = $entry->getPathname();
            $this->require(!$entry->isLink(), 'temporary_reparse_detected');
            if ($entry->isDir()) {
                @chmod($path, 0700);
                $this->require($this->removeWithRetry($path, true), 'temporary_cleanup_failed');
            } elseif ($entry->isFile()) {
                @chmod($path, 0600);
                $this->require($this->removeWithRetry($path, false), 'temporary_cleanup_failed');
            } else {
                throw new RuntimeException('temporary_special_file_detected');
            }
        }
        @chmod($resolved, 0700);
        $this->require($this->removeWithRetry($resolved, true), 'temporary_cleanup_failed');
    }

    private function removeWithRetry(string $path, bool $directory): bool
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $removed = $directory ? @rmdir($path) : @unlink($path);
            if ($removed || !file_exists($path) && !is_link($path)) {
                return true;
            }
            usleep(20_000);
        }

        return false;
    }

    private function copyTree(string $source, string $destination): void
    {
        $this->createDirectory($destination);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($source) + 1));
            $this->require(!$entry->isLink(), 'copy_reparse_detected');
            if ($entry->isDir()) {
                $this->createDirectory($destination . '/' . $relative);
            } elseif ($entry->isFile()) {
                $this->writeFile($destination . '/' . $relative, $this->readOrdinary($entry->getPathname()));
            } else {
                throw new RuntimeException('copy_special_file_detected');
            }
        }
    }

    private function createDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        $this->require(!file_exists($path) && !is_link($path) && mkdir($path, 0700, true), 'directory_create_failed');
    }

    private function writeFile(string $path, string $bytes): void
    {
        $this->createDirectory(dirname($path));
        $written = file_put_contents($path, $bytes, LOCK_EX);
        $this->require(strlen($bytes) === $written, 'file_write_failed');
    }

    private function removeFile(string $path): void
    {
        $this->readOrdinary($path);
        $this->require(unlink($path), 'file_remove_failed');
    }

    private function moveFile(string $source, string $destination): void
    {
        $this->readOrdinary($source);
        $this->require(!file_exists($destination) && !is_link($destination) && rename($source, $destination), 'file_move_failed');
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

    /**
     * @param list<string> $excludedDirectories
     * @param list<string> $excludedFiles
     */
    private function treeIdentity(string $root, array $excludedDirectories = [], array $excludedFiles = []): string
    {
        $records = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
            if (in_array(explode('/', $relative, 2)[0], $excludedDirectories, true) || in_array($relative, $excludedFiles, true)) {
                continue;
            }
            $this->require(!$entry->isLink(), 'tree_reparse_detected');
            if (!$entry->isFile()) {
                continue;
            }
            $bytes = $this->readOrdinary($entry->getPathname());
            $records[] = $relative . "\0" . strlen($bytes) . "\0" . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    private function repositoryIdentity(): string
    {
        return $this->treeIdentity($this->repositoryRoot, ['.git', 'vendor', '.cache', '.idea', '.phpunit.cache', '.vscode'], ['.php-cs-fixer.cache']);
    }

    /** @return list<string> */
    private function relativeFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isFile()) {
                $files[] = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /** @return list<string> */
    private function generatedFiles(string $buildDirectory): array
    {
        $files = [];
        foreach ($this->relativeFiles($buildDirectory) as $file) {
            if ('.gitignore' !== $file) {
                $files[] = $file;
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    private function assertNoLinks(string $root): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $this->require(!$entry->isLink(), 'installed_link_detected');
        }
    }

    private function assertAbsent(string $needle, string $bytes): void
    {
        $this->require(!str_contains($bytes, $needle), 'protected_value_disclosed');
    }

    private function require(bool $condition, string $token): void
    {
        if (!$condition) {
            throw new RuntimeException($token);
        }
    }

    /** @param resource $stream */
    private function writeStream(mixed $stream, string $bytes): void
    {
        for ($offset = 0, $length = strlen($bytes); $offset < $length;) {
            $written = fwrite($stream, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('acceptance_output_failed');
            }
            $offset += $written;
        }
    }
}
