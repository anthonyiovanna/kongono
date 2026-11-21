<?php

declare(strict_types=1);

use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\FileCache;
use Kongono\Core\Config;
use Kongono\Core\EnvLoader;
use Kongono\Core\Health\HealthCheck;
use Kongono\Core\I18n\Translator;
use Kongono\Core\Log\Logger;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
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
$appKey = Config::env('APP_KEY') ?? 'kongono-starter-dev-key-change-in-prod';
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
$bearerAuth = new BearerAuthMiddleware(static function (string $token) use ($signer): ?Principal {
    $claims = $signer->verify($token);
    if (null === $claims || !is_string($claims['sub'] ?? null)) {
        return null;
    }

    return new Principal($claims['sub'], PrincipalKind::Human, ['notes.read', 'notes.write'], $claims);
});

$app = App::create($baseDir, name: 'Kongono Starter App')
    ->config(['app' => ['debug' => true]])
    ->views()
    ->instance(CacheInterface::class, $cache)
    ->instance(StorageInterface::class, $storage)
    ->instance(LoggerInterface::class, $logger)
    ->instance(Metrics::class, $metrics)
    ->instance(HealthCheck::class, $health)
    ->instance(Translator::class, $translator)
    ->instance(Scheduler::class, $scheduler)
    ->instance(TokenSigner::class, $signer)
    ->instance(Encryptor::class, $encryptor)
    ->instance(SignedUrl::class, $signedUrl)
    ->instance(PasswordHasher::class, $hasher)
    ->instance(DevInspector::class, $inspector)
    ->instance(EventBus::class, $eventBus)
    ->instance(BearerAuthMiddleware::class, $bearerAuth)
    ->singleton(NoteStore::class, NoteStore::class)
    ->singleton(UserStore::class, UserStore::class)
    ->instance(Queue::class, $queue)
    ->controller(NoteController::class)
    ->controller(AuthController::class);

$app->middleware(
    new DevDashboardMiddleware($app, $inspector),
    new StaticFilesMiddleware($baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets', '/assets/'),
    new SecurityHeadersMiddleware(),
    new CorsMiddleware(allowedOrigins: ['*']),
    new RateLimitMiddleware($cache, maxRequests: 120, windowSeconds: 60),
    new SessionMiddleware(new CacheSessionStore($cache)),
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
