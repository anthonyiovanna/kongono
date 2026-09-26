<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageInspection\RuntimeEvidence\DetachedStorageRuntimeEvidence;
use RuntimeException;

final class StorageInspectionFactory
{
    private static bool $retirementUncertain = false;
    /** @var list<resource> */
    private static array $unretiredChildren = [];

    public const string PROFILE_SECRET = 'private-profile';
    public const string NAMESPACE_SECRET = 'private-namespace';
    public const string OBJECT_SECRET = 'private-object-identity';
    public const string CONTENT_SECRET = 'private-object-content';
    public const string METADATA_SECRET = 'private-metadata-value';
    public const string PATH_SECRET = 'private-storage-path';
    public const string PROVIDER_SECRET = 'private-provider-locator';
    public const string CREDENTIAL_SECRET = 'private-credential';
    public const string EXCEPTION_SECRET = 'private exception trace argument';

    public static function current(bool $single = false, string $suffix = ''): CurrentStorageApplicationBuild
    {
        $profiles = [
            new StorageProfileDeclaration(
                ContractVersion::Version1,
                new StorageProfileName(self::PROFILE_SECRET . $suffix),
                new StorageNamespace(self::NAMESPACE_SECRET . $suffix),
                new StorageCapabilitySet([StorageCapability::Read, StorageCapability::Create, StorageCapability::ContentDigest]),
                StorageDigestAlgorithm::Sha256,
                new StorageProfileLimits(4096, 8192, 10),
            ),
        ];
        if (!$single) {
            $profiles[]
                = new StorageProfileDeclaration(
                    ContractVersion::Version1,
                    new StorageProfileName('secondary-profile' . $suffix),
                    new StorageNamespace('secondary-namespace' . $suffix),
                    new StorageCapabilitySet([StorageCapability::Read, StorageCapability::List]),
                    null,
                    new StorageProfileLimits(8192, 16384, 20),
                );
        }
        $set = new StorageProfileSet($profiles);
        $artifact = (new StorageProfileBuilder())->build($set)->artifact();

        return new CurrentStorageApplicationBuild($set, $artifact);
    }

    public static function runtime(CurrentStorageApplicationBuild $current, int $offset = 0): DetachedStorageRuntimeEvidence
    {
        $counts = [];
        foreach (DetachedStorageRuntimeEvidence::OUTCOMES as $position => $outcome) {
            $counts[$outcome] = $position + $offset;
        }

        return new DetachedStorageRuntimeEvidence([
            'profile_set_sha256' => $current->profiles()->identity()->value(),
            'build_sha256' => $current->artifact()->buildIdentity()->value(),
            'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
        ], $counts);
    }

    public static function zeroRuntime(CurrentStorageApplicationBuild $current): DetachedStorageRuntimeEvidence
    {
        return new DetachedStorageRuntimeEvidence([
            'profile_set_sha256' => $current->profiles()->identity()->value(),
            'build_sha256' => $current->artifact()->buildIdentity()->value(),
            'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
        ], array_fill_keys(DetachedStorageRuntimeEvidence::OUTCOMES, 0));
    }

    public static function graph(GraphRole $role = GraphRole::Chie, int $services = 4): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            GraphContractVersion::Version1,
            6 + $services,
            6,
            ['composition.input' => 1, 'composition.service' => $services, 'composition.contract' => 3, 'composition.root' => 2],
            ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1],
        );
    }

    /**
     * @return array{stdout: string, stderr: string, exit: int|null, timed_out: bool, retired: bool, directory: string, stdout_path: string, stderr_path: string}
     */
    public static function isolatedPhp(
        string $body,
        ?string $traceMode = null,
        int $deadlineMilliseconds = 60_000,
        int $retirementMilliseconds = 30_000,
    ): array {
        if (self::$retirementUncertain || [] !== self::$unretiredChildren) {
            throw new RuntimeException('An owned PHP child has uncertain retirement; outer validator watchdog must retire its process tree.');
        }
        if ($deadlineMilliseconds < 1 || $deadlineMilliseconds > 60_000 || $retirementMilliseconds < 1 || $retirementMilliseconds > 30_000) {
            throw new RuntimeException('Private child deadlines must be finite and within the helper bounds.');
        }
        $root = dirname(__DIR__, 4);
        $temporaryRoot = realpath(sys_get_temp_dir());
        if (false === $temporaryRoot) {
            throw new RuntimeException('Private child TEMP is unavailable.');
        }
        $directory = $temporaryRoot . DIRECTORY_SEPARATOR . 'kongono-g11-' . bin2hex(random_bytes(16));
        if (!mkdir($directory, 0700)) {
            throw new RuntimeException('Could not create invocation-owned child directory: ' . $directory);
        }
        $stdout = $directory . DIRECTORY_SEPARATOR . 'stdout.bin';
        $stderr = $directory . DIRECTORY_SEPARATOR . 'stderr.bin';
        $code = 'require ' . var_export($root . '/vendor/autoload.php', true) . ';'
            . 'require ' . var_export(__FILE__, true) . ';'
            . $body;
        $args = [PHP_BINARY];
        if (null !== $traceMode) {
            $args[] = '-d';
            $args[] = 'zend.exception_ignore_args=' . $traceMode;
        }
        $args[] = '-r';
        $args[] = $code;
        $pipes = [];
        $child = proc_open($args, [0 => ['pipe', 'r'], 1 => ['file', $stdout, 'w'], 2 => ['file', $stderr, 'w']], $pipes, $root, null, ['bypass_shell' => true]);
        if (!is_resource($child)) {
            throw new RuntimeException('Private child did not start; capture: ' . $stdout . ', ' . $stderr);
        }
        fclose($pipes[0]);
        $deadline = hrtime(true) + $deadlineMilliseconds * 1_000_000;
        $timedOut = false;
        $exit = null;
        do {
            $status = proc_get_status($child);
            if (!$status['running']) {
                if ($status['exitcode'] >= 0) {
                    $exit = $status['exitcode'];
                }
                break;
            }
            if (hrtime(true) >= $deadline) {
                $timedOut = true;
                proc_terminate($child);
                $retirement = hrtime(true) + $retirementMilliseconds * 1_000_000;
                do {
                    usleep(10_000);
                    $status = proc_get_status($child);
                } while ($status['running'] && hrtime(true) < $retirement);
                if ($status['running']) {
                    self::$retirementUncertain = true;
                    self::$unretiredChildren[] = $child;
                    throw new RuntimeException('Owned child retirement uncertain; outer validator watchdog must retire tree; capture: ' . $stdout . ', ' . $stderr);
                }
                if ($status['exitcode'] >= 0) {
                    $exit = $status['exitcode'];
                }
                break;
            }
            usleep(10_000);
        } while (true);
        $closedExit = proc_close($child);
        if (null === $exit && $closedExit >= 0) {
            $exit = $closedExit;
        }
        $out = file_get_contents($stdout);
        $err = file_get_contents($stderr);
        if (!is_string($out) || !is_string($err)) {
            throw new RuntimeException('Private child stream capture failed; capture: ' . $stdout . ', ' . $stderr);
        }

        return [
            'stdout' => $out,
            'stderr' => $err,
            'exit' => $exit,
            'timed_out' => $timedOut,
            'retired' => true,
            'directory' => $directory,
            'stdout_path' => $stdout,
            'stderr_path' => $stderr,
        ];
    }

    /** @param array{stdout: string, stderr: string, exit: int|null, timed_out: bool, retired: bool, directory: string, stdout_path: string, stderr_path: string} $capture */
    public static function requireSuccessfulChild(array $capture): void
    {
        if (!$capture['retired'] || $capture['timed_out'] || 0 !== $capture['exit']) {
            throw new RuntimeException('Private child did not complete successfully; exit=' . var_export($capture['exit'], true)
                . '; timed_out=' . ($capture['timed_out'] ? 'yes' : 'no') . '; capture: '
                . $capture['stdout_path'] . ', ' . $capture['stderr_path']);
        }
    }

    /** @param array{stdout: string, stderr: string, exit: int|null, timed_out: bool, retired: bool, directory: string, stdout_path: string, stderr_path: string} $capture */
    public static function acknowledgeChild(array $capture): void
    {
        self::requireSuccessfulChild($capture);
        self::removeOwnedCapture($capture);
    }

    /**
     * Only synthetic regression evidence verified by the test may use this path.
     *
     * @param array{stdout: string, stderr: string, exit: int|null, timed_out: bool, retired: bool, directory: string, stdout_path: string, stderr_path: string} $capture
     */
    public static function discardVerifiedSyntheticCapture(array $capture): void
    {
        if (!$capture['retired']) {
            throw new RuntimeException('Unretired synthetic child evidence cannot be removed.');
        }
        self::removeOwnedCapture($capture);
    }

    /** @param array{stdout: string, stderr: string, exit: int|null, timed_out: bool, retired: bool, directory: string, stdout_path: string, stderr_path: string} $capture */
    private static function removeOwnedCapture(array $capture): void
    {
        $base = realpath(sys_get_temp_dir());
        $directory = realpath($capture['directory']);
        if (false === $base || false === $directory || dirname($directory) !== $base
            || 1 !== preg_match('/\Akongono-g11-[a-f0-9]{32}\z/D', basename($directory))
            || $capture['stdout_path'] !== $directory . DIRECTORY_SEPARATOR . 'stdout.bin'
            || $capture['stderr_path'] !== $directory . DIRECTORY_SEPARATOR . 'stderr.bin'
            || is_link($directory)) {
            throw new RuntimeException('Capture cleanup refused non-owned path: ' . $capture['directory']);
        }
        foreach ([$capture['stdout_path'], $capture['stderr_path']] as $path) {
            if (!is_file($path) || is_link($path) || realpath($path) !== $path) {
                throw new RuntimeException('Capture cleanup refused missing or redirected stream: ' . $path);
            }
        }
        $entries = scandir($directory);
        if (!is_array($entries) || $entries !== ['.', '..', 'stderr.bin', 'stdout.bin']) {
            throw new RuntimeException('Capture cleanup refused unexpected directory entries; retained evidence: '
                . $capture['stdout_path'] . ', ' . $capture['stderr_path']);
        }
        if (!unlink($capture['stdout_path']) || !unlink($capture['stderr_path']) || !rmdir($directory)) {
            if (!is_file($capture['stdout_path'])) {
                file_put_contents($capture['stdout_path'], $capture['stdout']);
            }
            if (!is_file($capture['stderr_path'])) {
                file_put_contents($capture['stderr_path'], $capture['stderr']);
            }
            throw new RuntimeException('Capture cleanup failed; retained evidence: ' . $capture['stdout_path'] . ', ' . $capture['stderr_path']);
        }
        if (file_exists($capture['stdout_path']) || file_exists($capture['stderr_path']) || is_dir($directory)) {
            throw new RuntimeException('Capture cleanup remained incomplete: ' . $directory);
        }
    }

    private function __construct() {}
}
