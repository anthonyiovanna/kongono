<?php

declare(strict_types=1);

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\FileCache;
use Kongono\Core\Config;
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
$debug = filter_var(Config::env('APP_DEBUG') ?? 'false', FILTER_VALIDATE_BOOL);
$configuredKey = Config::env('APP_KEY');
if ('production' === $appEnv && (null === $configuredKey || strlen(trim($configuredKey)) < 16)) {
    throw new \RuntimeException('APP_KEY must be configured with at least 16 bytes in production (run `php bin/kongo key:generate`).');
}
$appKey = null !== $configuredKey && strlen(trim($configuredKey)) >= 16
    ? trim($configuredKey)
    : 'ephemeral-' . bin2hex(random_bytes(24));
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
        $cache->set('health:probe', 'ok', 15);
        $ok = 'ok' === $cache->get('health:probe');
        $cache->delete('health:probe');

        return $ok;
    })
    ->check('storage', static function () use ($storage): bool {
        $storage->put('_health/probe.txt', 'ok', 'text/plain');
        $ok = 'ok' === $storage->get('_health/probe.txt');
        $storage->delete('_health/probe.txt');

        return $ok;
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

    return new Principal($claims['sub'], PrincipalKind::Human, ['notes.read', 'notes.write'], $claims);
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
                    'diff' => DiffRenderer::render($change['path'], $before, $change['content']),
                ];
            }

            return [
                'summary' => sprintf('Chie (%s / %s): %s', $plan['provider'], $plan['model'], $plan['summary']),
                'diffs' => $diffs,
                'next_steps' => $plan['next_steps'],
                'apply_command' => sprintf('php bin/kongo chie %s --apply', escapeshellarg($instruction)),
            ];
        },
    ),
    new CompressionMiddleware(),
    new StaticFilesMiddleware($baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets', '/assets/'),
    new SecurityHeadersMiddleware(),
    new CorsMiddleware(allowedOrigins: []),
    new RateLimitMiddleware($cache, maxRequests: 120, windowSeconds: 60),
    new SessionMiddleware(new CacheSessionStore($cache)),
    new CsrfMiddleware($signer, exemptPrefixes: ['/api/', '/_kongono/', '/mcp']),
    new RequestTimingMiddleware($metrics),
);

$viewsWithI18n = $app->container()->make(ViewEngine::class)->withTranslator($translator->get(...));
$app->instance(ViewEngine::class, $viewsWithI18n);

$mailer = new Mailer(
    new LogTransport($logger),
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
