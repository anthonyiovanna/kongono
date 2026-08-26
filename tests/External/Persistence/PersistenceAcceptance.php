<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Persistence;

use Kongono\Composition\Exception\RetiredCompositionBoundaryException;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseConnector;
use Kongono\Database\DatabaseExecutor;
use Kongono\Database\DatabaseParameter;
use Kongono\Database\DatabaseRow;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\DatabaseValue;
use Kongono\Database\ResultExpectation;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionOptions;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DestructiveMigrationPolicy;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\MigrationCatalog;
use Kongono\DatabaseMigration\MigrationDefinition;
use Kongono\DatabaseMigration\MigrationDirectionPlan;
use Kongono\DatabaseMigration\MigrationRisk;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseMigration\SchemaColumnType;
use Kongono\DatabaseMigrationPgsql\PostgreSql17MigrationExecutor;
use Kongono\DatabaseMigrationPgsql\PostgreSqlMigrationConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConnector;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;
use Kongono\Persistence\AggregateCascadeExecutor;
use Kongono\Persistence\AggregateCascadePlan;
use Kongono\Persistence\AggregateLoadExecutor;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\Persistence\CascadeAction;
use Kongono\Persistence\ChangeExecutionOutcome;
use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ChangeSet;
use Kongono\Persistence\ConcurrencyToken;
use Kongono\Persistence\ContextState;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;
use Kongono\Persistence\Exception\InvalidPersistenceStateException;
use Kongono\Persistence\Exception\PersistenceUncertainStateException;
use Kongono\Persistence\FlushOutcome;
use Kongono\Persistence\FlushResult;
use Kongono\Persistence\LoadedAggregate;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\PersistenceChange;
use Kongono\Persistence\PersistenceChangeExecutor;
use Kongono\Persistence\PersistenceStateAdapter;
use KongonoPersistenceMinimal\Domain\Account;
use KongonoPersistenceMinimal\Domain\Profile;
use KongonoPersistenceMinimal\PersistenceOperations;
use KongonoPersistenceMinimal\Runtime;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/** Executes the closed Phase 7 whole-platform PostgreSQL acceptance profile. */
final class PersistenceAcceptance
{
    private const ROLE = 'kongono_phase7h_acceptance';

    private int $cases = 0;
    private string $stage = 'preflight';
    /** @var array<string, array{samples:int,min_ns:int,p50_ns:int,p95_ns:int,max_ns:int}> */
    private array $performance = [];

    /** @param list<mixed> $arguments @param resource $stdout @param resource $stderr */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        if (!is_resource($stdout) || !is_resource($stderr)) {
            return 2;
        }
        if (1 !== count($arguments) || !is_string($arguments[0])) {
            $this->write($stderr, "persistence_acceptance_failed\n");

            return 2;
        }

        $fixture = new PersistenceFixture();
        $failure = null;
        try {
            $this->stage = 'provider';
            $fixture->start($arguments[0]);
            $this->case(true);
            $connector = $fixture->connector();
            $session = $connector->openSession();
            try {
                $this->provePostgreSql18($session, $connector);
                $this->stage = 'application_build';
                $application = $fixture->prepareApplication();
                $this->case(448 === strlen(implode('', $application['identities'])));
                $this->case(array_all($application['outputs'], static fn(string $path): bool => is_file($path)));
                $this->stage = 'evidence_refusal';
                $this->cases += $fixture->proveEvidenceFailures($application['application'], $application['outputs']);
                $fixture->proveChangedDuringRead($application['application']);
                $this->case(true);
                $this->stage = 'runtime_child';
                $runtime = $fixture->runRuntimeChild($application['application']);
                $this->cases += $runtime['case_count'];
                $this->performance = $runtime['performance'];
            } finally {
                $session->close();
            }
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                $fixture->cleanup();
                $this->case(true);
            } catch (Throwable $cleanup) {
                $failure ??= $cleanup;
            }
        }

        if (null !== $failure) {
            $message = $failure->getMessage();
            $token = 1 === preg_match('/^[a-z0-9_]+$/D', $message) ? $message : 'unexpected_failure';
            $this->write($stderr, 'persistence_acceptance_failed_' . $this->stage . '_' . $token . "\n");

            return 1;
        }

        $json = json_encode([
            'status' => 'ok',
            'profile' => 'kongono-persistence-pdo_pgsql-postgresql-18',
            'server_major' => 18,
            'case_count' => $this->cases,
            'cleanup' => 'complete',
            'performance' => [
                'schema' => 'kongono.phase7.persistence-performance-baseline',
                'version' => 1,
                'timer' => 'hrtime',
                'unit' => 'nanoseconds',
                'warmup_iterations' => PersistencePerformance::WARMUP_ITERATIONS,
                'measured_samples' => PersistencePerformance::MEASURED_SAMPLES,
                'percentile_method' => 'deterministic-nearest-rank',
                'scenarios' => $this->performance,
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->write($stdout, $json . "\n");

        return 0;
    }

    /** @param resource $stdout @param resource $stderr */
    public function runRuntimeChild(string $applicationRoot, int $port, mixed $stdout, mixed $stderr): int
    {
        if (!is_resource($stdout) || !is_resource($stderr) || $port < 1 || $port > 65_535) {
            return 2;
        }

        $connector = new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', $port, 'postgres', self::ROLE, ''));
        $control = null;
        $failure = null;
        try {
            $control = $connector->openSession();
            $this->provePersistenceRuntime($applicationRoot, $connector, $control);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                $control?->close();
            } catch (Throwable $cleanup) {
                $failure ??= $cleanup;
            }
        }

        if (null !== $failure) {
            $message = $failure->getMessage();
            if (1 === preg_match('/^[a-z0-9_]+$/D', $message)) {
                $token = $message;
            } else {
                $cause = $failure;
                while (null !== $cause->getPrevious()) {
                    $cause = $cause->getPrevious();
                }
                $leaf = strtolower((new \ReflectionClass($cause))->getShortName());
                $token = 1 === preg_match('/^[a-z][a-z0-9_]*$/D', $leaf) ? 'unexpected_' . $leaf : 'unexpected_failure';
            }
            $this->write($stderr, 'persistence_runtime_failed_' . $this->stage . '_' . $token . "\n");

            return 1;
        }

        $json = json_encode(
            ['case_count' => $this->cases, 'performance' => $this->performance],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $this->write($stdout, $json . "\n");

        return 0;
    }

    private function provePostgreSql18(DatabaseSession $session, PdoPgsqlConnector $connector): void
    {
        $version = $this->scalarInteger($session, "SELECT current_setting('server_version_num')::int8");
        $this->case(18 === intdiv($version, 10_000));
        $this->stage = 'migration';
        $suffix = bin2hex(random_bytes(6));
        $table = new SqlQualifiedName([new SqlIdentifier('p7h_' . $suffix)]);
        $history = new SqlQualifiedName([new SqlIdentifier('p7h_history_' . $suffix)]);
        $id = new SqlIdentifier('record_id');
        $definition = new MigrationDefinition(
            1,
            'postgresql18_compatibility',
            new MigrationDirectionPlan([new CreateTable($table, [new SchemaColumn($id, SchemaColumnType::Int64, false)], [$id])], MigrationRisk::Safe),
            new MigrationDirectionPlan([new DropTable($table)], MigrationRisk::Destructive),
        );
        $executor = new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration($history, random_int(-2_000_000_000, 2_000_000_000)));
        $applied = $executor->applyPending($session, new MigrationCatalog([$definition]), DestructiveMigrationPolicy::Deny);
        $this->case(1 === $applied->changedMigrationCount() && $this->relationExists($session, $table));
        $reverted = $executor->revertLast($session, new MigrationCatalog([$definition]), DestructiveMigrationPolicy::Allow);
        $this->case(0 === $reverted->resultingAppliedCount() && !$this->relationExists($session, $table));

        $this->stage = 'direct_sql';
        $this->completion($session, 'DROP TABLE IF EXISTS public.profiles');
        $this->completion($session, 'DROP TABLE IF EXISTS public.accounts');
        $this->completion($session, 'CREATE TABLE public.accounts (account_id bigint PRIMARY KEY, name text NOT NULL, version bigint NOT NULL)');
        $this->completion($session, 'CREATE TABLE public.profiles (profile_id bigint PRIMARY KEY, account_id bigint NOT NULL REFERENCES public.accounts(account_id), display_name text NULL)');
        $this->completion($session, "INSERT INTO public.accounts VALUES (1, 'Ada', 1), (2, 'Peer', 1)");
        $this->completion($session, "INSERT INTO public.profiles VALUES (3, 1, 'Third'), (2, 1, 'Second')");
        $this->case(2 === $this->scalarInteger($session, 'SELECT count(*)::int8 FROM public.accounts'));

        $this->stage = 'transaction_visibility';
        $reader = $connector->openSession();
        try {
            $transaction = $session->begin(new TransactionOptions());
            $this->completion($transaction, "INSERT INTO public.accounts VALUES (90, 'Commit', 1)");
            $this->case(0 === $this->scalarInteger($reader, 'SELECT count(*)::int8 FROM public.accounts WHERE account_id = 90'));
            $transaction->commit();
            $this->case(1 === $this->scalarInteger($reader, 'SELECT count(*)::int8 FROM public.accounts WHERE account_id = 90'));

            $rollback = $session->begin(new TransactionOptions());
            $this->completion($rollback, "UPDATE public.accounts SET name = 'Rolled back' WHERE account_id = 90");
            $rollback->rollback();
            $this->case('Commit' === $this->scalarText($reader, 'SELECT name FROM public.accounts WHERE account_id = 90'));
            $this->completion($session, 'DELETE FROM public.accounts WHERE account_id = 90');
        } finally {
            $reader->close();
        }
    }

    private function provePersistenceRuntime(string $applicationRoot, PdoPgsqlConnector $connector, DatabaseSession $control): void
    {
        $this->stage = 'runtime_start';
        require_once $applicationRoot . '/vendor/autoload.php';
        $recording = new RecordingConnector($connector);
        $application = Runtime::start($applicationRoot, $recording);
        $this->case(!$application->isRetired());

        $this->stage = 'runtime_begin_operation';
        $first = $application->beginOperation();
        $firstRoot = $this->root($first->root());
        $this->stage = 'runtime_load_account';
        $firstLoaded = $firstRoot->loadAccount(1);
        if (!$firstLoaded instanceof LoadedAggregate) {
            throw new RuntimeException('initial_load_failed');
        }
        $this->case($firstLoaded->root() instanceof Account);
        $this->stage = 'runtime_state_property';
        $firstState = $this->property($firstRoot, 'state');
        $this->stage = 'runtime_context_create';
        $firstContext = OperationPersistenceContext::forManaged($this->adapter($firstState), $firstLoaded->root());
        $identity = new EntityIdentity(Account::class, 'i:1');
        $this->stage = 'runtime_identity_lookup';
        $this->case($firstLoaded->root() === $firstContext->entityForIdentity($identity));
        $this->case($identity->equals($firstContext->identityOf($firstLoaded->root()) ?? new EntityIdentity(Account::class, 'invalid')));
        $firstSession = $this->session($this->property($firstRoot, 'session'));
        $firstRepository = $this->property($firstRoot, 'accounts');
        $firstSnapshot = $this->adapter($firstState)->snapshot($firstLoaded->root());
        $this->stage = 'runtime_first_close';
        $firstContext->retire();
        $this->case(ContextState::Retired === $firstContext->state());
        $first->close();
        $first->close();

        $this->stage = 'operation_isolation';
        $second = $application->beginOperation();
        $third = $application->beginOperation();
        $secondRoot = $this->root($second->root());
        $thirdRoot = $this->root($third->root());
        $secondSession = $this->session($this->property($secondRoot, 'session'));
        $thirdSession = $this->session($this->property($thirdRoot, 'session'));
        $secondRepository = $this->property($secondRoot, 'accounts');
        $thirdRepository = $this->property($thirdRoot, 'accounts');
        $secondLoaded = $secondRoot->loadAccount(1);
        $thirdLoaded = $thirdRoot->loadAccount(1);
        if (!$secondLoaded instanceof LoadedAggregate || !$thirdLoaded instanceof LoadedAggregate) {
            throw new RuntimeException('sequential_load_failed');
        }
        $this->case($secondRoot !== $firstRoot && $thirdRoot !== $secondRoot);
        $this->case($secondSession !== $firstSession && $thirdSession !== $secondSession);
        $this->case($secondRepository !== $firstRepository && $thirdRepository !== $secondRepository);
        $this->case($secondLoaded->root() !== $firstLoaded->root() && $thirdLoaded->root() !== $secondLoaded->root());
        $this->case($firstSnapshot === $this->adapter($this->property($secondRoot, 'state'))->snapshot($secondLoaded->root()));

        $secondContext = OperationPersistenceContext::forManaged($this->adapter($this->property($secondRoot, 'state')), $secondLoaded->root());
        $thirdContext = OperationPersistenceContext::forManaged($this->adapter($this->property($thirdRoot, 'state')), $thirdLoaded->root());
        $secondContext->retire();
        $this->case(ContextState::Retired === $secondContext->state() && ContextState::Active === $thirdContext->state());
        $second->close();
        $this->case(1 === $this->scalarInteger($thirdSession, 'SELECT 1::int4'));

        $this->stage = 'aggregate_load';
        $loadExecutor = new PostgreSqlAggregateLoadExecutor();
        $loadMethod = new ReflectionMethod($thirdRepository, 'loadWithProfiles');
        $aggregate = $loadMethod->invoke($thirdRepository, $identity, $thirdSession, $loadExecutor);
        if (!$aggregate instanceof LoadedAggregate) {
            throw new RuntimeException('aggregate_load_failed');
        }
        $profiles = $aggregate->relationship('account_profiles');
        $beforeInspection = $loadExecutor->statementCount;
        $profileIds = is_array($profiles) ? array_map(static fn(object $profile): int => $profile instanceof Profile ? $profile->id() : -1, $profiles) : [];
        $this->case([2, 3] === $profileIds);
        $this->case(2 === $beforeInspection && $beforeInspection === $loadExecutor->statementCount);
        $this->expect(RuntimeException::class, fn() => $loadExecutor->load(
            $thirdSession,
            new AggregateLoadPlan('account', 'undeclared', Account::class, [], 1),
            $identity,
        ));
        $this->case(!(new ReflectionMethod($thirdRepository, 'loadWithProfiles'))->isStatic());

        $this->stage = 'cascade';
        $cascade = new PostgreSqlAggregateCascadeExecutor();
        $persist = new ReflectionMethod($thirdRepository, 'persist');
        $result = $persist->invoke($thirdRepository, $aggregate, $thirdSession, $cascade);
        $this->case($result instanceof FlushResult && FlushOutcome::Applied === $result->outcome());
        $this->case(['root', 'account_profiles', 'account_profiles'] === $cascade->order && 3 === $cascade->statements);
        $this->expect(InvalidAggregatePersistenceException::class, fn() => $persist->invoke(
            $thirdRepository,
            new LoadedAggregate($aggregate->root(), ['undeclared' => []]),
            $thirdSession,
            $cascade,
        ));
        $this->case(1 === $cascade->calls);

        $this->stage = 'optimistic_conflict';
        $this->proveOptimisticConflict($connector);
        $this->stage = 'terminal_failure';
        $this->proveTerminalFailure($thirdSession, $thirdContext);
        $this->stage = 'nondisclosure';
        $this->proveNondisclosure($connector);
        $this->stage = 'performance';
        $this->measure($thirdRoot, $thirdSession, $thirdRepository, $aggregate, $control);

        $this->stage = 'shutdown';
        $activeShutdown = null;
        try {
            $application->shutdown();
        } catch (Throwable $failure) {
            $activeShutdown = $failure;
        }
        $this->case($activeShutdown instanceof RetiredCompositionBoundaryException);
        $this->case(1 === $this->scalarInteger($thirdSession, 'SELECT 1::int4'));
        $thirdContext->retire();
        $third->close();
        $application->shutdown();
        $this->case($application->isRetired());
        $this->case($recording->allClosedExactlyOnce());
        $this->case(2 === $this->scalarInteger($control, 'SELECT count(*)::int8 FROM public.accounts'));
    }

    private function proveOptimisticConflict(PdoPgsqlConnector $connector): void
    {
        $first = $connector->openSession();
        $second = $connector->openSession();
        try {
            $firstVersion = $this->scalarInteger($first, 'SELECT version::int8 FROM public.accounts WHERE account_id = 1');
            $secondVersion = $this->scalarInteger($second, 'SELECT version::int8 FROM public.accounts WHERE account_id = 1');
            $this->case($firstVersion === $secondVersion);
            $identity = new EntityIdentity(Account::class, 'i:1');
            $firstChange = new PersistenceChange(new Account(1, 'Winner', $firstVersion + 1), ChangeKind::Update, $identity, ['name', 'version'], new ConcurrencyToken('integer', (string) $firstVersion));
            $secondChange = new PersistenceChange(new Account(1, 'Loser', $secondVersion + 1), ChangeKind::Update, $identity, ['name', 'version'], new ConcurrencyToken('integer', (string) $secondVersion));
            $firstTransaction = $first->begin(new TransactionOptions());
            $firstExecutor = new PostgreSqlChangeExecutor();
            $firstOutcome = $firstExecutor->execute($firstTransaction, $firstChange);
            $firstTransaction->commit();
            $secondTransaction = $second->begin(new TransactionOptions());
            $secondExecutor = new PostgreSqlChangeExecutor();
            $secondOutcome = $secondExecutor->execute($secondTransaction, $secondChange);
            $secondTransaction->rollback();
            $this->case(ChangeExecutionOutcome::Applied === $firstOutcome);
            $this->case(ChangeExecutionOutcome::OptimisticConflict === $secondOutcome);
            $this->case(1 === $firstExecutor->calls && 1 === $secondExecutor->calls);
            $this->case('Winner' === $this->scalarText($first, 'SELECT name FROM public.accounts WHERE account_id = 1'));
        } finally {
            $first->close();
            $second->close();
        }
    }

    private function proveTerminalFailure(DatabaseSession $database, OperationPersistenceContext $activePeer): void
    {
        $entity = new MutableProbe(7001, 'before', 1);
        $failed = OperationPersistenceContext::forManaged(new MutableProbeAdapter(), $entity);
        $entity->name = 'after';
        $this->expect(PersistenceUncertainStateException::class, fn() => $failed->flush($database, new FailingChangeExecutor()));
        $this->case(ContextState::Failed === $failed->state() && ContextState::Active === $activePeer->state());
        $this->expect(InvalidPersistenceStateException::class, fn() => $failed->changes());
        $this->case(1 === $this->scalarInteger($database, 'SELECT 1::int4'));
    }

    private function proveNondisclosure(PdoPgsqlConnector $connector): void
    {
        $sentinel = 'KONGONO_PHASE7_PRIVATE_' . strtoupper(bin2hex(random_bytes(8)));
        try {
            (new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', 1, 'postgres', 'nobody', $sentinel)))->openSession();
            throw new RuntimeException('expected_failure_missing');
        } catch (\Kongono\Database\Exception\ExceptionInterface $failure) {
            $this->case(!str_contains($this->exceptionSurface($failure), $sentinel));
        }
        $databaseSentinel = 'KONGONO_PHASE7_DATABASE_' . bin2hex(random_bytes(8));
        try {
            (new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', $this->connectorPort($connector), $databaseSentinel, 'kongono_phase7h_acceptance', '')))->openSession();
            throw new RuntimeException('expected_failure_missing');
        } catch (\Kongono\Database\Exception\ExceptionInterface $failure) {
            $this->case(!str_contains($this->exceptionSurface($failure), $databaseSentinel));
        }
    }

    private function measure(PersistenceOperations $root, DatabaseSession $session, object $aggregateRepository, LoadedAggregate $aggregate, DatabaseSession $control): void
    {
        $performance = new PersistencePerformance();
        $state = $this->adapter($this->property($root, 'state'));
        $identity = new EntityIdentity(Account::class, 'i:1');
        $context = OperationPersistenceContext::forManaged($state, $aggregate->root());
        $this->performance['identity_map_reuse'] = $performance->measure(
            static fn(): ?object => $context->entityForIdentity($identity),
            static fn(mixed $value): bool => $value === $aggregate->root(),
        );

        $loader = $this->property($root, 'loads');
        $dataRepository = $this->property($loader, 'accounts');
        $find = new ReflectionMethod($dataRepository, 'find');
        $this->performance['generated_repository_find'] = $performance->measure(
            static fn(): mixed => $find->invoke($dataRepository, 1),
            static fn(mixed $value): bool => is_object($value) && 1 === (new ReflectionMethod($value, 'id'))->invoke($value),
        );

        $aggregateLoad = new PostgreSqlAggregateLoadExecutor();
        $load = new ReflectionMethod($aggregateRepository, 'loadWithProfiles');
        $this->performance['aggregate_repository_load'] = $performance->measure(
            static fn(): mixed => $load->invoke($aggregateRepository, $identity, $session, $aggregateLoad),
            static fn(mixed $value): bool => $value instanceof LoadedAggregate && is_array($value->relationship('account_profiles')),
        );

        $insertExecutor = new PostgreSqlChangeExecutor();
        $this->performance['explicit_unit_of_work_flush'] = $performance->measure(
            function () use ($session, $state, $insertExecutor): FlushResult {
                $transaction = $session->begin(new TransactionOptions());
                $unit = OperationPersistenceContext::forNew($state, new Account(8001, 'Measured', 1));
                try {
                    return $unit->flush($transaction, $insertExecutor);
                } finally {
                    $unit->retire();
                    $transaction->rollback();
                }
            },
            static fn(mixed $value): bool => $value instanceof FlushResult && FlushOutcome::Applied === $value->outcome(),
        );
        $context->retire();
        $this->case(['identity_map_reuse', 'generated_repository_find', 'aggregate_repository_load', 'explicit_unit_of_work_flush'] === array_keys($this->performance));
        foreach ($this->performance as $summary) {
            $this->case(
                50 === $summary['samples']
                && 0 < $summary['min_ns']
                && $summary['min_ns'] <= $summary['p50_ns']
                && $summary['p50_ns'] <= $summary['p95_ns']
                && $summary['p95_ns'] <= $summary['max_ns'],
            );
        }
        $this->case(2 === $this->scalarInteger($control, 'SELECT count(*)::int8 FROM public.accounts'));
    }

    private function connectorPort(PdoPgsqlConnector $connector): int
    {
        $configuration = $this->property($connector, 'configuration');
        $port = (new ReflectionProperty($configuration, 'port'))->getValue($configuration);
        if (!is_int($port)) {
            throw new RuntimeException('connector_port_invalid');
        }

        return $port;
    }

    private function exceptionSurface(\Kongono\Database\Exception\ExceptionInterface $failure): string
    {
        $surface = $failure->getMessage() . (string) $failure . $failure->diagnosticId() . $failure->diagnosticTitle() . $failure->diagnosticRemediation();
        foreach ($failure->diagnosticContext()->fields() as $field) {
            $surface .= (string) $field->value();
        }

        return $surface;
    }

    private function relationExists(DatabaseSession $session, SqlQualifiedName $name): bool
    {
        $literal = implode('.', array_map(static fn(SqlIdentifier $part): string => $part->value(), $name->segments()));
        $stream = $this->rows($session, new DatabaseStatement(
            'SELECT to_regclass(:name) IS NOT NULL',
            [new DatabaseParameter('name', DatabaseValue::text($literal))],
            ResultExpectation::Rows,
        ));
        $value = $this->row($stream)->at(0)->value();
        $stream->close();

        return true === $value;
    }

    private function scalarInteger(DatabaseExecutor $executor, string $sql): int
    {
        $stream = $this->rows($executor, new DatabaseStatement($sql, [], ResultExpectation::Rows));
        $value = $this->row($stream)->at(0)->value();
        $stream->close();
        if (!is_int($value)) {
            throw new RuntimeException('integer_result_required');
        }

        return $value;
    }

    private function scalarText(DatabaseExecutor $executor, string $sql): string
    {
        $stream = $this->rows($executor, new DatabaseStatement($sql, [], ResultExpectation::Rows));
        $value = $this->row($stream)->at(0)->value();
        $stream->close();
        if (!is_string($value)) {
            throw new RuntimeException('text_result_required');
        }

        return $value;
    }

    private function completion(DatabaseExecutor $executor, string $sql): CommandResult
    {
        $result = $executor->execute(new DatabaseStatement($sql, [], ResultExpectation::Completion));
        if (!$result instanceof CommandResult) {
            throw new RuntimeException('completion_result_required');
        }

        return $result;
    }

    private function rows(DatabaseExecutor $executor, DatabaseStatement $statement): RowStream
    {
        $result = $executor->execute($statement);
        if (!$result instanceof RowStream) {
            throw new RuntimeException('row_stream_required');
        }

        return $result;
    }

    private function row(RowStream $stream): DatabaseRow
    {
        $row = $stream->fetch();
        if (!$row instanceof DatabaseRow) {
            throw new RuntimeException('row_required');
        }

        return $row;
    }

    private function root(object $root): PersistenceOperations
    {
        if (!$root instanceof PersistenceOperations) {
            throw new RuntimeException('operation_root_invalid');
        }

        return $root;
    }

    private function session(object $session): DatabaseSession
    {
        if (!$session instanceof DatabaseSession) {
            throw new RuntimeException('operation_session_invalid');
        }

        return $session;
    }

    private function adapter(object $adapter): PersistenceStateAdapter
    {
        if (!$adapter instanceof PersistenceStateAdapter) {
            throw new RuntimeException('state_adapter_invalid');
        }

        return $adapter;
    }

    private function property(object $object, string $name): object
    {
        $value = (new ReflectionProperty($object, $name))->getValue($object);
        if (!is_object($value)) {
            throw new RuntimeException('object_property_invalid');
        }

        return $value;
    }

    /** @param class-string<Throwable> $type */
    private function expect(string $type, callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            $this->case($failure instanceof $type);

            return;
        }
        throw new RuntimeException('expected_failure_missing');
    }

    private function case(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('acceptance_case_failed');
        }
        ++$this->cases;
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $bytes): void
    {
        if (strlen($bytes) !== fwrite($stream, $bytes)) {
            throw new RuntimeException('acceptance_output_failed');
        }
    }
}

/** Loads the one declared aggregate plan through exactly two ordered statements. */
final class PostgreSqlAggregateLoadExecutor implements AggregateLoadExecutor
{
    public int $statementCount = 0;

    public function load(DatabaseExecutor $database, AggregateLoadPlan $plan, EntityIdentity $identity): ?LoadedAggregate
    {
        if ('account' !== $plan->aggregateName() || 'loadWithProfiles' !== $plan->methodName() || Account::class !== $plan->rootType() || Account::class !== $identity->entityType() || 1 !== preg_match('/^i:([1-9][0-9]*)$/D', $identity->key(), $match)) {
            throw new RuntimeException('undeclared_load_plan');
        }
        $rootStream = $this->rows($database, new DatabaseStatement(
            'SELECT account_id::int8, name, version::int8 FROM public.accounts WHERE account_id = :identity',
            [new DatabaseParameter('identity', DatabaseValue::integer((int) $match[1]))],
            ResultExpectation::Rows,
        ));
        $rootRow = $rootStream->fetch();
        $rootStream->close();
        if (null === $rootRow) {
            return null;
        }
        $root = new Account($this->integer($rootRow, 0), $this->text($rootRow, 1), $this->integer($rootRow, 2));
        $profileStream = $this->rows($database, new DatabaseStatement(
            'SELECT profile_id::int8, account_id::int8, display_name FROM public.profiles WHERE account_id = :identity ORDER BY profile_id ASC LIMIT 33',
            [new DatabaseParameter('identity', DatabaseValue::integer($root->id()))],
            ResultExpectation::Rows,
        ));
        $profiles = [];
        while (null !== ($row = $profileStream->fetch())) {
            $name = $row->at(2)->value();
            if (!(null === $name || is_string($name))) {
                throw new RuntimeException('profile_name_invalid');
            }
            $profiles[] = Profile::restore($this->integer($row, 0), $this->integer($row, 1), $name);
        }
        $profileStream->close();
        if (count($profiles) > 32) {
            throw new RuntimeException('relationship_bound_exceeded');
        }

        return new LoadedAggregate($root, ['account_profiles' => $profiles]);
    }

    private function rows(DatabaseExecutor $database, DatabaseStatement $statement): RowStream
    {
        ++$this->statementCount;
        $result = $database->execute($statement);
        if (!$result instanceof RowStream) {
            throw new RuntimeException('row_stream_required');
        }

        return $result;
    }

    private function integer(DatabaseRow $row, int $ordinal): int
    {
        $value = $row->at($ordinal)->value();
        if (!is_int($value)) {
            throw new RuntimeException('integer_result_required');
        }

        return $value;
    }

    private function text(DatabaseRow $row, int $ordinal): string
    {
        $value = $row->at($ordinal)->value();
        if (!is_string($value)) {
            throw new RuntimeException('text_result_required');
        }

        return $value;
    }
}

/** Executes only the reviewed one-level cascade and records its exact order. */
final class PostgreSqlAggregateCascadeExecutor implements AggregateCascadeExecutor
{
    public int $calls = 0;
    public int $statements = 0;
    /** @var list<string> */
    public array $order = [];

    public function execute(DatabaseExecutor $database, AggregateCascadePlan $plan, CascadeAction $action, LoadedAggregate $aggregate): FlushResult
    {
        ++$this->calls;
        if ('account' !== $plan->aggregateName() || Account::class !== $plan->rootType() || 1 !== count($plan->policies()) || 'account_profiles' !== $plan->policies()[0]->relationshipName() || CascadeAction::Persist !== $action) {
            throw new RuntimeException('cascade_plan_invalid');
        }
        $account = $aggregate->root();
        $profiles = $aggregate->relationship('account_profiles');
        if (!$account instanceof Account || !is_array($profiles) || 1 + count($profiles) > $plan->maxChanges()) {
            throw new RuntimeException('cascade_bound_invalid');
        }
        $this->completion($database, new DatabaseStatement(
            'UPDATE public.accounts SET name = name WHERE account_id = :identity',
            [new DatabaseParameter('identity', DatabaseValue::integer($account->id()))],
            ResultExpectation::Completion,
        ), 'root');
        foreach ($profiles as $profile) {
            if (!$profile instanceof Profile) {
                throw new RuntimeException('cascade_target_invalid');
            }
            $this->completion($database, new DatabaseStatement(
                'UPDATE public.profiles SET display_name = display_name WHERE profile_id = :identity',
                [new DatabaseParameter('identity', DatabaseValue::integer($profile->id()))],
                ResultExpectation::Completion,
            ), 'account_profiles');
        }
        $count = 1 + count($profiles);

        return new FlushResult(FlushOutcome::Applied, $count, $count, 0, 0);
    }

    private function completion(DatabaseExecutor $database, DatabaseStatement $statement, string $stage): void
    {
        $result = $database->execute($statement);
        if (!$result instanceof CommandResult || 1 !== $result->affectedRows()) {
            throw new RuntimeException('cascade_statement_failed');
        }
        ++$this->statements;
        $this->order[] = $stage;
    }
}

/** Applies one parameter-bound insert or optimistic update without retry. */
final class PostgreSqlChangeExecutor implements PersistenceChangeExecutor
{
    public int $calls = 0;

    public function execute(DatabaseExecutor $database, PersistenceChange $change): ChangeExecutionOutcome
    {
        ++$this->calls;
        $account = $change->entity();
        if (!$account instanceof Account) {
            throw new RuntimeException('change_entity_invalid');
        }
        if (ChangeKind::Insert === $change->kind()) {
            $result = $database->execute(new DatabaseStatement(
                'INSERT INTO public.accounts (account_id, name, version) VALUES (:identity, :name, :version)',
                [
                    new DatabaseParameter('identity', DatabaseValue::integer($account->id())),
                    new DatabaseParameter('name', DatabaseValue::text($account->name())),
                    new DatabaseParameter('version', DatabaseValue::integer($account->version())),
                ],
                ResultExpectation::Completion,
            ));
        } elseif (ChangeKind::Update === $change->kind()) {
            $token = $change->expectedConcurrencyToken();
            if (null === $token || 'integer' !== $token->scheme() || 1 !== preg_match('/^(0|[1-9][0-9]*)$/D', $token->value())) {
                throw new RuntimeException('concurrency_token_invalid');
            }
            $result = $database->execute(new DatabaseStatement(
                'UPDATE public.accounts SET name = :name, version = :version WHERE account_id = :identity AND version = :expected',
                [
                    new DatabaseParameter('name', DatabaseValue::text($account->name())),
                    new DatabaseParameter('version', DatabaseValue::integer($account->version())),
                    new DatabaseParameter('identity', DatabaseValue::integer($account->id())),
                    new DatabaseParameter('expected', DatabaseValue::integer((int) $token->value())),
                ],
                ResultExpectation::Completion,
            ));
        } else {
            throw new RuntimeException('change_kind_invalid');
        }
        if (!$result instanceof CommandResult) {
            throw new RuntimeException('change_result_invalid');
        }

        return 0 === $result->affectedRows() ? ChangeExecutionOutcome::OptimisticConflict : ChangeExecutionOutcome::Applied;
    }
}

/** Delegates real sessions while proving operation cleanup is idempotent. */
final class RecordingConnector implements DatabaseConnector
{
    /** @var list<RecordingSession> */
    private array $sessions = [];

    public function __construct(private readonly DatabaseConnector $connector) {}

    public function openSession(): DatabaseSession
    {
        $session = new RecordingSession($this->connector->openSession());
        $this->sessions[] = $session;

        return $session;
    }

    public function allClosedExactlyOnce(): bool
    {
        return [] !== $this->sessions && array_all($this->sessions, static fn(RecordingSession $session): bool => 1 === $session->closeCalls && $session->isClosed());
    }
}

final class RecordingSession implements DatabaseSession
{
    public int $closeCalls = 0;

    public function __construct(private readonly DatabaseSession $session) {}

    public function capabilities(): DatabaseCapabilities
    {
        return $this->session->capabilities();
    }

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        return $this->session->execute($statement);
    }

    public function begin(TransactionOptions $options): DatabaseTransaction
    {
        return $this->session->begin($options);
    }

    public function close(): void
    {
        if ($this->session->isClosed()) {
            return;
        }
        ++$this->closeCalls;
        $this->session->close();
    }

    public function isClosed(): bool
    {
        return $this->session->isClosed();
    }
}

final class MutableProbe
{
    public function __construct(public int $id, public string $name, public int $version) {}
}

final class MutableProbeAdapter implements PersistenceStateAdapter
{
    public function entityType(): string
    {
        return MutableProbe::class;
    }

    public function identity(object $entity): EntityIdentity
    {
        return new EntityIdentity(MutableProbe::class, 'i:' . $this->entity($entity)->id);
    }

    public function snapshot(object $entity): array
    {
        $probe = $this->entity($entity);

        return ['id' => $probe->id, 'name' => $probe->name, 'version' => $probe->version];
    }

    public function changes(object $entity, array $snapshot): ChangeSet
    {
        $probe = $this->entity($entity);
        if (($snapshot['name'] ?? null) === $probe->name) {
            return new ChangeSet([]);
        }
        $version = $snapshot['version'] ?? null;
        if (!is_int($version)) {
            throw new RuntimeException('mutable_snapshot_invalid');
        }

        return new ChangeSet([new PersistenceChange(
            $probe,
            ChangeKind::Update,
            $this->identity($probe),
            ['name'],
            new ConcurrencyToken('integer', (string) $version),
        )]);
    }

    public function insertion(object $entity): ChangeSet
    {
        return new ChangeSet([new PersistenceChange($this->entity($entity), ChangeKind::Insert, $this->identity($entity), ['id', 'name', 'version'], null)]);
    }

    public function deletion(object $entity): ChangeSet
    {
        $probe = $this->entity($entity);

        return new ChangeSet([new PersistenceChange($probe, ChangeKind::Delete, $this->identity($probe), [], new ConcurrencyToken('integer', (string) $probe->version))]);
    }

    private function entity(object $entity): MutableProbe
    {
        if (!$entity instanceof MutableProbe) {
            throw new RuntimeException('mutable_probe_required');
        }

        return $entity;
    }
}

final class FailingChangeExecutor implements PersistenceChangeExecutor
{
    public function execute(DatabaseExecutor $database, PersistenceChange $change): ChangeExecutionOutcome
    {
        $database->execute(new DatabaseStatement('UPDATE public.phase7h_missing SET value = 1', [], ResultExpectation::Completion));

        throw new RuntimeException('database_failure_expected');
    }
}
