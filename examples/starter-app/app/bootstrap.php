<?php

declare(strict_types=1);

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Diagnostics\IncidentReplayer;
use Kongono\Cli\Diagnostics\IncidentTestGenerator;
use Kongono\Cli\Proof\MasterProver;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\FileCache;
use Kongono\Core\Config;
use Kongono\Core\Dev\DevMailboxInterface;
use Kongono\Core\Dev\FileDevMailbox;
use Kongono\Core\EnvLoader;
use Kongono\Core\Features\FeatureManager;
use Kongono\Core\Health\HealthCheck;
use Kongono\Core\I18n\Translator;
use Kongono\Core\Log\Logger;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Middleware\CompressionMiddleware;
use Kongono\Http\Middleware\StaticFilesMiddleware;
use Kongono\Http\Realtime\EventBus;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\Queue;
use Kongono\Jobs\Scheduler\Scheduler;
use Kongono\Jobs\Worker;
use Kongono\Mail\Address;
use Kongono\Mail\DevMailboxTransport;
use Kongono\Mail\LogTransport;
use Kongono\Mail\Mailer;
use Kongono\Security\Encryptor;
use Kongono\Security\Middleware\BearerAuthMiddleware;
use Kongono\Security\Middleware\CorsMiddleware;
use Kongono\Security\Middleware\CsrfMiddleware;
use Kongono\Security\Middleware\RateLimitMiddleware;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;
use Kongono\Security\PasswordHasher;
use Kongono\Security\Principal;
use Kongono\Security\PrincipalKind;
use Kongono\Security\SignedUrl;
use Kongono\Security\TokenSigner;
use Kongono\Storage\LocalStorage;
use Kongono\Storage\StorageInterface;
use Kongono\View\ViewEngine;
use Psr\Log\LoggerInterface;
use StarterApp\Domain\NoteStore;
use StarterApp\Domain\UserStore;
use StarterApp\Http\AuthController;
use StarterApp\Http\NoteController;
use StarterApp\Http\RequestTimingMiddleware;

$baseDir = dirname(__DIR__);
EnvLoader::load($baseDir . DIRECTORY_SEPARATOR . '.env');

$cache = new FileCache($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'app');
$storage = new LocalStorage($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'storage');
$logger = new Logger('starter-app', retainInMemory: true);
$metrics = new Metrics();
$queueDriver = new MemoryQueueDriver();
$queue = new Queue($queueDriver);
$appEnv = strtolower(Config::env('APP_ENV') ?? 'local');
$isProdEnv = in_array($appEnv, ['production', 'prod'], true);
$debug = filter_var(Config::env('APP_DEBUG') ?? 'false', FILTER_VALIDATE_BOOL);
$configuredKey = Config::env('APP_KEY');
$trimmedKey = null !== $configuredKey ? trim($configuredKey) : '';
$decodedKeyBytes = str_starts_with($trimmedKey, 'base64:')
    ? base64_decode(substr($trimmedKey, 7), true)
    : $trimmedKey;
$keyIsPlaceholder = '' === $trimmedKey
    || str_contains(strtoupper($trimmedKey), 'CHANGE_ME')
    || str_contains(strtoupper($trimmedKey), 'REPLACE_WITH')
    || str_contains(strtolower($trimmedKey), 'example');
if ($isProdEnv && ($keyIsPlaceholder || !is_string($decodedKeyBytes) || strlen($decodedKeyBytes) < 32)) {
    throw new \RuntimeException('APP_KEY must be configured with at least 32 random bytes in production (run `php bin/kongo key:generate`).');
}
$appKey = !$keyIsPlaceholder && is_string($decodedKeyBytes) && strlen($decodedKeyBytes) >= 16
    ? $trimmedKey
    : 'ephemeral-' . bin2hex(random_bytes(24));
$secureCookies = filter_var(Config::env('SESSION_SECURE') ?? ($isProdEnv ? 'true' : 'false'), FILTER_VALIDATE_BOOL);
$signer = new TokenSigner($appKey);
$encryptor = new Encryptor($appKey);
$signedUrl = new SignedUrl($appKey);
$hasher = new PasswordHasher();

$translator = (new Translator('en'))
    ->addMessages('en', [
        'app.title' => 'Kongono v1 Starter',
        'notes.count' => 'No notes yet|1 note|:count notes',
    ])
    ->addMessages('ja', [
        'app.title' => 'Kongono v1 スターター',
        'notes.count' => 'メモなし|1件のメモ|:count件のメモ',
    ]);

$health = (new HealthCheck())
    ->check('cache', static function () use ($cache): bool {
        $probeKey = 'health:probe:' . bin2hex(random_bytes(8));
        try {
            $cache->set($probeKey, 'ok', 15);

            return 'ok' === $cache->get($probeKey);
        } finally {
            $cache->delete($probeKey);
        }
    })
    ->check('storage', static function () use ($storage): bool {
        $probeKey = '_health/probe-' . bin2hex(random_bytes(8)) . '.txt';
        try {
            $storage->put($probeKey, 'ok', 'text/plain');

            return 'ok' === $storage->get($probeKey);
        } finally {
            $storage->delete($probeKey);
        }
    });

$scheduler = new Scheduler();
$scheduler->call('heartbeat', static function () use ($logger): void {
    $logger->info('Scheduled heartbeat ran.');
})->everyMinute();

$inspector = new DevInspector();
$eventBus = new EventBus();
$features = (new FeatureManager($cache))
    ->define('beta_insights', static fn(?string $scope): bool => 'beta-tester' === $scope);
$bearerAuth = new BearerAuthMiddleware(static function (string $token) use ($signer): ?Principal {
    $claims = $signer->verify($token);
    if (null === $claims || !is_string($claims['sub'] ?? null)) {
        return null;
    }

    /** @var list<string> $capabilities */
    $capabilities = ['notes.read', 'notes.write'];
    if (is_array($claims['capabilities'] ?? null)) {
        $claimed = array_values(array_filter($claims['capabilities'], static fn(mixed $v): bool => is_string($v) && '' !== $v));
        if ([] !== $claimed) {
            $capabilities = $claimed;
        }
    }

    return new Principal($claims['sub'], PrincipalKind::Human, $capabilities, $claims);
});

$app = App::create($baseDir, name: 'Kongono Starter App')
    ->config(['app' => ['debug' => $debug]])
    ->views(autoReload: $debug)
    ->instance(CacheInterface::class, $cache)
    ->instance(StorageInterface::class, $storage)
    ->instance(LoggerInterface::class, $logger)
    ->instance(Metrics::class, $metrics)
    ->instance(HealthCheck::class, $health)
    ->instance(Translator::class, $translator)
    ->instance(FeatureManager::class, $features)
    ->instance(Scheduler::class, $scheduler)
    ->instance(TokenSigner::class, $signer)
    ->instance(Encryptor::class, $encryptor)
    ->instance(SignedUrl::class, $signedUrl)
    ->instance(PasswordHasher::class, $hasher)
    ->instance(DevInspector::class, $inspector)
    ->instance(EventBus::class, $eventBus)
    ->instance(BearerAuthMiddleware::class, $bearerAuth)
    ->instance(RateLimitMiddleware::class, new RateLimitMiddleware($cache, maxRequests: 20, windowSeconds: 60))
    ->singleton(NoteStore::class, NoteStore::class)
    ->singleton(UserStore::class, UserStore::class)
    ->instance(Queue::class, $queue)
    ->controller(NoteController::class)
    ->controller(AuthController::class);

$tryAi = static function (): ?AiClient {
    try {
        return AiClient::fromEnv();
    } catch (AiException) {
        return null;
    }
};

$app->recordIncidents();

$app->middleware(
    new DevDashboardMiddleware(
        app: $app,
        inspector: $inspector,
        oshieHandler: static function (string $question, ?string $lesson) use ($app, $baseDir, $tryAi): string {
            $ctx = new ProjectContext($baseDir, $app->graph());
            $oshie = new OshieTutor($tryAi());

            return null !== $lesson ? $oshie->teachLesson($lesson, $ctx) : $oshie->ask('' !== $question ? $question : 'Explain routing in Kongono.', $ctx);
        },
        hanteiHandler: static function (string $diagnostic) use ($app, $baseDir, $tryAi): string {
            $ctx = new ProjectContext($baseDir, $app->graph());

            return (new HanteiDebugger($tryAi()))->diagnose($diagnostic, $ctx);
        },
        chieHandler: static function (string $instruction) use ($app, $baseDir, $tryAi): array {
            $ai = $tryAi();
            if (null === $ai) {
                return [
                    'summary' => 'Chie preview requires an AI provider. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST in .env.',
                    'diffs' => [],
                    'apply_command' => sprintf('php bin/kongo chie %s --apply', escapeshellarg($instruction)),
                ];
            }
            $ctx = new ProjectContext($baseDir, $app->graph());
            $plan = (new ChieBuilder($ai))->build($instruction, $ctx, apply: false);
            $diffs = [];
            foreach ($plan['changes'] as $change) {
                $before = null;
                try {
                    $before = $ctx->readFileSafe($change['path']);
                } catch (\Throwable) {
                    $before = null;
                }
                $diffs[] = [
                    'path' => $change['path'],
                    'action' => $change['action'],
                    'explanation' => $change['explanation'],
                    'diff' => \Kongono\Core\Diagnostics\SecretScrubber::scrub(
                        DiffRenderer::render($change['path'], $before, \Kongono\Core\Diagnostics\SecretScrubber::scrub($change['content'])),
                    ),
                ];
            }

            return [
                'summary' => sprintf('Chie (%s / %s): %s', $plan['provider'], $plan['model'], $plan['summary']),
                'diffs' => $diffs,
                'next_steps' => $plan['next_steps'],
                'apply_command' => sprintf('php bin/kongo chie %s --apply', escapeshellarg($instruction)),
            ];
        },
        proofsHandler: static fn(): array => (new MasterProver())->proveAll($baseDir, $app),
        incidentReplayHandler: static function (string $reference) use ($app, $baseDir): array {
            $replayer = new IncidentReplayer();
            $record = $replayer->resolveStore($baseDir, $app)->find($reference);
            if (null === $record) {
                return ['error' => sprintf('Incident "%s" not found.', $reference)];
            }

            return $replayer->replay($app, $record);
        },
        incidentTestHandler: static function (string $reference) use ($app, $baseDir): array {
            $record = (new IncidentReplayer())->resolveStore($baseDir, $app)->find($reference);
            if (null === $record) {
                return ['error' => sprintf('Incident "%s" not found.', $reference)];
            }

            return (new IncidentTestGenerator())->generate($record);
        },
        hotReload: true,
        schemaHandler: static fn(): array => (new ProjectContext($baseDir, $app->graph(), $app))->inspectSchema(),
    ),
    new CompressionMiddleware(),
    new StaticFilesMiddleware($baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets', '/assets/'),
    new SecurityHeadersMiddleware(),
    new CorsMiddleware(allowedOrigins: []),
    new RateLimitMiddleware($cache, maxRequests: 120, windowSeconds: 60),
    new SessionMiddleware(new CacheSessionStore($cache), secure: $secureCookies),
    new CsrfMiddleware($signer, exemptPrefixes: ['/api/', '/_kongono/', '/mcp']),
    new RequestTimingMiddleware($metrics),
);

$viewsWithI18n = $app->container()->make(ViewEngine::class)->withTranslator($translator->get(...));
$app->instance(ViewEngine::class, $viewsWithI18n);

$devMailbox = new FileDevMailbox($baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'mailbox.json');
$app->instance(DevMailboxInterface::class, $devMailbox);

$mailer = new Mailer(
    $app->isDebug() ? new DevMailboxTransport($devMailbox, $logger) : new LogTransport($logger),
    new Address('no-reply@kongono.local', 'Kongono Starter'),
    $viewsWithI18n,
);
$app->instance(Mailer::class, $mailer);

$worker = (new Worker($queueDriver, $app->kernel(), $app->container()))
    ->register('note.indexed', static function (Job $job) use ($logger): JobOutcome {
        $logger->info('Indexed note job', ['job_id' => $job->id, 'payload' => $job->payload]);

        return JobOutcome::Completed;
    });
$app->instance(Worker::class, $worker);

return $app;
