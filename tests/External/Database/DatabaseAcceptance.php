<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Database;

use FilesystemIterator;
use Kongono\Composition\Exception\ExceptionInterface as CompositionExceptionInterface;
use Kongono\Composition\Internal\PathGuard as CompositionPathGuard;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapability;
use Kongono\Database\DatabaseParameter;
use Kongono\Database\DatabaseRow;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\DatabaseValue;
use Kongono\Database\Exception\DatabaseExecutionException;
use Kongono\Database\Exception\DatabaseResultException;
use Kongono\Database\Exception\DatabaseTransactionCommitException;
use Kongono\Database\Exception\ExceptionInterface;
use Kongono\Database\Exception\InvalidDatabaseStateException;
use Kongono\Database\ResultExpectation;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionAccess;
use Kongono\Database\TransactionIsolation;
use Kongono\Database\TransactionOptions;
use Kongono\Database\TransactionState;
use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\DatabaseMapping\Build\MappingArtifactValidator;
use Kongono\DatabaseMapping\Exception\ExceptionInterface as MappingExceptionInterface;
use Kongono\DatabaseMapping\Exception\RowCardinalityException;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessGenerator;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessPublisher;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessValidator;
use Kongono\DatabaseMapping\Internal\PathGuard as MappingPathGuard;
use Kongono\DatabaseMigration\AddColumn;
use Kongono\DatabaseMigration\AppliedMigration;
use Kongono\DatabaseMigration\CreateIndex;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DestructiveMigrationPolicy;
use Kongono\DatabaseMigration\DropColumn;
use Kongono\DatabaseMigration\DropIndex;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\Exception\DestructiveMigrationReviewRequiredException;
use Kongono\DatabaseMigration\Exception\ExceptionInterface as MigrationExceptionInterface;
use Kongono\DatabaseMigration\Exception\MigrationExecutionException;
use Kongono\DatabaseMigration\Exception\MigrationHistoryMismatchException;
use Kongono\DatabaseMigration\Exception\MigrationLockUnavailableException;
use Kongono\DatabaseMigration\Exception\MigrationNotReversibleException;
use Kongono\DatabaseMigration\MigrationCatalog;
use Kongono\DatabaseMigration\MigrationDefinition;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationDirectionPlan;
use Kongono\DatabaseMigration\MigrationRisk;
use Kongono\DatabaseMigration\RenameColumn;
use Kongono\DatabaseMigration\RenameTable;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseMigration\SchemaColumnType;
use Kongono\DatabaseMigrationPgsql\PostgreSql17MigrationCompiler;
use Kongono\DatabaseMigrationPgsql\PostgreSql17MigrationExecutor;
use Kongono\DatabaseMigrationPgsql\PostgreSqlMigrationConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConnector;
use Kongono\DatabaseSql\DeletePlan;
use Kongono\DatabaseSql\Exception\ExceptionInterface as SqlExceptionInterface;
use Kongono\DatabaseSql\InsertPlan;
use Kongono\DatabaseSql\SelectPlan;
use Kongono\DatabaseSql\SqlAndPredicate;
use Kongono\DatabaseSql\SqlAssignment;
use Kongono\DatabaseSql\SqlColumn;
use Kongono\DatabaseSql\SqlComparisonOperator;
use Kongono\DatabaseSql\SqlComparisonPredicate;
use Kongono\DatabaseSql\SqlDialectCapabilities;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlInPredicate;
use Kongono\DatabaseSql\SqlMutationScope;
use Kongono\DatabaseSql\SqlNullOrdering;
use Kongono\DatabaseSql\SqlOrder;
use Kongono\DatabaseSql\SqlPlan;
use Kongono\DatabaseSql\SqlPlanKind;
use Kongono\DatabaseSql\SqlProjection;
use Kongono\DatabaseSql\SqlQualifiedName;
use Kongono\DatabaseSql\SqlSortDirection;
use Kongono\DatabaseSql\UpdatePlan;
use Kongono\DatabaseSqlPgsql\PostgreSql17Compiler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class DatabaseAcceptance
{
    private const PREFIX = 'kongono-phase6c-';
    private const ROLE = 'kongono_phase6c_acceptance';
    private const MAX_OUTPUT = 1_048_576;

    private int $cases = 0;
    private ?string $workspace = null;
    private ?string $data = null;
    private ?string $bin = null;
    private ?int $port = null;
    private bool $started = false;
    private string $stage = 'preflight';
    /** @var array<string, array{samples: int, min_ns: int, p50_ns: int, p95_ns: int, max_ns: int}> */
    private array $performance = [];

    /** @param list<mixed> $arguments @param resource $stdout @param resource $stderr */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        if (!is_resource($stdout) || !is_resource($stderr)) {
            return 2;
        }
        if (1 !== count($arguments) || !is_string($arguments[0])) {
            $this->write($stderr, "database_acceptance_failed\n");

            return 2;
        }
        $failure = null;
        try {
            $this->bin = $this->validateBinaries($arguments[0]);
            $this->case(true);
            $this->workspace = $this->createWorkspace();
            $this->data = $this->workspace . '/data';
            $this->stage = 'initialize';
            $this->initialize();
            $port = $this->availablePort();
            $this->port = $port;
            $this->case(!$this->listening($port));
            $this->stage = 'start';
            $this->start();
            $this->case($this->listening($port));
            $this->stage = 'role';
            $this->createRole();
            $this->stage = 'provider';
            $this->proveProvider();
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                $this->cleanup();
            } catch (Throwable $cleanup) {
                $failure ??= $cleanup;
            }
        }
        if (null !== $failure) {
            $token = $failure instanceof ExceptionInterface
                ? strtolower(str_replace('-', '_', $failure->diagnosticId()))
                : (1 === preg_match('/^[a-z_]+$/D', $failure->getMessage()) ? $this->stage . '_' . $failure->getMessage() : 'unexpected_failure');
            $this->write($stderr, 'database_acceptance_failed_' . $token . "\n");

            return 1;
        }
        $json = json_encode(
            [
                'status' => 'ok',
                'profile' => 'pdo_pgsql-postgresql-17',
                'server_major' => 17,
                'case_count' => $this->cases,
                'cleanup' => 'complete',
                'performance' => [
                    'schema' => 'kongono.phase6.performance-baseline',
                    'version' => 1,
                    'timer' => 'hrtime',
                    'unit' => 'nanoseconds',
                    'warmup_iterations' => PerformanceMeasurement::WARMUP_ITERATIONS,
                    'measured_samples' => PerformanceMeasurement::MEASURED_SAMPLES,
                    'percentile_method' => 'deterministic-nearest-rank',
                    'scenarios' => $this->performance,
                ],
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
        $this->write($stdout, $json . "\n");

        return 0;
    }

    private function proveProvider(): void
    {
        $connector = $this->connector();
        $session = $connector->openSession();
        try {
            $this->case(array_column($session->capabilities()->all(), 'name') === array_column(DatabaseCapability::cases(), 'name'));
            $this->stage = 'round_trips';
            $this->roundTrips($session);
            $this->stage = 'structured_plans';
            $this->structuredPlans($session);
            $this->stage = 'result_shape';
            $this->resultShapeAndRows($session);
            $this->stage = 'stream_lifecycle';
            $this->streamLifecycle($session);
            $this->stage = 'transactions';
            $this->transactions($session);
            $this->stage = 'migrations';
            $this->migrations($session, $connector);
            $this->stage = 'mapping';
            $this->mapping($session);
            $this->stage = 'data_application';
            try {
                $this->dataApplication($session);
            } catch (Throwable $failure) {
                if ($failure instanceof RuntimeException) {
                    throw $failure;
                }
                throw new RuntimeException('data_' . strtolower((new ReflectionClass($failure))->getShortName()), 0, $failure);
            }
            $this->stage = 'performance';
            $this->performance($session);
            $this->stage = 'disclosure';
            $this->unsupportedAndDisclosure($session);
        } finally {
            $session->close();
        }
        $this->stage = 'independent_sessions';
        $this->independentSessions($connector);
        $this->stage = 'transaction_visibility';
        $this->transactionVisibility($connector);
        $this->stage = 'terminal_transaction_failure';
        $this->terminalTransactionFailure($connector);
        $this->stage = 'connection_disclosure';
        $this->connectionDisclosure();
    }

    private function performance(DatabaseSession $session): void
    {
        $measurement = new PerformanceMeasurement();
        $compiler = new PostgreSql17Compiler();
        $select = static fn(): DatabaseStatement => $compiler->compile(new SelectPlan(
            new SqlQualifiedName([new SqlIdentifier('phase6f_account')]),
            [new SqlProjection(new SqlColumn(new SqlIdentifier('record_id')))],
        ));
        $this->performance['immutable_plan_compile'] = $measurement->measure(
            $select,
            static fn(mixed $result): bool => $result instanceof DatabaseStatement
                && ResultExpectation::Rows === $result->expectation(),
        )->toArray();

        $statement = new DatabaseStatement(
            'SELECT CAST(:value AS integer) AS value',
            [new DatabaseParameter('value', DatabaseValue::integer(1))],
            ResultExpectation::Rows,
        );
        $this->performance['prepared_select_round_trip'] = $measurement->measure(
            function () use ($session, $statement): int {
                $stream = $this->rows($session, $statement);
                try {
                    return (int) $this->row($stream)->at(0)->value();
                } finally {
                    $stream->close();
                }
            },
            static fn(mixed $result): bool => 1 === $result,
        )->toArray();

        $repository = $this->generatedInstance('KongonoPhase6FGenerated\\AccountRepository', [$session, $compiler]);
        $this->performance['generated_repository_find'] = $measurement->measure(
            fn(): mixed => $this->invokeGenerated($repository, 'find', [8]),
            fn(mixed $result): bool => is_object($result)
                && $this->isGeneratedType($result, 'KongonoPhase6FRecord\\Account')
                && 8 === $this->invokeGenerated($result, 'id'),
        )->toArray();

        $this->performance['transaction_begin_rollback'] = $measurement->measure(
            static function () use ($session): TransactionState {
                $transaction = $session->begin(new TransactionOptions());
                $transaction->rollback();

                return $transaction->state();
            },
            static fn(mixed $result): bool => TransactionState::RolledBack === $result,
        )->toArray();

        $this->case([
            'immutable_plan_compile',
            'prepared_select_round_trip',
            'generated_repository_find',
            'transaction_begin_rollback',
        ] === array_keys($this->performance));
        foreach ($this->performance as $summary) {
            $this->case(
                PerformanceMeasurement::MEASURED_SAMPLES === $summary['samples']
                && 0 < $summary['min_ns']
                && $summary['min_ns'] <= $summary['p50_ns']
                && $summary['p50_ns'] <= $summary['p95_ns']
                && $summary['p95_ns'] <= $summary['max_ns'],
            );
        }
    }

    private function mapping(DatabaseSession $session): void
    {
        if (null === $this->workspace) {
            throw new RuntimeException('mapping_workspace_unavailable');
        }
        $application = $this->workspace . '/mapping-application';
        $configuration = $application . '/config';
        $records = $application . '/records';
        $generated = $application . '/generated';
        foreach ([$application, $configuration, $records, $generated] as $directory) {
            if (!mkdir($directory, 0700) && !is_dir($directory)) {
                throw new RuntimeException('mapping_fixture_create_failed');
            }
        }
        $recordPath = $records . '/Records.php';
        $this->writeFixture($application . '/composer.json', "{}\n");
        $this->writeFixture($application . '/composer.lock', "{}\n");
        $this->writeFixture(
            $recordPath,
            <<<'PHP'
<?php

declare(strict_types=1);

namespace KongonoPhase6FRecord;

final readonly class Account
{
    public function __construct(
        private int $id,
        private bool $active,
        private float $score,
        private ?string $name,
        private string $payload,
    ) {}

    public function id(): int { return $this->id; }
    public function active(): bool { return $this->active; }
    public function score(): float { return $this->score; }
    public function name(): ?string { return $this->name; }
    public function payload(): string { return $this->payload; }
}

final readonly class TextRecord
{
    public function __construct(private string $key, private string $note) {}

    public function key(): string { return $this->key; }
    public function note(): string { return $this->note; }
}
PHP
        );
        require $recordPath;

        $source = [
            'schema' => 'kongono.data-mapping',
            'version' => 1,
            'generated_namespace' => 'KongonoPhase6FGenerated',
            'mappings' => [
                [
                    'record' => 'KongonoPhase6FRecord\\Account',
                    'mapper' => 'AccountMapper',
                    'repository' => 'AccountRepository',
                    'table' => ['phase6f_account'],
                    'identity' => 'id',
                    'fields' => [
                        ['field' => 'id', 'column' => 'record_id', 'kind' => 'integer', 'nullable' => false, 'constructor_parameter' => 'id', 'accessor' => 'id'],
                        ['field' => 'active', 'column' => 'active', 'kind' => 'boolean', 'nullable' => false, 'constructor_parameter' => 'active', 'accessor' => 'active'],
                        ['field' => 'score', 'column' => 'score', 'kind' => 'float', 'nullable' => false, 'constructor_parameter' => 'score', 'accessor' => 'score'],
                        ['field' => 'name', 'column' => 'display_name', 'kind' => 'text', 'nullable' => true, 'constructor_parameter' => 'name', 'accessor' => 'name'],
                        ['field' => 'payload', 'column' => 'payload', 'kind' => 'binary', 'nullable' => false, 'constructor_parameter' => 'payload', 'accessor' => 'payload'],
                    ],
                ],
                [
                    'record' => 'KongonoPhase6FRecord\\TextRecord',
                    'mapper' => 'TextRecordMapper',
                    'repository' => 'TextRecordRepository',
                    'table' => ['phase6f_text_record'],
                    'identity' => 'key',
                    'fields' => [
                        ['field' => 'key', 'column' => 'record_key', 'kind' => 'text', 'nullable' => false, 'constructor_parameter' => 'key', 'accessor' => 'key'],
                        ['field' => 'note', 'column' => 'note', 'kind' => 'text', 'nullable' => false, 'constructor_parameter' => 'note', 'accessor' => 'note'],
                    ],
                ],
            ],
        ];
        $sourceBytes = json_encode($source, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $sourcePath = $configuration . '/kongono.data-mapping.json';
        $this->writeFixture($sourcePath, $sourceBytes);
        $relativeSource = 'config/kongono.data-mapping.json';

        $artifact = (new MappingArtifactBuilder())->build($application, $relativeSource);
        $validatedArtifact = (new MappingArtifactValidator())->validateCurrent($application, $relativeSource, $artifact->canonicalBytes());
        $generatedArtifact = (new GeneratedDataAccessGenerator())->generate($application, $relativeSource, $validatedArtifact->canonicalBytes());
        $validatedGenerated = (new GeneratedDataAccessValidator())->validateCurrent($application, $relativeSource, $validatedArtifact->canonicalBytes(), $generatedArtifact->canonicalBytes());
        $this->case($validatedArtifact->artifactSha256() === $artifact->artifactSha256() && $validatedGenerated->generatedFileSha256() === $generatedArtifact->generatedFileSha256());

        $published = (new GeneratedDataAccessPublisher())->publish($validatedGenerated, $generated);
        require $published;
        $this->case(
            class_exists('KongonoPhase6FGenerated\\AccountMapper', false)
            && class_exists('KongonoPhase6FGenerated\\AccountRepository', false)
            && class_exists('KongonoPhase6FGenerated\\TextRecordMapper', false)
            && class_exists('KongonoPhase6FGenerated\\TextRecordRepository', false),
        );

        $this->completion($session, 'CREATE TEMP TABLE phase6f_account (record_id bigint NOT NULL, active boolean NOT NULL, score double precision NOT NULL, display_name text NULL, payload bytea NOT NULL)');
        $this->completion($session, 'CREATE TEMP TABLE phase6f_text_record (record_key text NOT NULL, note text NOT NULL)');
        $compiler = new PostgreSql17Compiler();
        $accountRepository = $this->generatedInstance('KongonoPhase6FGenerated\\AccountRepository', [$session, $compiler]);
        $textRepository = $this->generatedInstance('KongonoPhase6FGenerated\\TextRecordRepository', [$session, $compiler]);
        $binary = "\0\xFFphase6f";
        $executorUsable = [];

        $account = $this->generatedInstance('KongonoPhase6FRecord\\Account', [7, true, 1.25, 'alpha', $binary]);
        $insert = $this->invokeGenerated($accountRepository, 'insert', [$account]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case($insert instanceof CommandResult && 1 === $insert->affectedRows());

        $found = $this->invokeGenerated($accountRepository, 'find', [7]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case(is_object($found) && $this->isGeneratedType($found, 'KongonoPhase6FRecord\\Account') && [7, true, 1.25, 'alpha', $binary] === $this->recordValues($found, ['id', 'active', 'score', 'name', 'payload']));

        $nullable = $this->generatedInstance('KongonoPhase6FRecord\\Account', [8, false, 2.5, null, 'nullable']);
        $this->invokeGenerated($accountRepository, 'insert', [$nullable]);
        $roundTrip = $this->invokeGenerated($accountRepository, 'find', [8]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case(is_object($roundTrip) && $this->isGeneratedType($roundTrip, 'KongonoPhase6FRecord\\Account') && null === $this->invokeGenerated($roundTrip, 'name'));

        $updated = $this->generatedInstance('KongonoPhase6FRecord\\Account', [7, false, 9.5, 'updated', 'changed']);
        $update = $this->invokeGenerated($accountRepository, 'update', [$updated]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case($update instanceof CommandResult && 1 === $update->affectedRows());

        $postUpdate = $this->invokeGenerated($accountRepository, 'find', [7]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case(is_object($postUpdate) && $this->isGeneratedType($postUpdate, 'KongonoPhase6FRecord\\Account') && [7, false, 9.5, 'updated', 'changed'] === $this->recordValues($postUpdate, ['id', 'active', 'score', 'name', 'payload']));

        $delete = $this->invokeGenerated($accountRepository, 'delete', [7]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case($delete instanceof CommandResult && 1 === $delete->affectedRows());

        $missing = $this->invokeGenerated($accountRepository, 'find', [7]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case(null === $missing);

        $text = $this->generatedInstance('KongonoPhase6FRecord\\TextRecord', ['assigned-text', 'first']);
        $this->invokeGenerated($textRepository, 'insert', [$text]);
        $foundText = $this->invokeGenerated($textRepository, 'find', ['assigned-text']);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case(is_object($foundText) && $this->isGeneratedType($foundText, 'KongonoPhase6FRecord\\TextRecord') && ['assigned-text', 'first'] === $this->recordValues($foundText, ['key', 'note']));

        $privateSentinel = 'mapping_private_' . bin2hex(random_bytes(8));
        $duplicate = $this->generatedInstance('KongonoPhase6FRecord\\TextRecord', ['assigned-text', $privateSentinel]);
        $this->invokeGenerated($textRepository, 'insert', [$duplicate]);
        $firstFailure = null;
        try {
            $this->invokeGenerated($textRepository, 'find', ['assigned-text']);
        } catch (RowCardinalityException $exception) {
            $firstFailure = $exception;
        }
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case($firstFailure instanceof RowCardinalityException && !$this->discloses($firstFailure, $privateSentinel));

        $transaction = $session->begin(new TransactionOptions());
        $transactionRepository = $this->generatedInstance('KongonoPhase6FGenerated\\AccountRepository', [$transaction, $compiler]);
        $transactionRecord = $this->generatedInstance('KongonoPhase6FRecord\\Account', [99, true, 3.0, 'rollback', 'rollback']);
        $this->invokeGenerated($transactionRepository, 'insert', [$transactionRecord]);
        $transaction->rollback();
        $rolledBack = $this->invokeGenerated($accountRepository, 'find', [99]);
        $executorUsable[] = 1 === $this->scalarInteger($session, 'SELECT 1::int4');
        $this->case(null === $rolledBack && TransactionState::RolledBack === $transaction->state());

        $this->case(!in_array(false, $executorUsable, true));
        $this->case(1 === $this->scalarInteger($session, 'SELECT count(*)::int8 FROM phase6f_account') && 2 === $this->scalarInteger($session, 'SELECT count(*)::int8 FROM phase6f_text_record'));

        $secondFailure = null;
        try {
            $this->invokeGenerated($textRepository, 'find', ['assigned-text']);
        } catch (RowCardinalityException $exception) {
            $secondFailure = $exception;
        }
        $this->case(
            $secondFailure instanceof RowCardinalityException
            && $firstFailure instanceof RowCardinalityException
            && (string) $firstFailure === (string) $secondFailure
            && 1 === $this->scalarInteger($session, 'SELECT 1::int4'),
        );

        $repositoryRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__, 3)));
        $applicationRoot = str_replace('\\', '/', (string) realpath($application));
        $secondNondisclosing = $secondFailure instanceof RowCardinalityException && !$this->discloses($secondFailure, $privateSentinel);
        $this->case(
            $secondNondisclosing
            && !str_starts_with(strtolower($applicationRoot) . '/', strtolower($repositoryRoot) . '/')
            && str_starts_with(str_replace('\\', '/', $published), $applicationRoot . '/generated/'),
        );
    }

    private function dataApplication(DatabaseSession $session): void
    {
        if (null === $this->workspace || null === $this->port) {
            throw new RuntimeException('data_application_workspace_unavailable');
        }
        $application = $this->workspace . '/data-application';
        $packages = $this->workspace . '/packages';
        $this->stage = 'data_copy';
        $this->copyDataSkeleton($application);
        $this->mirrorDataPackages($application, $packages);

        $this->stage = 'data_install';
        $install = $this->process(
            [...$this->composerCommand(), 'install', '--no-interaction', '--no-ansi', '--no-plugins'],
            true,
            $application,
            ['COMPOSER_DISABLE_NETWORK' => '1'],
        );
        $this->dataCase(0 === $install['exit'] && is_file($application . '/vendor/autoload.php'), 'data_install');

        $this->stage = 'data_build';
        $firstBuild = $this->dataComposerScript($application, 'app:build', true);
        $firstIdentities = $this->dataBuildIdentities($firstBuild['stdout']);
        $beforeCheck = $this->directoryIdentity($application . '/var/kongono/build');
        $firstCheck = $this->dataComposerScript($application, 'app:check', true);
        $this->dataCase($firstIdentities === $this->dataBuildIdentities($firstCheck['stdout']), 'data_check_identity');
        $this->dataCase($beforeCheck === $this->directoryIdentity($application . '/var/kongono/build'), 'data_check_mutated');
        $repeatBuild = $this->dataComposerScript($application, 'app:build', true);
        $repeatCheck = $this->dataComposerScript($application, 'app:check', true);
        $this->dataCase(
            $firstBuild['stdout'] === $repeatBuild['stdout']
            && $firstCheck['stdout'] === $repeatCheck['stdout']
            && $firstIdentities === $this->dataBuildIdentities($repeatBuild['stdout'])
            && $firstIdentities === $this->dataBuildIdentities($repeatCheck['stdout']),
            'data_determinism',
        );

        $this->stage = 'data_stale';
        $sourcePath = $application . '/kongono.data-mapping.json';
        $sourceBytes = $this->readOrdinary($sourcePath);
        $sentinel = 'phase6g_private_' . bin2hex(random_bytes(8));
        $this->writeFixture($sourcePath, rtrim($sourceBytes, "\n") . ' ' . $sentinel . "\n");
        $stale = $this->dataComposerScript($application, 'app:check', false);
        $this->dataCase(0 !== $stale['exit'] && !$this->disclosesResult($stale, $sentinel), 'data_stale_refusal');
        $this->writeFixture($sourcePath, $sourceBytes);

        $this->stage = 'data_state_matrix';
        $buildDirectory = $application . '/var/kongono/build';
        $outputs = [
            'mapping' => $buildDirectory . '/kongono-data-mapping-v1-' . $firstIdentities['mapping'] . '.json',
            'data' => $buildDirectory . '/kongono-data-access-v1-' . $firstIdentities['data'] . '.php',
            'composition' => $buildDirectory . '/kongono-composition-v1-' . $firstIdentities['composition'] . '.json',
            'wiring' => $buildDirectory . '/kongono-wiring-v1-' . $firstIdentities['wiring'] . '.php',
        ];
        foreach ($outputs as $name => $path) {
            $renamed = $path . '.renamed';
            if (!rename($path, $renamed)) {
                throw new RuntimeException('data_rename_failed');
            }
            $missing = $this->dataComposerScript($application, 'app:check', false);
            $this->dataCase(0 !== $missing['exit'], 'data_missing_' . $name);
            if (!rename($renamed, $path)) {
                throw new RuntimeException('data_restore_failed');
            }

            $bytes = $this->readOrdinary($path);
            $this->writeFixture($path, $bytes . "\n");
            $tampered = $this->dataComposerScript($application, 'app:check', false);
            $this->dataCase(0 !== $tampered['exit'], 'data_tampered_' . $name);
            $this->writeFixture($path, $bytes);
        }
        foreach ([['mapping', 'composition'], ['data', 'wiring']] as [$left, $right]) {
            $leftBytes = $this->readOrdinary($outputs[$left]);
            $rightBytes = $this->readOrdinary($outputs[$right]);
            $this->writeFixture($outputs[$left], $rightBytes);
            $this->writeFixture($outputs[$right], $leftBytes);
            $crossed = $this->dataComposerScript($application, 'app:check', false);
            $this->dataCase(0 !== $crossed['exit'], 'data_crossed_' . $left);
            $this->writeFixture($outputs[$left], $leftBytes);
            $this->writeFixture($outputs[$right], $rightBytes);
        }
        $this->changedSnapshotRefusal($application, $sourcePath, $outputs);
        $this->dataCase(
            $firstIdentities === $this->dataBuildIdentities($this->dataComposerScript($application, 'app:check', true)['stdout']),
            'data_restore_current',
        );

        $this->stage = 'data_table';
        $this->completion($session, 'DROP TABLE IF EXISTS public.accounts');
        $this->completion($session, 'CREATE TABLE public.accounts (account_id bigint PRIMARY KEY, display_name text NOT NULL)');
        try {
            $this->stage = 'data_host';
            $hostPath = $application . '/phase6g-host.php';
            $this->writeFixture($hostPath, $this->dataHostBytes());
            $host = $this->process([PHP_BINARY, $hostPath, (string) $this->port], true, $application);
            $observed = json_decode($host['stdout'], true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($observed) || array_keys($observed) !== [
                'startup', 'insert', 'find', 'sequential', 'overlap', 'lifetime_identity', 'peer_visibility', 'isolated_close',
                'retired_session', 'nondisclosure', 'active_shutdown_refusal', 'peer_survives', 'delete', 'shutdown',
            ]) {
                throw new RuntimeException('data_application_host_shape_invalid');
            }
            foreach ($observed as $name => $value) {
                if (!is_string($name)) {
                    throw new RuntimeException('data_application_host_shape_invalid');
                }
                $this->dataCase(true === $value, 'data_host_' . $name);
            }
            $this->dataCase(
                1 === $this->scalarInteger(
                    $session,
                    "SELECT count(*)::int8 FROM pg_stat_activity WHERE usename = '" . self::ROLE . "'",
                ),
                'data_session_cleanup',
            );
        } finally {
            $this->completion($session, 'DROP TABLE IF EXISTS public.accounts');
        }
    }

    /** @param array{mapping: string, data: string, composition: string, wiring: string} $outputs */
    private function changedSnapshotRefusal(string $application, string $mappingSource, array $outputs): void
    {
        $mappingRoot = MappingPathGuard::resolveApplicationRoot($application);
        $mappingSourceSnapshot = MappingPathGuard::readSource($mappingRoot, 'kongono.data-mapping.json');
        $mappingSourceBytes = $this->readOrdinary($mappingSource);
        $this->writeFixture($mappingSource, $mappingSourceBytes . "\n");
        $this->expect(MappingExceptionInterface::class, static fn() => MappingPathGuard::reverify($mappingSourceSnapshot));
        $this->writeFixture($mappingSource, $mappingSourceBytes);

        foreach (['mapping', 'data'] as $name) {
            $snapshot = MappingPathGuard::readGeneratedFile($mappingRoot, $outputs[$name]);
            $bytes = $this->readOrdinary($outputs[$name]);
            $this->writeFixture($outputs[$name], $bytes . "\n");
            $this->expect(MappingExceptionInterface::class, static fn() => MappingPathGuard::reverify($snapshot));
            $this->writeFixture($outputs[$name], $bytes);
        }

        $compositionRoot = CompositionPathGuard::resolveApplicationRoot($application);
        $compositionSource = $application . '/kongono.composition.json';
        $compositionSourceSnapshot = CompositionPathGuard::readSource($compositionRoot);
        $compositionSourceBytes = $this->readOrdinary($compositionSource);
        $this->writeFixture($compositionSource, $compositionSourceBytes . "\n");
        $this->expect(CompositionExceptionInterface::class, static fn() => CompositionPathGuard::reverify($compositionSourceSnapshot));
        $this->writeFixture($compositionSource, $compositionSourceBytes);

        foreach (['composition', 'wiring'] as $name) {
            $snapshot = CompositionPathGuard::readGeneratedFile($compositionRoot, $outputs[$name]);
            $bytes = $this->readOrdinary($outputs[$name]);
            $this->writeFixture($outputs[$name], $bytes . "\n");
            $this->expect(CompositionExceptionInterface::class, static fn() => CompositionPathGuard::reverify($snapshot));
            $this->writeFixture($outputs[$name], $bytes);
        }
    }

    private function copyDataSkeleton(string $application): void
    {
        $skeleton = str_replace('\\', '/', dirname(__DIR__, 3)) . '/skeletons/data-minimal';
        $manifest = json_decode($this->readOrdinary($skeleton . '/kongono.skeleton.json'), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            throw new RuntimeException('data_skeleton_manifest_invalid');
        }
        $paths = $manifest['template_paths'] ?? null;
        if (!is_array($paths) || [] === $paths) {
            throw new RuntimeException('data_skeleton_manifest_invalid');
        }
        foreach ($paths as $relative) {
            if (!is_string($relative)) {
                throw new RuntimeException('data_skeleton_manifest_invalid');
            }
            $this->writeFixture($application . '/' . $relative, $this->readOrdinary($skeleton . '/' . $relative));
        }
        $composerPath = $application . '/composer.json';
        $composer = str_replace('"url": "../../packages/*"', '"url": "../packages/*"', $this->readOrdinary($composerPath), $replacements);
        if (1 !== $replacements) {
            throw new RuntimeException('data_skeleton_repository_invalid');
        }
        $this->writeFixture($composerPath, $composer);

        $lockPath = $application . '/composer.lock';
        $lock = json_decode($this->readOrdinary($lockPath), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($lock) || !is_array($lock['packages'] ?? null)) {
            throw new RuntimeException('data_skeleton_lock_invalid');
        }
        foreach ($lock['packages'] as $index => $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null) || !str_starts_with($package['name'], 'kongono/')) {
                throw new RuntimeException('data_skeleton_lock_invalid');
            }
            if (!is_array($package['dist'] ?? null)) {
                throw new RuntimeException('data_skeleton_lock_invalid');
            }
            $package['dist']['url'] = '../packages/' . substr($package['name'], strlen('kongono/'));
            $lock['packages'][$index] = $package;
        }
        $this->writeFixture($lockPath, json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 64) . "\n");
    }

    private function mirrorDataPackages(string $application, string $packages): void
    {
        $lock = json_decode($this->readOrdinary($application . '/composer.lock'), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($lock)) {
            throw new RuntimeException('data_package_mirror_invalid');
        }
        $locked = $lock['packages'] ?? null;
        if (!is_array($locked) || 9 !== count($locked)) {
            throw new RuntimeException('data_package_mirror_invalid');
        }
        $repositoryPackages = str_replace('\\', '/', dirname(__DIR__, 3)) . '/packages';
        foreach ($locked as $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null) || !str_starts_with($package['name'], 'kongono/')) {
                throw new RuntimeException('data_package_mirror_invalid');
            }
            $name = substr($package['name'], strlen('kongono/'));
            $this->copyTree($repositoryPackages . '/' . $name, $packages . '/' . $name);
        }
    }

    /** @return array{mapping: string, data: string, composition: string, wiring: string} */
    private function dataBuildIdentities(string $stdout): array
    {
        $patterns = [
            'mapping' => '/mapping_artifact_sha256=([a-f0-9]{64})/',
            'data' => '/generated_data_access_sha256=([a-f0-9]{64})/',
            'composition' => '/composition_artifact_sha256=([a-f0-9]{64})/',
            'wiring' => '/generated_wiring_sha256=([a-f0-9]{64})/',
        ];
        $identities = [];
        foreach ($patterns as $name => $pattern) {
            if (1 !== preg_match($pattern, $stdout, $match)) {
                throw new RuntimeException('data_build_identity_invalid');
            }
            $identities[$name] = $match[1];
        }

        return $identities;
    }

    /** @return array{exit: int, stdout: string, stderr: string} */
    private function dataComposerScript(string $application, string $script, bool $requireSuccess): array
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
        if (is_string($configured) && '' !== $configured) {
            $resolved = realpath($configured);
            if (false !== $resolved && is_file($resolved) && !is_link($configured)) {
                return [PHP_BINARY, str_replace('\\', '/', $resolved)];
            }
        }

        throw new RuntimeException('composer_binary_unavailable');
    }

    /** @param array{exit: int, stdout: string, stderr: string} $result */
    private function disclosesResult(array $result, string $sentinel): bool
    {
        return str_contains($result['stdout'], $sentinel) || str_contains($result['stderr'], $sentinel);
    }

    private function dataHostBytes(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Kongono\Composition\Exception\RetiredCompositionBoundaryException;
use Kongono\DatabaseApplication\Exception\DatabaseSessionUnavailableException;
use Kongono\DatabasePdoPgsql\PdoPgsqlConfiguration;
use Kongono\DatabasePdoPgsql\PdoPgsqlConnector;
use KongonoDataMinimal\Account;
use KongonoDataMinimal\AccountOperations;
use KongonoDataMinimal\Runtime;

require __DIR__ . '/vendor/autoload.php';

$port = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);
if (!is_int($port)) {
    throw new RuntimeException('host_port_invalid');
}
$application = Runtime::start(
    str_replace('\\', '/', __DIR__),
    new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', $port, 'postgres', 'kongono_phase6c_acceptance', '')),
);
$result = ['startup' => true];
$property = static function (object $object, string $name): object {
    $value = (new ReflectionProperty($object, $name))->getValue($object);
    if (!is_object($value)) {
        throw new RuntimeException('host_identity_invalid');
    }

    return $value;
};
$first = $application->beginOperation();
$firstRoot = $first->root();
$result['insert'] = $firstRoot instanceof AccountOperations && 1 === $firstRoot->insert(new Account(6101, 'first'))->affectedRows();
$result['find'] = $firstRoot instanceof AccountOperations && 'first' === $firstRoot->find(6101)?->name();
$first->close();
$second = $application->beginOperation();
$secondRoot = $second->root();
$result['sequential'] = $secondRoot instanceof AccountOperations && $secondRoot !== $firstRoot;
$third = $application->beginOperation();
$thirdRoot = $third->root();
$result['overlap'] = $thirdRoot instanceof AccountOperations && $thirdRoot !== $secondRoot;
$firstRepository = $firstRoot instanceof AccountOperations ? $property($firstRoot, 'repository') : null;
$secondRepository = $secondRoot instanceof AccountOperations ? $property($secondRoot, 'repository') : null;
$thirdRepository = $thirdRoot instanceof AccountOperations ? $property($thirdRoot, 'repository') : null;
$firstExecutor = is_object($firstRepository) ? $property($firstRepository, 'executor') : null;
$secondExecutor = is_object($secondRepository) ? $property($secondRepository, 'executor') : null;
$thirdExecutor = is_object($thirdRepository) ? $property($thirdRepository, 'executor') : null;
$firstCompiler = is_object($firstRepository) ? $property($firstRepository, 'compiler') : null;
$secondCompiler = is_object($secondRepository) ? $property($secondRepository, 'compiler') : null;
$thirdCompiler = is_object($thirdRepository) ? $property($thirdRepository, 'compiler') : null;
$result['lifetime_identity'] = $firstRoot !== $secondRoot
    && $secondRoot !== $thirdRoot
    && $firstRepository !== $secondRepository
    && $secondRepository !== $thirdRepository
    && $firstExecutor !== $secondExecutor
    && $secondExecutor !== $thirdExecutor
    && $firstCompiler === $secondCompiler
    && $secondCompiler === $thirdCompiler;
$result['peer_visibility'] = $secondRoot instanceof AccountOperations
    && $thirdRoot instanceof AccountOperations
    && 1 === $secondRoot->update(new Account(6101, 'second'))->affectedRows()
    && 'second' === $thirdRoot->find(6101)?->name();
$second->close();
$result['isolated_close'] = $thirdRoot instanceof AccountOperations && 'second' === $thirdRoot->find(6101)?->name();
$retired = null;
try {
    if ($secondRoot instanceof AccountOperations) {
        $secondRoot->find(6101);
    }
} catch (Throwable $failure) {
    $retired = $failure;
}
$result['retired_session'] = $retired instanceof DatabaseSessionUnavailableException;
$result['nondisclosure'] = $retired instanceof DatabaseSessionUnavailableException
    && !str_contains((string) $retired, 'kongono_phase6c_acceptance')
    && !str_contains((string) $retired, str_replace('\\', '/', __DIR__));
$activeRefusal = null;
try {
    $application->shutdown();
} catch (Throwable $failure) {
    $activeRefusal = $failure;
}
$result['active_shutdown_refusal'] = $activeRefusal instanceof RetiredCompositionBoundaryException;
$result['peer_survives'] = $thirdRoot instanceof AccountOperations && 'second' === $thirdRoot->find(6101)?->name();
$result['delete'] = $thirdRoot instanceof AccountOperations && 1 === $thirdRoot->delete(6101)->affectedRows();
$third->close();
$application->shutdown();
$result['shutdown'] = $application->isRetired();

fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
PHP;
    }

    private function dataCase(bool $condition, string $failure): void
    {
        if (!$condition) {
            throw new RuntimeException($failure);
        }
        $this->case(true);
    }

    private function roundTrips(DatabaseSession $session): void
    {
        $binary = "\0\xFFphase6c";
        $sentinel = "named_'_value";
        $stream = $this->rows($session, new DatabaseStatement(
            'SELECT CAST(:b AS boolean) AS b, CAST(:i AS bigint) AS i, CAST(:f AS double precision) AS f, CAST(:t AS text) AS t, CAST(:x AS bytea) AS x, CAST(:n AS text) AS n',
            [
                new DatabaseParameter('b', DatabaseValue::boolean(true)),
                new DatabaseParameter('i', DatabaseValue::integer(PHP_INT_MAX)),
                new DatabaseParameter('f', DatabaseValue::float(1.25)),
                new DatabaseParameter('t', DatabaseValue::text($sentinel)),
                new DatabaseParameter('x', DatabaseValue::binary($binary)),
                new DatabaseParameter('n', DatabaseValue::null()),
            ],
            ResultExpectation::Rows,
        ));
        $row = $this->row($stream);
        $this->case([true, PHP_INT_MAX, 1.25, $sentinel, $binary, null] === array_map(static fn($value) => $value->value(), $row->values()));
        $this->case(null === $stream->fetch() && $stream->isClosed());
    }

    private function resultShapeAndRows(DatabaseSession $session): void
    {
        $completion = $session->execute(new DatabaseStatement('CREATE TEMP TABLE phase6c_shape (id integer)', [], ResultExpectation::Completion));
        $this->case($completion instanceof CommandResult && 0 === $completion->affectedRows());
        $this->expect(DatabaseResultException::class, fn() => $session->execute(new DatabaseStatement('SELECT 1', [], ResultExpectation::Completion)));
        $this->expect(DatabaseResultException::class, fn() => $session->execute(new DatabaseStatement('INSERT INTO phase6c_shape VALUES (1)', [], ResultExpectation::Rows)));

        $stream = $this->rows($session, new DatabaseStatement('SELECT 1::int4 AS "dup", 2::int4 AS "Dup", 3::int4 AS "dup"', [], ResultExpectation::Rows));
        $row = $this->row($stream);
        $this->case(['dup', 'Dup', 'dup'] === array_map(static fn($column) => $column->label(), $row->columns()));
        $this->case(2 === $row->named('Dup')->value() && 3 === $row->at(2)->value());
        $this->expect(DatabaseResultException::class, fn() => $row->named('dup'));
        $stream->close();
    }

    private function structuredPlans(DatabaseSession $session): void
    {
        $compiler = new PostgreSql17Compiler();
        $target = $this->sqlName('phase6d_plan');
        $id = $this->sqlIdentifier('id');
        $label = $this->sqlIdentifier('label');
        $rank = $this->sqlIdentifier('rank');
        $quoted = $this->sqlIdentifier('odd"name');
        $this->completion($session, 'CREATE TABLE phase6d_plan (id integer PRIMARY KEY, label text NOT NULL, rank integer NULL, "odd""name" text NOT NULL)');

        $valueSentinel = 'KONGONO_PLAN_VALUE_' . bin2hex(random_bytes(8));
        $insertReturning = $compiler->compile(new InsertPlan(
            $target,
            [
                new SqlAssignment($id, DatabaseValue::integer(1)),
                new SqlAssignment($label, DatabaseValue::text('alpha')),
                new SqlAssignment($rank, DatabaseValue::null()),
                new SqlAssignment($quoted, DatabaseValue::text($valueSentinel)),
            ],
            [new SqlProjection(new SqlColumn($id))],
        ));
        $stream = $this->rows($session, $insertReturning);
        $this->case(1 === $this->row($stream)->at(0)->value());
        $stream->close();

        $insert = $compiler->compile(new InsertPlan($target, [
            new SqlAssignment($id, DatabaseValue::integer(2)),
            new SqlAssignment($label, DatabaseValue::text('beta')),
            new SqlAssignment($rank, DatabaseValue::integer(2)),
            new SqlAssignment($quoted, DatabaseValue::text('second')),
        ]));
        $this->case($this->completionStatement($session, $insert)->affectedRows() === 1);

        $predicate = new SqlAndPredicate([
            new SqlInPredicate(new SqlColumn($id, $this->sqlIdentifier('p')), [DatabaseValue::integer(1), DatabaseValue::integer(2)]),
            new SqlComparisonPredicate(new SqlColumn($label, $this->sqlIdentifier('p')), SqlComparisonOperator::Like, DatabaseValue::text('%a%')),
        ]);
        $select = $compiler->compile(new SelectPlan(
            $target,
            [new SqlProjection(new SqlColumn($id, $this->sqlIdentifier('p'))), new SqlProjection(new SqlColumn($quoted, $this->sqlIdentifier('p')))],
            $this->sqlIdentifier('p'),
            $predicate,
            [new SqlOrder(new SqlColumn($rank, $this->sqlIdentifier('p')), SqlSortDirection::Ascending, SqlNullOrdering::Last)],
            1,
            0,
        ));
        $stream = $this->rows($session, $select);
        $row = $this->row($stream);
        $this->case(2 === $row->at(0)->value() && 'second' === $row->at(1)->value());
        $this->case(null === $stream->fetch());

        $update = $compiler->compile(new UpdatePlan(
            $target,
            [new SqlAssignment($label, DatabaseValue::text('changed'))],
            SqlMutationScope::forPredicate(new SqlComparisonPredicate(new SqlColumn($id), SqlComparisonOperator::Equal, DatabaseValue::integer(1))),
            [new SqlProjection(new SqlColumn($quoted))],
        ));
        $stream = $this->rows($session, $update);
        $this->case($valueSentinel === $this->row($stream)->at(0)->value());
        $stream->close();

        $transaction = $session->begin(new TransactionOptions());
        $transactionInsert = $compiler->compile(new InsertPlan($target, [
            new SqlAssignment($id, DatabaseValue::integer(3)),
            new SqlAssignment($label, DatabaseValue::text('transaction')),
            new SqlAssignment($rank, DatabaseValue::integer(3)),
            new SqlAssignment($quoted, DatabaseValue::text('third')),
        ]));
        $this->case($this->completionStatement($transaction, $transactionInsert)->affectedRows() === 1);
        $transaction->commit();

        $delete = $compiler->compile(new DeletePlan(
            $target,
            SqlMutationScope::forPredicate(new SqlComparisonPredicate(new SqlColumn($id), SqlComparisonOperator::Equal, DatabaseValue::integer(3))),
            [new SqlProjection(new SqlColumn($id))],
        ));
        $stream = $this->rows($session, $delete);
        $this->case(3 === $this->row($stream)->at(0)->value());
        $stream->close();

        $allRowsTarget = $this->sqlName('phase6d_all_rows');
        $this->completion($session, 'CREATE TABLE phase6d_all_rows (id integer)');
        foreach ([1, 2] as $value) {
            $this->completionStatement($session, $compiler->compile(new InsertPlan($allRowsTarget, [new SqlAssignment($id, DatabaseValue::integer($value))])));
        }
        $allRows = $compiler->compile(new DeletePlan($allRowsTarget, SqlMutationScope::allRows()));
        $this->case(2 === $this->completionStatement($session, $allRows)->affectedRows());

        $this->case('kgn_p00001' === $compiler->compile(new InsertPlan($allRowsTarget, [new SqlAssignment($id, DatabaseValue::integer(4))]))->parameters()[0]->name());
        $this->case('kgn_p00001' === $compiler->compile(new InsertPlan($allRowsTarget, [new SqlAssignment($id, DatabaseValue::integer(5))]))->parameters()[0]->name());
        $this->case(2 === $this->scalarInteger($session, 'SELECT count(*)::int8 AS count FROM phase6d_plan'));

        $identifierSentinel = 'KONGONO_PLAN_IDENTIFIER_' . bin2hex(random_bytes(8));
        try {
            new SqlIdentifier($identifierSentinel . "\0");
            throw new RuntimeException('expected_failure_missing');
        } catch (SqlExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $identifierSentinel));
        }

        $overlong = str_repeat('K', 64);
        try {
            $compiler->compile(new SelectPlan($target, [new SqlProjection(new SqlColumn(new SqlIdentifier($overlong)))]));
            throw new RuntimeException('expected_failure_missing');
        } catch (SqlExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $overlong));
        }

        $foreignAccesses = 0;
        $foreign = new class ($foreignAccesses) implements SqlPlan {
            public function __construct(private int &$accesses) {}

            public function kind(): SqlPlanKind
            {
                ++$this->accesses;

                return SqlPlanKind::Select;
            }

            public function requiredCapabilities(): SqlDialectCapabilities
            {
                ++$this->accesses;

                return new SqlDialectCapabilities([]);
            }
        };
        $this->expect(SqlExceptionInterface::class, static fn() => $compiler->compile($foreign));
        $this->case(0 === $foreignAccesses);

        $duplicate = new SqlAssignment($id, DatabaseValue::integer(1));
        $this->expect(SqlExceptionInterface::class, static fn() => new InsertPlan($target, [$duplicate, $duplicate]));

        $raw = $this->rows($session, new DatabaseStatement('SELECT :value::text AS value', [new DatabaseParameter('value', DatabaseValue::text($valueSentinel))], ResultExpectation::Rows));
        $this->case($valueSentinel === $this->row($raw)->at(0)->value());
        $raw->close();
    }

    private function streamLifecycle(DatabaseSession $session): void
    {
        $stream = $this->rows($session, new DatabaseStatement('SELECT generate_series(1, 3)::int4 AS item', [], ResultExpectation::Rows));
        $first = $this->row($stream);
        $second = $this->row($stream);
        $this->case(1 === $first->at(0)->value() && 2 === $second->at(0)->value());
        $this->expect(InvalidDatabaseStateException::class, fn() => $session->execute(new DatabaseStatement('SELECT 1', [], ResultExpectation::Rows)));
        $this->expect(InvalidDatabaseStateException::class, fn() => $session->begin(new TransactionOptions()));
        $this->case(3 === $this->row($stream)->at(0)->value() && null === $stream->fetch() && $stream->isClosed());
        $stream = $this->rows($session, new DatabaseStatement('SELECT 4::int4 AS item', [], ResultExpectation::Rows));
        $stream->close();
        $this->case($stream->isClosed());
        $this->expect(InvalidDatabaseStateException::class, fn() => $stream->fetch());
    }

    private function transactions(DatabaseSession $session): void
    {
        $this->completion($session, 'CREATE TABLE phase6c_tx (id integer)');
        $transaction = $session->begin(new TransactionOptions());
        $this->stage = 'transactions_direct_session';
        $this->expect(InvalidDatabaseStateException::class, fn() => $session->execute(new DatabaseStatement('SELECT 1', [], ResultExpectation::Rows)));
        $this->stage = 'transactions_nested_begin';
        $this->expect(InvalidDatabaseStateException::class, fn() => $session->begin(new TransactionOptions()));
        $this->stage = 'transactions_commit';
        $this->completion($transaction, 'INSERT INTO phase6c_tx VALUES (1)');
        $transaction->commit();
        $this->case(TransactionState::Committed === $transaction->state() && $transaction->isClosed());
        $this->case(1 === $this->scalarInteger($session, 'SELECT count(*)::int8 AS count FROM phase6c_tx'));

        $transaction = $session->begin(new TransactionOptions());
        $this->completion($transaction, 'INSERT INTO phase6c_tx VALUES (2)');
        $transaction->rollback();
        $this->case(TransactionState::RolledBack === $transaction->state() && 1 === $this->scalarInteger($session, 'SELECT count(*)::int8 AS count FROM phase6c_tx'));

        foreach (TransactionIsolation::cases() as $isolation) {
            foreach (TransactionAccess::cases() as $access) {
                $this->stage = 'transactions_options';
                $transaction = $session->begin(new TransactionOptions($isolation, $access));
                $stream = $this->rows($transaction, new DatabaseStatement('SELECT 1::int4 AS value', [], ResultExpectation::Rows));
                $this->case(1 === $this->row($stream)->at(0)->value());
                $stream->close();
                if (TransactionAccess::ReadOnly === $access) {
                    $this->stage = 'transactions_read_only';
                    $this->expect(DatabaseExecutionException::class, fn() => $this->completion($transaction, 'INSERT INTO phase6c_tx VALUES (3)'));
                }
                $transaction->close();
                $this->case(TransactionState::RolledBack === $transaction->state());
            }
        }

        $transaction = $session->begin(new TransactionOptions());
        $this->completion($transaction, 'INSERT INTO phase6c_tx VALUES (4)');
        $transaction->close();
        $this->case(1 === $this->scalarInteger($session, 'SELECT count(*)::int8 AS count FROM phase6c_tx'));
    }

    private function migrations(DatabaseSession $session, PdoPgsqlConnector $connector): void
    {
        $suffix = bin2hex(random_bytes(6));
        $table = $this->sqlName('p6e_table_' . $suffix);
        $renamedTable = $this->sqlName('p6e_renamed_' . $suffix);
        $index = $this->sqlName('p6e_index_' . $suffix);
        $history = $this->sqlName('p6e_history_' . $suffix);
        $id = $this->sqlIdentifier('record_id');
        $payload = $this->sqlIdentifier('payload');
        $extra = $this->sqlIdentifier('extra');
        $renamed = $this->sqlIdentifier('renamed_extra');
        $columns = [
            new SchemaColumn($id, SchemaColumnType::Int64, false),
            new SchemaColumn($this->sqlIdentifier('flag'), SchemaColumnType::Boolean, true),
            new SchemaColumn($this->sqlIdentifier('ratio'), SchemaColumnType::Float64, true),
            new SchemaColumn($payload, SchemaColumnType::Text, true),
            new SchemaColumn($this->sqlIdentifier('bytes'), SchemaColumnType::Binary, true),
            new SchemaColumn($this->sqlIdentifier('identity'), SchemaColumnType::Uuid, true),
            new SchemaColumn($this->sqlIdentifier('document'), SchemaColumnType::Json, true),
            new SchemaColumn($this->sqlIdentifier('observed_at'), SchemaColumnType::TimestampTz, true),
        ];
        $first = new MigrationDefinition(
            1,
            'create_every_type',
            new MigrationDirectionPlan([
                new CreateTable($table, $columns, [$id]),
                new CreateIndex($index, $table, [$payload], true),
            ], MigrationRisk::Safe),
            new MigrationDirectionPlan([new DropTable($table)], MigrationRisk::Destructive),
        );
        $second = new MigrationDefinition(
            2,
            'exercise_all_operations',
            new MigrationDirectionPlan([
                new AddColumn($table, new SchemaColumn($extra, SchemaColumnType::Text, true)),
                new RenameColumn($table, $extra, $renamed),
                new DropColumn($table, $renamed),
                new DropIndex($index),
                new RenameTable($table, $renamedTable->segments()[0]),
                new DropTable($renamedTable),
            ], MigrationRisk::Destructive),
            new MigrationDirectionPlan([
                new CreateTable($table, [new SchemaColumn($id, SchemaColumnType::Int64, false)], [$id]),
            ], MigrationRisk::Safe),
        );
        $catalog = new MigrationCatalog([$first, $second]);
        $lockKey = random_int(-2_000_000_000, 2_000_000_000);
        $configuration = new PostgreSqlMigrationConfiguration($history, $lockKey);
        $executor = new PostgreSql17MigrationExecutor($configuration);

        $this->expect(DestructiveMigrationReviewRequiredException::class, fn() => $executor->applyPending($session, $catalog, DestructiveMigrationPolicy::Deny));
        $this->case(!$this->relationExists($session, $table));
        $applied = $executor->applyPending($session, $catalog, DestructiveMigrationPolicy::Allow);
        $this->case([
            MigrationDirection::Apply,
            0,
            2,
            2,
            8,
        ] === [
            $applied->direction(),
            $applied->previousAppliedCount(),
            $applied->resultingAppliedCount(),
            $applied->changedMigrationCount(),
            $applied->executedOperationCount(),
        ]);
        $this->case(!$this->relationExists($session, $table) && !$this->relationExists($session, $renamedTable));
        $this->case($this->historyMatches($session, $history, [$first, $second]));

        $noOp = $executor->applyPending($session, $catalog, DestructiveMigrationPolicy::Deny);
        $this->case($noOp->isNoOp() && !$session->isClosed() && 1 === $this->scalarInteger($session, 'SELECT 1::int4'));
        $safeRevert = $executor->revertLast($session, $catalog, DestructiveMigrationPolicy::Deny);
        $this->case(MigrationDirection::Revert === $safeRevert->direction() && 1 === $safeRevert->changedMigrationCount() && $this->relationExists($session, $table));
        $this->expect(DestructiveMigrationReviewRequiredException::class, fn() => $executor->revertLast($session, $catalog, DestructiveMigrationPolicy::Deny));
        $destructiveRevert = $executor->revertLast($session, $catalog, DestructiveMigrationPolicy::Allow);
        $this->case(0 === $destructiveRevert->resultingAppliedCount() && !$this->relationExists($session, $table));
        $this->case($executor->revertLast($session, $catalog, DestructiveMigrationPolicy::Deny)->isNoOp());

        $irreversible = new MigrationCatalog([new MigrationDefinition(1, 'irreversible', $first->apply())]);
        $irreversibleExecutor = $this->migrationExecutor($suffix . '_irreversible');
        $irreversibleExecutor->applyPending($session, $irreversible, DestructiveMigrationPolicy::Deny);
        $this->expect(MigrationNotReversibleException::class, fn() => $irreversibleExecutor->revertLast($session, $irreversible, DestructiveMigrationPolicy::Allow));

        $this->historyMismatchCases($session, $catalog, $first, $second, $suffix);
        $this->lockContention($connector, $suffix);
        $this->migrationRollback($session, $suffix);
        $this->reservedHistory($session, $suffix);

        $firstProfile = $this->migrationExecutor($suffix . '_profile_a');
        $secondProfile = $this->migrationExecutor($suffix . '_profile_b');
        $this->case($firstProfile->applyPending($session, new MigrationCatalog([]), DestructiveMigrationPolicy::Deny)->isNoOp());
        $this->case($secondProfile->applyPending($session, new MigrationCatalog([]), DestructiveMigrationPolicy::Deny)->isNoOp());

        $idSentinel = 'KONGONO_MIGRATION_ID_' . strtoupper(bin2hex(random_bytes(6)));
        try {
            new MigrationDefinition(1, $idSentinel, $first->apply());
            throw new RuntimeException('expected_failure_missing');
        } catch (MigrationExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $idSentinel));
        }
        $checksumSentinel = 'KONGONO_MIGRATION_CHECKSUM_' . bin2hex(random_bytes(6));
        try {
            new AppliedMigration(1, 'valid_id', $checksumSentinel);
            throw new RuntimeException('expected_failure_missing');
        } catch (MigrationExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $checksumSentinel));
        }
        $identifierSentinel = str_repeat('z', 64);
        try {
            (new PostgreSql17MigrationCompiler())->compile(new DropTable($this->sqlName($identifierSentinel)));
            throw new RuntimeException('expected_failure_missing');
        } catch (MigrationExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $identifierSentinel));
        }
    }

    private function historyMismatchCases(
        DatabaseSession $session,
        MigrationCatalog $catalog,
        MigrationDefinition $first,
        MigrationDefinition $second,
        string $suffix,
    ): void {
        $cases = [
            [[1, $first->id(), str_repeat('0', 64)]],
            [[1, 'changed_id', $first->sha256()]],
            [[1, $second->id(), $second->sha256()]],
            [[2, $second->id(), $second->sha256()]],
            [[1, 'unknown_entry', str_repeat('1', 64)]],
        ];
        foreach ($cases as $index => $records) {
            $history = $this->sqlName('p6e_history_' . $suffix . '_m' . $index);
            $executor = new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration($history, random_int(-2_000_000_000, 2_000_000_000)));
            $executor->applyPending($session, new MigrationCatalog([]), DestructiveMigrationPolicy::Deny);
            foreach ($records as [$ordinal, $id, $checksum]) {
                $this->insertHistory($session, $history, $ordinal, $id, $checksum);
            }
            $this->expect(MigrationHistoryMismatchException::class, fn() => $executor->applyPending($session, $catalog, DestructiveMigrationPolicy::Allow));
        }

        $shifted = new MigrationCatalog([
            new MigrationDefinition(1, 'inserted_before', $first->apply(), $first->revert()),
            new MigrationDefinition(2, $first->id(), $first->apply(), $first->revert()),
        ]);
        $history = $this->sqlName('p6e_history_' . $suffix . '_shift');
        $executor = new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration($history, random_int(-2_000_000_000, 2_000_000_000)));
        $executor->applyPending($session, new MigrationCatalog([]), DestructiveMigrationPolicy::Deny);
        $this->insertHistory($session, $history, 1, $first->id(), $first->sha256());
        $this->expect(MigrationHistoryMismatchException::class, fn() => $executor->applyPending($session, $shifted, DestructiveMigrationPolicy::Allow));
    }

    private function lockContention(PdoPgsqlConnector $connector, string $suffix): void
    {
        $holder = $connector->openSession();
        $contender = $connector->openSession();
        $transaction = null;
        $lockKey = random_int(-2_000_000_000, 2_000_000_000);
        try {
            $transaction = $holder->begin(new TransactionOptions(TransactionIsolation::Serializable, TransactionAccess::ReadWrite));
            $stream = $this->rows($transaction, new DatabaseStatement(
                'SELECT pg_try_advisory_xact_lock(:kgn_lock)',
                [new DatabaseParameter('kgn_lock', DatabaseValue::integer($lockKey))],
                ResultExpectation::Rows,
            ));
            $this->case(true === $this->row($stream)->at(0)->value());
            $stream->close();
            $executor = new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration($this->sqlName('p6e_history_' . $suffix . '_lock'), $lockKey));
            try {
                $executor->applyPending($contender, new MigrationCatalog([]), DestructiveMigrationPolicy::Deny);
                throw new RuntimeException('expected_failure_missing');
            } catch (MigrationLockUnavailableException $exception) {
                $this->case(!$this->discloses($exception, (string) $lockKey));
            }
        } finally {
            $transaction?->close();
            $holder->close();
            $contender->close();
        }
    }

    private function migrationRollback(DatabaseSession $session, string $suffix): void
    {
        $table = $this->sqlName('p6e_rollback_' . $suffix);
        $index = $this->sqlName('p6e_rollback_index_' . $suffix);
        $column = new SchemaColumn($this->sqlIdentifier('record_id'), SchemaColumnType::Int64, false);
        $catalog = new MigrationCatalog([new MigrationDefinition(1, 'rollback_probe', new MigrationDirectionPlan([
            new CreateTable($table, [$column]),
            new CreateIndex($index, $table, [$column->name()], false),
            new CreateIndex($index, $table, [$column->name()], false),
        ], MigrationRisk::Safe))]);
        $executor = $this->migrationExecutor($suffix . '_rollback');
        try {
            $executor->applyPending($session, $catalog, DestructiveMigrationPolicy::Deny);
            throw new RuntimeException('expected_failure_missing');
        } catch (MigrationExecutionException $exception) {
            $this->case(!$this->discloses($exception, $table->segments()[0]->value()));
        }
        $this->case(!$this->relationExists($session, $table));
    }

    private function reservedHistory(DatabaseSession $session, string $suffix): void
    {
        $history = $this->sqlName('p6e_history_' . $suffix . '_reserved');
        $column = new SchemaColumn($this->sqlIdentifier('record_id'), SchemaColumnType::Int64, false);
        $catalog = new MigrationCatalog([new MigrationDefinition(1, 'reserved_probe', new MigrationDirectionPlan([
            new CreateTable($history, [$column]),
        ], MigrationRisk::Safe))]);
        $executor = new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration($history, random_int(-2_000_000_000, 2_000_000_000)));
        $this->expect(MigrationExecutionException::class, fn() => $executor->applyPending($session, $catalog, DestructiveMigrationPolicy::Deny));
        $this->case(!$this->relationExists($session, $history));
    }

    private function migrationExecutor(string $suffix): PostgreSql17MigrationExecutor
    {
        return new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration(
            $this->sqlName('p6e_history_' . $suffix),
            random_int(-2_000_000_000, 2_000_000_000),
        ));
    }

    /** @param list<MigrationDefinition> $definitions */
    private function historyMatches(DatabaseSession $session, SqlQualifiedName $history, array $definitions): bool
    {
        $stream = $this->rows($session, new DatabaseStatement(
            'SELECT "ordinal", "id", "checksum" FROM ' . $this->quotedName($history) . ' ORDER BY "ordinal" ASC',
            [],
            ResultExpectation::Rows,
        ));
        try {
            foreach ($definitions as $definition) {
                $row = $stream->fetch();
                if (null === $row || [$definition->ordinal(), $definition->id(), $definition->sha256()] !== array_map(static fn(DatabaseValue $value): bool|int|float|string|null => $value->value(), $row->values())) {
                    return false;
                }
            }

            return null === $stream->fetch();
        } finally {
            $stream->close();
        }
    }

    private function insertHistory(DatabaseSession $session, SqlQualifiedName $history, int $ordinal, string $id, string $checksum): void
    {
        $result = $session->execute(new DatabaseStatement(
            'INSERT INTO ' . $this->quotedName($history) . ' ("ordinal", "id", "checksum") VALUES (:ordinal, :id, :checksum)',
            [
                new DatabaseParameter('ordinal', DatabaseValue::integer($ordinal)),
                new DatabaseParameter('id', DatabaseValue::text($id)),
                new DatabaseParameter('checksum', DatabaseValue::text($checksum)),
            ],
            ResultExpectation::Completion,
        ));
        if (!$result instanceof CommandResult) {
            throw new RuntimeException('completion_result_required');
        }
    }

    private function relationExists(DatabaseSession $session, SqlQualifiedName $name): bool
    {
        $stream = $this->rows($session, new DatabaseStatement(
            'SELECT to_regclass(:relation)::text AS relation',
            [new DatabaseParameter('relation', DatabaseValue::text($name->segments()[0]->value()))],
            ResultExpectation::Rows,
        ));
        try {
            return null !== $this->row($stream)->at(0)->value();
        } finally {
            $stream->close();
        }
    }

    private function quotedName(SqlQualifiedName $name): string
    {
        return implode('.', array_map(
            static fn(SqlIdentifier $identifier): string => '"' . str_replace('"', '""', $identifier->value()) . '"',
            $name->segments(),
        ));
    }

    private function unsupportedAndDisclosure(DatabaseSession $session): void
    {
        $stream = $this->rows($session, new DatabaseStatement("SELECT '{}'::json AS unsupported", [], ResultExpectation::Rows));
        $this->expect(DatabaseResultException::class, fn() => $stream->fetch());

        $sentinel = 'KONGONO_SQL_' . bin2hex(random_bytes(8));
        try {
            $session->execute(new DatabaseStatement('SELECT * FROM "' . $sentinel . '"', [], ResultExpectation::Rows));
            throw new RuntimeException('expected_failure_missing');
        } catch (ExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $sentinel));
        }
        $stream = $this->rows($session, new DatabaseStatement('SELECT :value::text AS value', [new DatabaseParameter('value', DatabaseValue::text($sentinel))], ResultExpectation::Rows));
        $this->case($sentinel === $this->row($stream)->at(0)->value());
        $stream->close();

        $parameterSentinel = 'KONGONO_PARAMETER_' . bin2hex(random_bytes(8));
        try {
            $session->execute(new DatabaseStatement('SELECT CAST(:value AS integer)', [new DatabaseParameter('value', DatabaseValue::text($parameterSentinel))], ResultExpectation::Rows));
            throw new RuntimeException('expected_failure_missing');
        } catch (ExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $parameterSentinel));
        }
    }

    private function independentSessions(PdoPgsqlConnector $connector): void
    {
        $first = $connector->openSession();
        $second = $connector->openSession();
        try {
            $this->completion($first, 'CREATE TEMP TABLE phase6c_isolation (id integer)');
            $this->completion($first, 'INSERT INTO phase6c_isolation (id) VALUES (1)');
            $this->expect(DatabaseExecutionException::class, fn() => $second->execute(new DatabaseStatement('SELECT * FROM phase6c_isolation', [], ResultExpectation::Rows)));
            $this->completion($second, 'CREATE TEMP TABLE phase6c_isolation (id integer)');
            $this->completion($second, 'INSERT INTO phase6c_isolation (id) VALUES (2), (3)');
            $this->case(1 === $this->scalarInteger($first, 'SELECT count(*)::int8 FROM phase6c_isolation'));
            $this->case(2 === $this->scalarInteger($second, 'SELECT count(*)::int8 FROM phase6c_isolation'));
            $first->close();
            $this->expect(InvalidDatabaseStateException::class, fn() => $first->execute(new DatabaseStatement('SELECT 1', [], ResultExpectation::Rows)));
            $this->case(1 === $this->scalarInteger($second, 'SELECT 1::int4 AS value'));
        } finally {
            $first->close();
            $second->close();
        }
    }

    private function transactionVisibility(PdoPgsqlConnector $connector): void
    {
        $writer = $connector->openSession();
        $reader = $connector->openSession();
        $table = 'phase6h_visibility_' . bin2hex(random_bytes(6));
        $quoted = '"' . $table . '"';
        try {
            $this->completion($writer, 'CREATE TABLE ' . $quoted . ' (id integer PRIMARY KEY)');
            $first = $writer->begin(new TransactionOptions(TransactionIsolation::ReadCommitted, TransactionAccess::ReadWrite));
            $this->completion($first, 'INSERT INTO ' . $quoted . ' (id) VALUES (1)');
            $this->case(0 === $this->scalarInteger($reader, 'SELECT count(*)::int8 FROM ' . $quoted));
            $first->commit();
            $this->case(TransactionState::Committed === $first->state());
            $this->case(1 === $this->scalarInteger($reader, 'SELECT count(*)::int8 FROM ' . $quoted));

            $second = $writer->begin(new TransactionOptions(TransactionIsolation::ReadCommitted, TransactionAccess::ReadWrite));
            $this->completion($second, 'INSERT INTO ' . $quoted . ' (id) VALUES (2)');
            $second->rollback();
            $this->case(TransactionState::RolledBack === $second->state());
            $this->case(1 === $this->scalarInteger($reader, 'SELECT count(*)::int8 FROM ' . $quoted));
        } finally {
            try {
                $this->completion($writer, 'DROP TABLE IF EXISTS ' . $quoted);
            } finally {
                $writer->close();
                $reader->close();
            }
        }
    }

    private function connectionDisclosure(): void
    {
        $sentinel = 'KONGONO_CONNECTION_' . bin2hex(random_bytes(8));
        try {
            (new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', $this->unoccupiedPort(), 'postgres', self::ROLE, $sentinel)))->openSession();
            throw new RuntimeException('expected_failure_missing');
        } catch (ExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $sentinel));
        }


        $databaseSentinel = 'KONGONO_DSN_' . bin2hex(random_bytes(8));
        try {
            (new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', (int) $this->port, $databaseSentinel, self::ROLE, '')))->openSession();
            throw new RuntimeException('expected_failure_missing');
        } catch (ExceptionInterface $exception) {
            $this->case(!$this->discloses($exception, $databaseSentinel));
        }
    }

    private function terminalTransactionFailure(PdoPgsqlConnector $connector): void
    {
        $victim = $connector->openSession();
        $killer = $connector->openSession();
        try {
            $process = $this->scalarInteger($victim, 'SELECT pg_backend_pid()::int4 AS process');
            $transaction = $victim->begin(new TransactionOptions());
            $stream = $this->rows($killer, new DatabaseStatement(
                'SELECT pg_terminate_backend(:process)::boolean AS terminated',
                [new DatabaseParameter('process', DatabaseValue::integer($process))],
                ResultExpectation::Rows,
            ));
            $this->case(true === $this->row($stream)->at(0)->value());
            $stream->close();
            $this->expect(DatabaseTransactionCommitException::class, fn() => $transaction->commit());
            $this->case(TransactionState::Failed === $transaction->state() && $transaction->isClosed());
            $this->expect(InvalidDatabaseStateException::class, fn() => $transaction->commit());
            $this->case(1 === $this->scalarInteger($killer, 'SELECT 1::int4 AS peer_usable'));
        } finally {
            $victim->close();
            $killer->close();
        }
    }

    private function discloses(ExceptionInterface|MappingExceptionInterface|MigrationExceptionInterface|SqlExceptionInterface $exception, string $sentinel): bool
    {
        $surface = $exception->getMessage() . (string) $exception . $exception->diagnosticId() . $exception->diagnosticTitle() . $exception->diagnosticRemediation();
        foreach ($exception->diagnosticContext()->fields() as $field) {
            $surface .= (string) $field->value();
        }

        return str_contains($surface, $sentinel);
    }

    /** @param list<mixed> $arguments */
    private function generatedInstance(string $class, array $arguments = []): object
    {
        if (!class_exists($class, false)) {
            throw new RuntimeException('generated_class_unavailable');
        }

        return (new ReflectionClass($class))->newInstanceArgs($arguments);
    }

    /** @param list<mixed> $arguments */
    private function invokeGenerated(object $target, string $method, array $arguments = []): mixed
    {
        return (new ReflectionMethod($target, $method))->invokeArgs($target, $arguments);
    }

    private function isGeneratedType(object $value, string $expected): bool
    {
        return (new ReflectionClass($value))->getName() === $expected;
    }

    /**
     * @param list<string> $accessors
     *
     * @return list<mixed>
     */
    private function recordValues(object $record, array $accessors): array
    {
        return array_map(fn(string $accessor): mixed => $this->invokeGenerated($record, $accessor), $accessors);
    }

    private function writeFixture(string $path, string $bytes): void
    {
        $this->createDirectory(dirname($path));
        $written = file_put_contents($path, $bytes, LOCK_EX);
        if (strlen($bytes) !== $written) {
            throw new RuntimeException('mapping_fixture_write_failed');
        }
    }

    private function connector(): PdoPgsqlConnector
    {
        if (null === $this->port) {
            throw new RuntimeException('provider_port_unavailable');
        }

        return new PdoPgsqlConnector(new PdoPgsqlConfiguration('127.0.0.1', $this->port, 'postgres', self::ROLE, ''));
    }

    private function createRole(): void
    {
        $this->process([
            $this->executable('psql.exe'), '-h', '127.0.0.1', '-p', (string) $this->port,
            '-U', 'postgres', '-d', 'postgres', '-v', 'ON_ERROR_STOP=1', '-c',
            'CREATE ROLE ' . self::ROLE . ' LOGIN; GRANT CREATE ON SCHEMA public TO ' . self::ROLE,
        ], true);
    }

    private function initialize(): void
    {
        if (null === $this->data) {
            throw new RuntimeException('data_directory_unavailable');
        }
        $this->process([$this->executable('initdb.exe'), '-D', $this->data, '--auth=trust', '--no-sync', '--no-locale', '--username=postgres'], true);
    }

    private function start(): void
    {
        if (null === $this->data || null === $this->port || null === $this->workspace) {
            throw new RuntimeException('provider_state_unavailable');
        }
        $this->startProviderProcess([
            $this->executable('pg_ctl.exe'), 'start', '-D', $this->data, '-l', $this->workspace . '/postgres.log',
            '-w', '-t', '30', '-o', '-h 127.0.0.1 -p ' . $this->port . ' -c listen_addresses=127.0.0.1',
        ]);
        $this->started = true;
    }

    /** @param list<string> $command */
    private function startProviderProcess(array $command): void
    {
        if (null === $this->workspace) {
            throw new RuntimeException('provider_state_unavailable');
        }
        $pipes = [];
        $process = proc_open(
            $command,
            [
                ['pipe', 'r'],
                ['file', $this->workspace . '/pgctl.stdout', 'ab'],
                ['file', $this->workspace . '/pgctl.stderr', 'ab'],
            ],
            $pipes,
            null,
            null,
            ['bypass_shell' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || !isset($pipes[0])) {
            throw new RuntimeException('process_start_failed');
        }
        fclose($pipes[0]);
        $exit = null;
        $deadline = microtime(true) + 60;
        while (microtime(true) < $deadline) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exit = $status['exitcode'];
                break;
            }
            usleep(10_000);
        }
        if (null === $exit) {
            proc_terminate($process);
        }
        $closedExit = proc_close($process);
        $exit = null !== $exit && $exit >= 0 ? $exit : $closedExit;
        if (0 !== $exit) {
            throw new RuntimeException('process_failed');
        }
    }

    private function cleanup(): void
    {
        $failure = null;
        if ($this->started && null !== $this->data) {
            try {
                $result = $this->process([$this->executable('pg_ctl.exe'), 'stop', '-D', $this->data, '-w', '-t', '30', '-m', 'fast'], false);
                if (0 !== $result['exit']) {
                    $this->process([$this->executable('pg_ctl.exe'), 'stop', '-D', $this->data, '-w', '-t', '30', '-m', 'immediate'], true);
                }
            } catch (Throwable $exception) {
                $failure = $exception;
            }
            $this->started = false;
        }
        if (null !== $this->port && $this->listening($this->port)) {
            $failure ??= new RuntimeException('provider_process_remains');
        } else {
            $this->case(true);
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
        } else {
            $this->case(true);
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     *
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private function process(array $command, bool $requireSuccess, ?string $workingDirectory = null, array $environment = []): array
    {
        $pipes = [];
        $currentEnvironment = getenv();
        $processEnvironment = [] === $environment
            ? null
            : array_merge($currentEnvironment, $environment);
        $process = proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            $processEnvironment,
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
        $deadline = microtime(true) + 60;
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
        foreach (['postgres.exe', 'initdb.exe', 'pg_ctl.exe', 'psql.exe'] as $name) {
            $path = $resolved . '/' . $name;
            if (!is_file($path) || is_link($path) || !is_executable($path)) {
                throw new RuntimeException('binary_invalid');
            }
            $result = $this->process([$path, '--version'], true);
            if (1 !== preg_match('/\b17\.2\b/', $result['stdout'])) {
                throw new RuntimeException('binary_version_invalid');
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

    private function removeWorkspace(string $workspace): void
    {
        $temporary = rtrim(str_replace('\\', '/', (string) realpath(sys_get_temp_dir())), '/');
        $resolved = realpath($workspace);
        if (false === $resolved || dirname(str_replace('\\', '/', $resolved)) !== $temporary || !str_starts_with(basename($resolved), self::PREFIX)) {
            throw new RuntimeException('cleanup_scope_invalid');
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isLink()) {
                throw new RuntimeException('cleanup_reparse_detected');
            }
            $path = $entry->getPathname();
            $removed = $entry->isDir() ? $this->removeWithRetry($path, true) : ($entry->isFile() && $this->removeWithRetry($path, false));
            if (!$removed) {
                throw new RuntimeException('cleanup_failed');
            }
        }
        if (!$this->removeWithRetry($resolved, true)) {
            throw new RuntimeException('cleanup_failed');
        }
    }

    private function copyTree(string $source, string $destination): void
    {
        $this->createDirectory($destination);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($source) + 1));
            if ($entry->isLink()) {
                throw new RuntimeException('copy_reparse_detected');
            }
            if ($entry->isDir()) {
                $this->createDirectory($destination . '/' . $relative);
            } elseif ($entry->isFile()) {
                $this->writeFixture($destination . '/' . $relative, $this->readOrdinary($entry->getPathname()));
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
        if (file_exists($path) || is_link($path) || !mkdir($path, 0700, true)) {
            throw new RuntimeException('directory_create_failed');
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

    private function directoryIdentity(string $directory): string
    {
        $root = rtrim(str_replace('\\', '/', $directory), '/');
        $records = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || $entry->isLink()) {
                throw new RuntimeException('data_snapshot_invalid');
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            $records[substr($path, strlen($root) + 1)] = hash('sha256', $this->readOrdinary($path));
        }
        ksort($records, SORT_STRING);

        return hash('sha256', json_encode($records, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
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

    private function unoccupiedPort(): int
    {
        $port = $this->availablePort();
        if ($this->listening($port)) {
            throw new RuntimeException('unexpected_listener');
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

    private function scalarInteger(DatabaseSession $session, string $sql): int
    {
        $stream = $this->rows($session, new DatabaseStatement($sql, [], ResultExpectation::Rows));
        $value = $this->row($stream)->at(0)->value();
        $stream->close();
        if (!is_int($value)) {
            throw new RuntimeException('integer_result_required');
        }

        return $value;
    }

    private function completion(DatabaseSession|DatabaseTransaction $executor, string $sql): CommandResult
    {
        $result = $executor->execute(new DatabaseStatement($sql, [], ResultExpectation::Completion));
        if (!$result instanceof CommandResult) {
            throw new RuntimeException('completion_result_required');
        }

        return $result;
    }

    private function completionStatement(DatabaseSession|DatabaseTransaction $executor, DatabaseStatement $statement): CommandResult
    {
        $result = $executor->execute($statement);
        if (!$result instanceof CommandResult) {
            throw new RuntimeException('completion_result_required');
        }

        return $result;
    }

    private function sqlIdentifier(string $value): SqlIdentifier
    {
        return new SqlIdentifier($value);
    }

    private function sqlName(string $value): SqlQualifiedName
    {
        return new SqlQualifiedName([$this->sqlIdentifier($value)]);
    }

    private function rows(DatabaseSession|DatabaseTransaction $executor, DatabaseStatement $statement): RowStream
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

    /** @param class-string<Throwable> $type */
    private function expect(string $type, callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable $exception) {
            $this->case($exception instanceof $type);

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
