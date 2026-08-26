<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ChangeSet;
use Kongono\Persistence\ConcurrencyToken;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\PersistenceChange;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateValidator;
use Kongono\PersistenceBuild\Tests\Fixture\Domain\Account;
use Kongono\PersistenceBuild\Tests\Fixture\Domain\Profile;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class GeneratedPersistenceStateAcceptanceTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/kongono-persistence-state-acceptance-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporaryRoot));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->temporaryRoot)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->temporaryRoot);
    }

    public function testFreshValidatedGeneratedFileExercisesEveryAdapterWithoutMutation(): void
    {
        $root = dirname(__DIR__, 5);
        $source = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $source);
        $generated = (new GeneratedPersistenceStateGenerator())->generate($root, $source, $persistence->canonicalBytes());
        (new GeneratedPersistenceStateValidator())->validateCurrent(
            $root,
            $source,
            $persistence->canonicalBytes(),
            $generated->canonicalBytes(),
        );
        $path = $this->temporaryRoot . '/generated.php';
        self::assertSame(strlen($generated->canonicalBytes()), file_put_contents($path, $generated->canonicalBytes()));
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path);
        exec($command, $lintOutput, $lintStatus);
        self::assertSame(0, $lintStatus, implode("\n", $lintOutput));
        $map = $generated->classMap();
        $accountAdapter = $map[Account::class];
        $profileAdapter = $map[Profile::class];
        if (!class_exists($accountAdapter, false)) {
            require $path;
        }
        $record = ['id' => 7, 'name' => 'Ada', 'version' => 3];
        $recordBefore = $record;
        $account = $accountAdapter::hydrate($record);
        self::assertInstanceOf(Account::class, $account);
        self::assertSame($record, $accountAdapter::extract($account));
        self::assertSame($recordBefore, $record);
        $identity = $accountAdapter::identity($account);
        self::assertInstanceOf(EntityIdentity::class, $identity);
        self::assertSame('i:7', $identity->key());
        $snapshot = $accountAdapter::snapshot($account);
        self::assertIsArray($snapshot);
        $unchanged = $accountAdapter::changes($account, $snapshot);
        self::assertInstanceOf(ChangeSet::class, $unchanged);
        self::assertTrue($unchanged->isEmpty());
        $changed = new Account(7, 'Grace', 4);
        $changes = $accountAdapter::changes($changed, $snapshot);
        self::assertInstanceOf(ChangeSet::class, $changes);
        $update = $changes->changes()[0];
        self::assertSame(['name', 'version'], $update->changedFields());
        self::assertSame(ChangeKind::Update, $update->kind());
        $updateToken = $update->expectedConcurrencyToken();
        self::assertInstanceOf(ConcurrencyToken::class, $updateToken);
        self::assertSame('i:3', $updateToken->value());
        self::assertSame('kongono.scalar-v1', $updateToken->scheme());
        self::assertSame($snapshot, ['id' => 7, 'name' => 'Ada', 'version' => 3]);
        $insertion = $accountAdapter::insertion($changed);
        self::assertInstanceOf(ChangeSet::class, $insertion);
        $insert = $insertion->changes()[0];
        self::assertInstanceOf(PersistenceChange::class, $insert);
        self::assertSame(ChangeKind::Insert, $insert->kind());
        self::assertNull($insert->identity());
        self::assertNull($insert->expectedConcurrencyToken());
        self::assertSame(['id', 'name', 'version'], $insert->changedFields());
        $deletion = $accountAdapter::deletion($changed);
        self::assertInstanceOf(ChangeSet::class, $deletion);
        $delete = $deletion->changes()[0];
        self::assertInstanceOf(PersistenceChange::class, $delete);
        self::assertSame(ChangeKind::Delete, $delete->kind());
        $deleteIdentity = $delete->identity();
        self::assertInstanceOf(EntityIdentity::class, $deleteIdentity);
        self::assertSame('i:7', $deleteIdentity->key());
        $deleteToken = $delete->expectedConcurrencyToken();
        self::assertInstanceOf(ConcurrencyToken::class, $deleteToken);
        self::assertSame('i:4', $deleteToken->value());

        $profileRecord = ['id' => 8, 'accountId' => 7, 'displayName' => null];
        $profile = $profileAdapter::hydrate($profileRecord);
        self::assertInstanceOf(Profile::class, $profile);
        self::assertSame($profileRecord, $profileAdapter::extract($profile));
        $profileSnapshot = $profileAdapter::snapshot($profile);
        self::assertIsArray($profileSnapshot);
        $profileChanges = $profileAdapter::changes($profile, $profileSnapshot);
        self::assertInstanceOf(ChangeSet::class, $profileChanges);
        self::assertTrue($profileChanges->isEmpty());
        $profileDeletion = $profileAdapter::deletion($profile);
        self::assertInstanceOf(ChangeSet::class, $profileDeletion);
        self::assertNull($profileDeletion->changes()[0]->expectedConcurrencyToken());
    }

    public function testGeneratedShapeAndScalarValidationFailWithFixedDiagnostic(): void
    {
        $root = dirname(__DIR__, 5);
        $source = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $source);
        $generated = (new GeneratedPersistenceStateGenerator())->generate($root, $source, $persistence->canonicalBytes());
        $path = $this->temporaryRoot . '/generated.php';
        self::assertSame(strlen($generated->canonicalBytes()), file_put_contents($path, $generated->canonicalBytes()));
        $adapter = $generated->classMap()[Account::class];
        if (!class_exists($adapter, false)) {
            require $path;
        }

        foreach ([
            ['id' => 7, 'name' => 'Ada'],
            ['id' => 7, 'name' => 'Ada', 'version' => 3, 'unknown' => true],
            ['id' => '7', 'name' => 'Ada', 'version' => 3],
            ['id' => 7, 'name' => "\xFF", 'version' => 3],
        ] as $invalid) {
            try {
                $adapter::hydrate($invalid);
                self::fail('Expected generated value rejection.');
            } catch (InvalidGeneratedPersistenceStateException $failure) {
                self::assertSame('KGN-PERSISTENCE-BUILD-1015', $failure->diagnosticId());
            }
        }
    }
}
