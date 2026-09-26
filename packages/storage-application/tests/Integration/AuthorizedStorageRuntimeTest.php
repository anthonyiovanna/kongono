<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Tests\Integration;

use Fiber;
use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageApplication\Authorization\StorageOperationAuthorizer;
use Kongono\StorageApplication\Exception\StorageApplicationInvariantException;
use Kongono\StorageApplication\Exception\StorageAuthorizationDeniedException;
use Kongono\StorageApplication\Exception\StorageAuthorizationFailureException;
use Kongono\StorageApplication\Exception\StorageRuntimeCompositionException;
use Kongono\StorageApplication\Runtime\AuthorizedStorageRuntime;
use Kongono\StorageApplication\Runtime\StorageApplicationRuntimeFactory;
use Kongono\StorageApplication\Tests\Fixture\CountingByteSource;
use Kongono\StorageApplication\Tests\Fixture\RecordingStorageAuthorizer;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthorizedStorageRuntimeTest extends TestCase
{
    public function testDenialFailureAndMismatchDoNotTouchCallerSourceOrObjectRoot(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-authorized-runtime-' . bin2hex(random_bytes(8));
        mkdir($root);
        file_put_contents($root . DIRECTORY_SEPARATOR . 'sentinel', 'preserve');
        try {
            $name = new StorageProfileName('demo');
            $set = new StorageProfileSet([new StorageProfileDeclaration(
                ContractVersion::Version1,
                $name,
                new StorageNamespace('objects'),
                new StorageCapabilitySet([StorageCapability::Read, StorageCapability::Create, StorageCapability::Replace, StorageCapability::Delete, StorageCapability::ContentDigest, StorageCapability::ConditionalMutation]),
                StorageDigestAlgorithm::Sha256,
                new StorageProfileLimits(1024, 4096, 10),
            )]);
            $current = new CurrentStorageApplicationBuild($set, (new StorageProfileBuilder())->build($set)->artifact());
            $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), $name);
            $policy = new RecordingStorageAuthorizer(false);
            $runtime = (new StorageApplicationRuntimeFactory())->create($current, $name, new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $policy);
            $source = new CountingByteSource('abc');
            $request = self::request('create-one', StorageOperation::Create);
            try {
                $runtime->create($request, $source);
                self::fail('Denied request was delegated.');
            } catch (StorageAuthorizationDeniedException) {
                self::assertSame(1, $policy->calls);
                self::assertSame($request, $policy->lastRequest);
                self::assertSame(0, $source->reads);
                self::assertSame(0, $source->closes);
            }
            $policy->failure = new RuntimeException('private-collaborator-marker');
            try {
                $runtime->create($request, $source);
                self::fail('Collaborator failure was ignored.');
            } catch (StorageAuthorizationFailureException $failure) {
                self::assertStringNotContainsString('private-collaborator-marker', (string) $failure);
                self::assertSame(2, $policy->calls);
                self::assertSame(0, $source->reads);
                self::assertSame(0, $source->closes);
            }
            $policy->failure = null;
            $policy->allow = true;
            try {
                $runtime->replace($request, $source);
                self::fail('Operation mismatch was authorized.');
            } catch (StorageApplicationInvariantException) {
                self::assertSame(2, $policy->calls);
                self::assertSame(0, $source->reads);
            }
            self::assertSame('preserve', file_get_contents($root . DIRECTORY_SEPARATOR . 'sentinel'));
            self::assertSame(StorageMutationOutcome::Completed, $runtime->create($request, $source)->outcome());
            self::assertSame(3, $policy->calls);
            self::assertGreaterThan(0, $source->reads);
            self::assertSame(1, $source->closes);
            self::assertSame(StorageMutationOutcome::AlreadyExists, $runtime->create(self::request('create-two', StorageOperation::Create), new CountingByteSource('abc'))->outcome());
            self::assertSame('preserve', file_get_contents($root . DIRECTORY_SEPARATOR . 'sentinel'));
        } finally {
            $this->removePrivate($root);
        }
    }

    public function testEveryOperationAuthorizesTheUnchangedRequestOnceAndPreservesResults(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-authorized-all-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        self::assertSame(8, file_put_contents($root . DIRECTORY_SEPARATOR . 'sentinel', 'preserve'));
        try {
            $name = new StorageProfileName('demo');
            $set = new StorageProfileSet([new StorageProfileDeclaration(
                ContractVersion::Version1,
                $name,
                new StorageNamespace('objects'),
                new StorageCapabilitySet([StorageCapability::Read, StorageCapability::Create, StorageCapability::Replace, StorageCapability::Delete, StorageCapability::List, StorageCapability::ContentDigest, StorageCapability::ConditionalMutation]),
                StorageDigestAlgorithm::Sha256,
                new StorageProfileLimits(1024, 4096, 10),
            )]);
            $current = new CurrentStorageApplicationBuild($set, (new StorageProfileBuilder())->build($set)->artifact());
            $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), $name);
            $policy = new RecordingStorageAuthorizer(true);
            $runtime = (new StorageApplicationRuntimeFactory())->create($current, $name, new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $policy);
            $identity = new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('one'));

            $create = self::request('all-create', StorageOperation::Create);
            $created = $runtime->create($create, new CountingByteSource('abc'));
            self::assertSame($create, $policy->lastRequest);
            self::assertSame(1, $policy->calls);
            self::assertSame(StorageMutationOutcome::Completed, $created->outcome());
            $firstVersion = $created->resultingVersion();
            self::assertInstanceOf(StorageObjectVersion::class, $firstVersion);

            $read = new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('all-read'), $identity, new StorageByteCount(4096));
            $readResult = $runtime->read($read);
            self::assertSame($read, $policy->lastRequest);
            self::assertSame(2, $policy->calls);
            self::assertSame(StorageReadOutcome::Completed, $readResult->result()->outcome());
            $readSource = $readResult->source();
            self::assertInstanceOf(StorageByteSource::class, $readSource);
            self::assertSame('abc', $readSource->read(1024)?->bytes());
            self::assertNull($readSource->read(1024));
            $readSource->close();

            $list = new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('all-list'), new StorageNamespace('objects'), 10);
            self::assertSame(StorageListOutcome::Completed, $runtime->list($list)->outcome());
            self::assertSame($list, $policy->lastRequest);
            self::assertSame(3, $policy->calls);

            $replace = self::request('all-replace', StorageOperation::Replace, StorageMutationCondition::ifVersion($firstVersion));
            $replaced = $runtime->replace($replace, new CountingByteSource('abc'));
            self::assertSame(StorageMutationOutcome::Completed, $replaced->outcome());
            self::assertSame($replace, $policy->lastRequest);
            self::assertSame(4, $policy->calls);
            $secondVersion = $replaced->resultingVersion();
            self::assertInstanceOf(StorageObjectVersion::class, $secondVersion);

            $delete = new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('all-delete'), $identity, StorageMutationCondition::ifVersion($secondVersion));
            self::assertSame(StorageMutationOutcome::Completed, $runtime->delete($delete)->outcome());
            self::assertSame($delete, $policy->lastRequest);
            self::assertSame(5, $policy->calls);
            self::assertSame('preserve', file_get_contents($root . DIRECTORY_SEPARATOR . 'sentinel'));
        } finally {
            $this->removePrivate($root);
        }
    }

    public function testWrongBindingAndReentrantAuthorizationCannotTouchWriteSource(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-authorized-reentry-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        self::assertSame(8, file_put_contents($root . DIRECTORY_SEPARATOR . 'sentinel', 'preserve'));
        try {
            $name = new StorageProfileName('demo');
            $set = new StorageProfileSet([new StorageProfileDeclaration(
                ContractVersion::Version1,
                $name,
                new StorageNamespace('objects'),
                new StorageCapabilitySet([StorageCapability::Read, StorageCapability::Create, StorageCapability::ContentDigest]),
                StorageDigestAlgorithm::Sha256,
                new StorageProfileLimits(1024, 4096, 10),
            )]);
            $current = new CurrentStorageApplicationBuild($set, (new StorageProfileBuilder())->build($set)->artifact());
            $local = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), $name);
            $adapter = new class ($local) implements StorageAdapter {
                public int $descriptors = 0;
                public int $operations = 0;
                public function __construct(private ContainedLocalStorageAdapter $inner) {}
                public function descriptor(): StorageAdapterDescriptor
                {
                    ++$this->descriptors;
                    return $this->inner->descriptor();
                }
                public function read(StorageReadRequest $request): StorageAdapterRead
                {
                    ++$this->operations;
                    return $this->inner->read($request);
                }
                public function write(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
                {
                    ++$this->operations;
                    return $this->inner->write($request, $source);
                }
                public function delete(StorageDeleteRequest $request): StorageMutationResult
                {
                    ++$this->operations;
                    return $this->inner->delete($request);
                }
                public function list(StorageListRequest $request): StorageListResult
                {
                    ++$this->operations;
                    return $this->inner->list($request);
                }
            };
            $factory = new StorageApplicationRuntimeFactory();
            $policy = new RecordingStorageAuthorizer(true);
            try {
                $factory->create($current, new StorageProfileName('missing'), new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $policy);
                self::fail('Absent selected profile was bound.');
            } catch (StorageRuntimeCompositionException) {
            }
            self::assertSame(0, $adapter->descriptors);
            try {
                $factory->create($current, $name, new StorageAdapterIdentity('wrong-adapter', 1), $adapter, $policy);
                self::fail('Wrong adapter identity was bound.');
            } catch (StorageRuntimeCompositionException) {
            }
            self::assertSame(1, $adapter->descriptors);
            self::assertSame(0, $adapter->operations);
            self::assertSame(0, $policy->calls);

            $source = new CountingByteSource('abc');
            $reentrant = new class ($source) implements StorageOperationAuthorizer {
                public ?AuthorizedStorageRuntime $runtime = null;
                public int $calls = 0;
                public function __construct(private CountingByteSource $source) {}
                public function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): bool
                {
                    ++$this->calls;
                    if ($request instanceof StorageWriteRequest && $this->runtime instanceof AuthorizedStorageRuntime) {
                        $this->runtime->create($request, $this->source);
                    }
                    return true;
                }
            };
            $runtime = $factory->create($current, $name, new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $reentrant);
            self::assertSame(2, $adapter->descriptors);
            self::assertSame(0, $adapter->operations);
            $reentrant->runtime = $runtime;
            try {
                $runtime->create(self::request('reentry-create', StorageOperation::Create), $source);
                self::fail('Reentrant authorization was delegated.');
            } catch (StorageAuthorizationFailureException $failure) {
                self::assertSame(1, $reentrant->calls);
                self::assertStringNotContainsString('one', (string) $failure);
            }
            self::assertSame(0, $source->reads);
            self::assertSame(0, $source->closes);
            self::assertSame(0, $adapter->operations);
            self::assertSame('preserve', file_get_contents($root . DIRECTORY_SEPARATOR . 'sentinel'));
        } finally {
            $this->removePrivate($root);
        }
    }

    public function testOverlappingFiberCallCannotTouchItsSourceAndGuardRetires(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-authorized-fiber-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        self::assertSame(8, file_put_contents($root . DIRECTORY_SEPARATOR . 'sentinel', 'preserve'));
        try {
            $name = new StorageProfileName('demo');
            $set = new StorageProfileSet([new StorageProfileDeclaration(
                ContractVersion::Version1,
                $name,
                new StorageNamespace('objects'),
                new StorageCapabilitySet([StorageCapability::Read, StorageCapability::Create, StorageCapability::ContentDigest]),
                StorageDigestAlgorithm::Sha256,
                new StorageProfileLimits(1024, 4096, 10),
            )]);
            $current = new CurrentStorageApplicationBuild($set, (new StorageProfileBuilder())->build($set)->artifact());
            $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), $current->artifact(), $name);
            $policy = new class implements StorageOperationAuthorizer {
                public int $calls = 0;
                public function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): bool
                {
                    ++$this->calls;
                    if (1 === $this->calls) {
                        Fiber::suspend('held');
                    }
                    return true;
                }
            };
            $runtime = (new StorageApplicationRuntimeFactory())->create($current, $name, new StorageAdapterIdentity('contained-local-filesystem', 1), $adapter, $policy);
            $firstSource = new CountingByteSource('abc');
            $fiber = new Fiber(static fn(): StorageMutationResult => $runtime->create(self::request('fiber-create', StorageOperation::Create), $firstSource));
            self::assertSame('held', $fiber->start());
            $overlapSource = new CountingByteSource('abc');
            try {
                $runtime->create(self::request('overlap-create', StorageOperation::Create), $overlapSource);
                self::fail('Overlapping dispatch was delegated.');
            } catch (StorageApplicationInvariantException) {
                self::assertSame(1, $policy->calls);
                self::assertSame(0, $overlapSource->reads);
                self::assertSame(0, $overlapSource->closes);
            }
            $fiber->resume();
            self::assertTrue($fiber->isTerminated());
            $fiberResult = $fiber->getReturn();
            self::assertInstanceOf(StorageMutationResult::class, $fiberResult);
            self::assertSame(StorageMutationOutcome::Completed, $fiberResult->outcome());
            self::assertSame(1, $firstSource->closes);
            self::assertSame(StorageMutationOutcome::AlreadyExists, $runtime->create(self::request('after-fiber', StorageOperation::Create), new CountingByteSource('abc'))->outcome());
            self::assertSame(2, $policy->calls);
            self::assertSame('preserve', file_get_contents($root . DIRECTORY_SEPARATOR . 'sentinel'));
        } finally {
            $this->removePrivate($root);
        }
    }

    public function testPublicFailureEvidenceHidesCapturedTraceArgumentsInBothPhpModes(): void
    {
        $original = ini_get('zend.exception_ignore_args');
        self::assertIsString($original);
        $marker = 'private-trace-marker-' . bin2hex(random_bytes(8));
        try {
            foreach (['0', '1'] as $mode) {
                self::assertNotFalse(ini_set('zend.exception_ignore_args', $mode));
                self::assertSame($mode, ini_get('zend.exception_ignore_args'));
                $thrower = static function (string $argument): never {
                    throw new RuntimeException('private-native-marker');
                };
                try {
                    $thrower($marker);
                } catch (RuntimeException $previous) {
                    if ('0' === $mode) {
                        self::assertSame($marker, $previous->getTrace()[0]['args'][0] ?? null);
                    } else {
                        self::assertArrayNotHasKey('args', $previous->getTrace()[0]);
                    }
                    $failure = StorageAuthorizationFailureException::failed($previous);
                    $context = $failure->diagnosticContext();
                    $public = $failure->getMessage() . (string) $failure . $failure->diagnosticTitle() . $failure->diagnosticRemediation()
                        . $context->operation() . $context->violatedInvariant();
                    foreach ($context->fields() as $field) {
                        $public .= $field->key() . $field->value();
                    }
                    self::assertStringNotContainsString($marker, $public);
                    self::assertStringNotContainsString('private-native-marker', $public);
                    self::assertSame($previous, $failure->getPrevious());
                }
            }
        } finally {
            ini_set('zend.exception_ignore_args', $original);
        }
    }

    private static function request(string $operationName, StorageOperation $operation, ?StorageMutationCondition $condition = null): StorageWriteRequest
    {
        $bytes = 'abc';
        return new StorageWriteRequest(
            ContractVersion::Version1,
            new StorageOperationIdentity($operationName),
            $operation,
            new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('one')),
            new StorageByteCount(strlen($bytes)),
            new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)),
            new StorageMediaType('text/plain'),
            new StorageMetadata([]),
            $condition ?? StorageMutationCondition::ifAbsent(),
        );
    }

    private function removePrivate(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                self::fail('Unexpected private fixture entry.');
            }
            $entry->isDir() ? $this->removePrivate($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($path);
    }
}
