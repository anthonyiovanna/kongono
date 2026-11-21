<?php

declare(strict_types=1);

use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\FileCache;
use Kongono\Core\EnvLoader;
use Kongono\Core\Log\Logger;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\App;
use Kongono\Http\Middleware\StaticFilesMiddleware;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\Queue;
use Kongono\Jobs\Scheduler\Scheduler;
use Kongono\Jobs\Worker;
use Kongono\Security\Middleware\CorsMiddleware;
use Kongono\Security\Middleware\RateLimitMiddleware;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;
use Kongono\Storage\LocalStorage;
use Kongono\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use StarterApp\Domain\NoteStore;
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
$scheduler = new Scheduler();
$scheduler->call('heartbeat', static function () use ($logger): void {
    $logger->info('Scheduled heartbeat ran.');
})->everyMinute();

$app = App::create($baseDir, name: 'Kongono Starter App')
    ->config(['app' => ['debug' => true]])
    ->views()
    ->instance(CacheInterface::class, $cache)
    ->instance(StorageInterface::class, $storage)
    ->instance(LoggerInterface::class, $logger)
    ->instance(Metrics::class, $metrics)
    ->instance(Scheduler::class, $scheduler)
    ->singleton(NoteStore::class, NoteStore::class)
    ->instance(Queue::class, $queue)
    ->middleware(
        new StaticFilesMiddleware($baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets', '/assets/'),
        new SecurityHeadersMiddleware(),
        new CorsMiddleware(allowedOrigins: ['*']),
        new RateLimitMiddleware($cache, maxRequests: 120, windowSeconds: 60),
        new SessionMiddleware(new CacheSessionStore($cache)),
        new RequestTimingMiddleware($metrics),
    )
    ->controller(NoteController::class);

$worker = (new Worker($queueDriver, $app->kernel(), $app->container()))
    ->register('note.indexed', static function (Job $job) use ($logger): JobOutcome {
        $logger->info('Indexed note job', ['job_id' => $job->id, 'payload' => $job->payload]);

        return JobOutcome::Completed;
    });
$app->instance(Worker::class, $worker);

return $app;
