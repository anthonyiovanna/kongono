<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Fiber;
use Kongono\Core\Capabilities\CapabilityException;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Security\Egress\SafeHttpClient;
use Kongono\Security\TokenSigner;
use Kongono\Security\Totp;
use PHPUnit\Framework\TestCase;

/**
 * Multi-process OS-level concurrency race tests and 2,000-request persistent-worker soak tests.
 *
 * Proves that atomic FileCache locking, Idempotency-Key locks, flock-serialized stores, and
 * per-Fiber ExecutionCapabilities hold under real parallel PHP processes and long-lived workers.
 */
final class ConcurrencyAndSoakSecurityTest extends TestCase
{
    public function testMultiProcessConcurrentTotpReplayOnlyAllowsExactlyOneWinner(): void
    {
        $tmp = $this->makeTempDir('mp_totp');
        try {
            $cacheDir = $tmp . DIRECTORY_SEPARATOR . 'cache';
            mkdir($cacheDir, 0o777, true);
            $barrier = $tmp . DIRECTORY_SEPARATOR . 'go.flag';
            $workerScript = $tmp . DIRECTORY_SEPARATOR . 'worker.php';

            $totp = new Totp();
            $secret = Totp::generateSecret();
            $ts = 1_700_000_060;
            $code = $totp->code($secret, $ts);

            $autoload = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            $php = <<<'PHP'
<?php
declare(strict_types=1);
require $argv[1];
$cacheDir = $argv[2];
$barrier = $argv[3];
$secret = $argv[4];
$code = $argv[5];
$ts = (int) $argv[6];

$cache = new \Kongono\Core\Cache\FileCache($cacheDir);
$totp = new \Kongono\Security\Totp();

$deadline = microtime(true) + 10.0;
while (!is_file($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

$ok = $totp->verify($secret, $code, window: 1, timestamp: $ts, replayCache: $cache);
echo $ok ? "WIN\n" : "DENIED\n";
PHP;
            file_put_contents($workerScript, $php);

            $outputs = $this->runParallelPhpWorkers(
                8,
                $barrier,
                [$workerScript, $autoload, $cacheDir, $barrier, $secret, $code, (string) $ts],
            );

            $wins = count(array_filter($outputs, static fn(string $o): bool => 'WIN' === trim($o)));
            $denied = count(array_filter($outputs, static fn(string $o): bool => 'DENIED' === trim($o)));

            self::assertSame(1, $wins, 'Exactly 1 concurrent process may redeem a TOTP code. Outputs: ' . implode(', ', $outputs));
            self::assertSame(7, $denied, 'All 7 competing processes must be rejected. Outputs: ' . implode(', ', $outputs));
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testMultiProcessConcurrentIdempotencyKeyOnlyExecutesHandlerOnce(): void
    {
        $tmp = $this->makeTempDir('mp_idemp');
        try {
            $cacheDir = $tmp . DIRECTORY_SEPARATOR . 'cache';
            mkdir($cacheDir, 0o777, true);
            $barrier = $tmp . DIRECTORY_SEPARATOR . 'go.flag';
            $counterFile = $tmp . DIRECTORY_SEPARATOR . 'exec_count.log';
            $workerScript = $tmp . DIRECTORY_SEPARATOR . 'worker.php';
            $autoload = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

            $php = <<<'PHP'
<?php
declare(strict_types=1);
require $argv[1];
$cacheDir = $argv[2];
$barrier = $argv[3];
$counterFile = $argv[4];

$cache = new \Kongono\Core\Cache\FileCache($cacheDir);
$mw = new \Kongono\Http\Middleware\IdempotencyMiddleware($cache);
$req = \Kongono\Http\Request::json('POST', '/api/charges', ['amount' => 5000], ['Idempotency-Key' => 'charge-race-key-1']);
$handler = new class ($counterFile) implements \Psr\Http\Server\RequestHandlerInterface {
    public function __construct(private readonly string $counterFile) {}
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        file_put_contents($this->counterFile, "1\n", FILE_APPEND | LOCK_EX);
        usleep(40_000);
        return \Kongono\Http\Response::json(['charged' => true], 201);
    }
};

$deadline = microtime(true) + 10.0;
while (!is_file($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

try {
    $res = $mw->process($req, $handler);
    echo 'HTTP:' . $res->getStatusCode() . ':' . ($res->getHeaderLine('Idempotency-Replayed') ?: 'fresh') . "\n";
} catch (\Kongono\Http\HttpException $e) {
    echo 'HTTP:' . $e->statusCode . ":exception\n";
}
PHP;
            file_put_contents($workerScript, $php);

            $outputs = $this->runParallelPhpWorkers(
                6,
                $barrier,
                [$workerScript, $autoload, $cacheDir, $barrier, $counterFile],
            );

            self::assertFileExists($counterFile);
            $lines = array_values(array_filter(explode("\n", trim((string) file_get_contents($counterFile)))));
            self::assertCount(1, $lines, 'IdempotencyMiddleware handler must execute strictly ONCE under 6 concurrent workers. Outputs: ' . implode(', ', $outputs));
            foreach ($outputs as $out) {
                self::assertMatchesRegularExpression('/\AHTTP:(?:201|409):/', trim($out));
            }
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testMultiProcessConcurrentStarterAppRegistrationRejectsDuplicateEmails(): void
    {
        $tmp = $this->makeTempDir('mp_register');
        try {
            $storageDir = $tmp . DIRECTORY_SEPARATOR . 'storage';
            mkdir($storageDir, 0o777, true);
            $barrier = $tmp . DIRECTORY_SEPARATOR . 'go.flag';
            $workerScript = $tmp . DIRECTORY_SEPARATOR . 'worker.php';
            $autoload = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

            $php = <<<'PHP'
<?php
declare(strict_types=1);
require $argv[1];
$storageDir = $argv[2];
$barrier = $argv[3];

$store = new \StarterApp\Domain\UserStore(
    new \Kongono\Storage\LocalStorage($storageDir),
    new \Kongono\Security\PasswordHasher(),
);

$deadline = microtime(true) + 10.0;
while (!is_file($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

try {
    $store->register('Race User', 'race@kongono.dev', 'super-secret-pass-123');
    echo "CREATED\n";
} catch (\RuntimeException) {
    echo "DUPLICATE\n";
}
PHP;
            file_put_contents($workerScript, $php);

            $outputs = $this->runParallelPhpWorkers(
                8,
                $barrier,
                [$workerScript, $autoload, $storageDir, $barrier],
            );

            $created = count(array_filter($outputs, static fn(string $o): bool => 'CREATED' === trim($o)));
            $dups = count(array_filter($outputs, static fn(string $o): bool => 'DUPLICATE' === trim($o)));

            self::assertSame(1, $created, 'Strictly 1 concurrent registration may succeed. Outputs: ' . implode(', ', $outputs));
            self::assertSame(7, $dups, 'All 7 duplicate registrations must fail cleanly. Outputs: ' . implode(', ', $outputs));

            $usersJson = json_decode((string) file_get_contents($storageDir . '/data/users.json'), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($usersJson);
            self::assertCount(1, $usersJson);
            self::assertArrayHasKey('race@kongono.dev', $usersJson);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testPersistentWorkerTwoThousandRequestSoakHasZeroLeaksAndFlatMemory(): void
    {
        $starterRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'starter-app';
        $this->removeDir($starterRoot . DIRECTORY_SEPARATOR . 'var');
        $this->removeDir($starterRoot . DIRECTORY_SEPARATOR . 'storage');

        try {
            /** @var App $app */
            $app = require $starterRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
            $signer = $app->container()->make(TokenSigner::class);
            $validToken = $signer->sign(['sub' => 'soak-user', 'capabilities' => ['notes.read', 'notes.write']], ttlSeconds: 3600);

            $warmupMemory = 0;
            $totalRequests = 2000;

            for ($i = 1; $i <= $totalRequests; ++$i) {
                // Distinct per-client IP simulates real multi-client traffic without tripping starter-app's 120/min per-IP RateLimitMiddleware
                $serverParams = ['REMOTE_ADDR' => sprintf('198.51.%d.%d', ($i >> 8) & 255, $i & 255)];
                $case = $i % 6;
                if (0 === $case) {
                    $res = $app->handle(Request::create('GET', '/health', serverParams: $serverParams));
                    self::assertSame(200, $res->getStatusCode());
                } elseif (1 === $case) {
                    $res = $app->handle(Request::create('GET', '/', serverParams: $serverParams));
                    self::assertSame(200, $res->getStatusCode());
                } elseif (2 === $case) {
                    // Authenticated JSON request
                    $res = $app->handle(Request::create('GET', '/api/notes', ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $validToken], serverParams: $serverParams));
                    self::assertSame(200, $res->getStatusCode());
                } elseif (3 === $case) {
                    // Immediately followed by unauthenticated mutating request -> zero Principal bleed!
                    $req401 = Request::create(
                        'POST',
                        '/api/notes',
                        ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                        '{"title":"Bleed","body":"Check"}',
                        serverParams: $serverParams,
                    );
                    $res = $app->handle($req401);
                    self::assertSame(401, $res->getStatusCode());
                } elseif (4 === $case) {
                    // Validation 422 failure under valid auth
                    $req422 = Request::create(
                        'POST',
                        '/api/notes',
                        ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $validToken],
                        '{"title":"x","body":""}',
                        serverParams: $serverParams,
                    );
                    $res = $app->handle($req422);
                    self::assertSame(422, $res->getStatusCode());
                } else {
                    // Interleaved Fiber capability check + 404 route
                    $fiberBlocked = false;
                    $f = new Fiber(static function () use (&$fiberBlocked): void {
                        ExecutionCapabilities::withActive(
                            new ExecutionCapabilities(readOnlyDb: true, noOutboundHttp: true),
                            static function () use (&$fiberBlocked): void {
                                try {
                                    SafeHttpClient::fake(['https://soak.example.test/*' => ['status' => 200, 'body' => 'ok']])
                                        ->request('GET', 'https://soak.example.test/ping');
                                } catch (CapabilityException) {
                                    $fiberBlocked = true;
                                }
                            },
                        );
                    });
                    $f->start();
                    self::assertTrue($fiberBlocked);
                    $res = $app->handle(Request::create('GET', '/missing-' . ($i % 50), serverParams: $serverParams));
                    self::assertSame(404, $res->getStatusCode());
                }

                self::assertSame(0, $app->kernel()->activeOperationCount(), "Operation leak at request #{$i}");
                self::assertNull(ExecutionCapabilities::current(), "ExecutionCapabilities leak at request #{$i}");

                if (500 === $i) {
                    gc_collect_cycles();
                    $warmupMemory = memory_get_usage();
                }
            }

            gc_collect_cycles();
            $endMemory = memory_get_usage();
            $growthBytes = $endMemory - $warmupMemory;
            self::assertLessThan(
                4 * 1024 * 1024,
                $growthBytes,
                sprintf('Persistent worker leaked %.2f MiB across 1,500 post-warmup requests.', $growthBytes / 1_048_576),
            );
        } finally {
            $this->removeDir($starterRoot . DIRECTORY_SEPARATOR . 'var');
            $this->removeDir($starterRoot . DIRECTORY_SEPARATOR . 'storage');
        }
    }

    /**
     * Spawn `$count` parallel PHP worker processes, wait until they are all launched, release the
     * shared `$barrier` simultaneously, and return each worker's stdout.
     *
     * @param list<string> $scriptAndArgs
     * @return list<string>
     */
    private function runParallelPhpWorkers(int $count, string $barrier, array $scriptAndArgs): array
    {
        $cmd = [PHP_BINARY, ...$scriptAndArgs];
        /** @var list<array{proc: resource, stdout: resource, stderr: resource}> $workers */
        $workers = [];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        for ($i = 0; $i < $count; ++$i) {
            $proc = proc_open($cmd, $descriptors, $pipes);
            self::assertIsResource($proc, 'Failed to spawn parallel PHP test worker.');
            fclose($pipes[0]);
            $workers[] = ['proc' => $proc, 'stdout' => $pipes[1], 'stderr' => $pipes[2]];
        }

        // Brief spin so all child PHP runtimes finish autoloading and hit the barrier loop together
        usleep(250_000);
        file_put_contents($barrier, 'GO');

        $outputs = [];
        foreach ($workers as $w) {
            $stdout = stream_get_contents($w['stdout']);
            $stderr = stream_get_contents($w['stderr']);
            fclose($w['stdout']);
            fclose($w['stderr']);
            $code = proc_close($w['proc']);
            self::assertSame(0, $code, sprintf('Parallel worker exited with code %d. stderr: %s', $code, (string) $stderr));
            $outputs[] = (string) $stdout;
        }

        return $outputs;
    }

    private function makeTempDir(string $prefix): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kgn_conc_' . $prefix . '_' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);

        return $dir;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var \SplFileInfo $item */
        foreach ($it as $item) {
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
