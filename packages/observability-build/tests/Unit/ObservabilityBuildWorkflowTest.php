<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Tests\Unit;

use Kongono\ObservabilityBuild\Build\ObservabilityArtifactBuilder;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifactValidator;
use Kongono\ObservabilityBuild\Build\ObservabilitySourceReader;
use Kongono\ObservabilityBuild\Exception\InvalidObservabilityArtifactException;
use Kongono\ObservabilityBuild\Exception\InvalidObservabilitySourceException;
use Kongono\ObservabilityBuild\Exception\ObservabilityWorkflowException;
use Kongono\ObservabilityBuild\Exception\StaleObservabilityArtifactException;
use Kongono\ObservabilityBuild\Workflow\CurrentObservabilityConfiguration;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use Kongono\ObservabilityBuild\Workflow\PlannedObservabilityConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ObservabilityBuildWorkflowTest extends TestCase
{
    /** @var list<string> */
    private array $roots = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->roots) as $root) {
            self::removeTree($root);
        }
    }

    public function testPublicWorkflowAndImmutableResultSurfacesAreExact(): void
    {
        $workflow = new ReflectionClass(ObservabilityBuildWorkflow::class);
        self::assertTrue($workflow->isFinal());
        self::assertSame(['build', 'check', 'inspect'], $this->declaredPublicMethods($workflow));

        $planned = new ReflectionClass(PlannedObservabilityConfiguration::class);
        self::assertTrue($planned->isFinal());
        self::assertTrue($planned->isReadOnly());
        self::assertSame(
            ['artifactBasename', 'artifactSha256', 'profile', 'publicationRequired', 'sourceSha256'],
            $this->declaredPublicMethods($planned, false),
        );

        $current = new ReflectionClass(CurrentObservabilityConfiguration::class);
        self::assertTrue($current->isFinal());
        self::assertTrue($current->isReadOnly());
        self::assertSame(
            ['artifactBasename', 'artifactSha256', 'batchPolicy', 'evidence', 'profile', 'resource', 'sourceSha256'],
            $this->declaredPublicMethods($current, false),
        );
    }

    public function testCanonicalArtifactIsDeterministicAndSeparatesSourceModelAndImplementationIdentity(): void
    {
        $firstRoot = $this->application(self::source());
        $secondRoot = $this->application(self::source());
        $builder = new ObservabilityArtifactBuilder();
        $first = $builder->build($firstRoot);
        $second = $builder->build($secondRoot);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->artifactSha256());
        self::assertSame(ObservabilitySourceReader::PROFILE, $first->profile());
        self::assertSame('application', $first->resource()->serviceName());
        self::assertSame([256, 64, 1000], [
            $first->batchPolicy()->queueCapacity(),
            $first->batchPolicy()->maximumBatchSize(),
            $first->batchPolicy()->transportTimeoutMilliseconds(),
        ]);
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertFalse(str_ends_with($first->canonicalBytes(), "\n\n"));
        self::assertSame(
            $first->canonicalBytes(),
            (new ObservabilityArtifactValidator())->validateStructure($first->canonicalBytes())->canonicalBytes(),
        );

        $orderedRoot = $this->application(self::sourceOrderedDifferently());
        $ordered = $builder->build($orderedRoot);
        self::assertSame($first->modelSha256(), $ordered->modelSha256());
        self::assertNotSame($first->sourceSha256(), $ordered->sourceSha256());
        self::assertNotSame($first->artifactSha256(), $ordered->artifactSha256());

        $data = json_decode($first->canonicalBytes(), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsArray($data['builder']);
        $data['builder']['implementation_sha256'] = str_repeat('a', 64);
        $changedBytes = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $changed = (new ObservabilityArtifactValidator())->validateStructure($changedBytes);
        self::assertNotSame($first->artifactSha256(), $changed->artifactSha256());
        $this->expectException(StaleObservabilityArtifactException::class);
        (new ObservabilityArtifactValidator())->validateCurrent($firstRoot, $changedBytes);
    }

    public function testInspectBuildAndCheckAreWriteFreeWhereRequiredAndIdempotent(): void
    {
        $root = $this->application(self::source());
        $output = $root . '/var/build';
        $workflow = new ObservabilityBuildWorkflow();
        clearstatcache(true);
        $before = self::treeIdentity($root);
        $planned = $workflow->inspect($root, $output);
        clearstatcache(true);

        self::assertTrue($planned->publicationRequired());
        self::assertSame($before, self::treeIdentity($root));
        self::assertSame('kongono-observability-v1-' . $planned->artifactSha256() . '.json', $planned->artifactBasename());
        self::assertSame(ObservabilitySourceReader::PROFILE, $planned->profile());

        $current = $workflow->build($root, $output);
        self::assertSame($planned->artifactBasename(), $current->artifactBasename());
        self::assertSame($planned->artifactSha256(), $current->artifactSha256());
        self::assertSame($current->artifactSha256(), $workflow->build($root, $output)->artifactSha256());

        clearstatcache(true);
        $published = self::treeIdentity($root);
        $checked = $workflow->check($root, $output);
        $inspected = $workflow->inspect($root, $output);
        clearstatcache(true);
        self::assertSame($published, self::treeIdentity($root));
        self::assertSame($current->artifactSha256(), $checked->artifactSha256());
        self::assertFalse($inspected->publicationRequired());
        self::assertCount(1, self::files($output));
        self::assertSame([], self::temporaryResidue($root));
    }

    public function testStructuralValidationRemainsSeparateFromExactCurrentness(): void
    {
        $root = $this->application(self::source());
        $artifact = (new ObservabilityArtifactBuilder())->build($root);
        self::write($root . '/kongono.observability.json', self::source('changed'));
        $validator = new ObservabilityArtifactValidator();

        self::assertSame($artifact->canonicalBytes(), $validator->validateStructure($artifact->canonicalBytes())->canonicalBytes());
        $this->expectException(StaleObservabilityArtifactException::class);
        $validator->validateCurrent($root, $artifact->canonicalBytes());
    }

    public function testChangedCrossedRenamedMissingAndTamperedArtifactsFailClosedWithoutScanning(): void
    {
        $root = $this->application(self::source());
        $other = $this->application(self::source('other'));
        $output = $root . '/var/build';
        $workflow = new ObservabilityBuildWorkflow();
        $current = $workflow->build($root, $output);
        $path = $output . '/' . $current->artifactBasename();

        self::assertTrue(rename($path, $path . '.renamed'));
        $this->expectWorkflowFailure(static fn(): CurrentObservabilityConfiguration => $workflow->check($root, $output));
        self::assertTrue(rename($path . '.renamed', $path));

        $otherArtifact = (new ObservabilityArtifactBuilder())->build($other);
        self::write($path, $otherArtifact->canonicalBytes());
        $this->expectWorkflowFailure(static fn(): CurrentObservabilityConfiguration => $workflow->check($root, $output));

        self::write($path, "tampered\n");
        $before = self::treeIdentity($root);
        $this->expectWorkflowFailure(static fn(): PlannedObservabilityConfiguration => $workflow->inspect($root, $output));
        self::assertSame($before, self::treeIdentity($root));
    }

    public function testConflictingTargetIsNeverOverwritten(): void
    {
        $root = $this->application(self::source());
        $output = $root . '/var/build';
        $workflow = new ObservabilityBuildWorkflow();
        $current = $workflow->build($root, $output);
        $target = $output . '/' . $current->artifactBasename();
        self::write($target, "conflict\n");

        try {
            $workflow->build($root, $output);
            self::fail('Expected no-clobber failure.');
        } catch (ObservabilityWorkflowException) {
            self::assertSame("conflict\n", file_get_contents($target));
            self::assertSame([], self::temporaryResidue($root));
        }
    }

    /** @param callable(string): (int|false) $mutate */
    #[DataProvider('invalidSources')]
    public function testInvalidClosedSourcesFailWithoutCoercionOrDisclosure(callable $mutate): void
    {
        $root = $this->application(self::source());
        $mutate($root);
        $size = filesize($root . '/kongono.observability.json');
        self::assertIsInt($size);
        $expectedDiagnostic = $size > 32768 ? 'KGN-OBSERVABILITY-BUILD-1002' : 'KGN-OBSERVABILITY-BUILD-1003';
        try {
            (new ObservabilityArtifactBuilder())->build($root);
            self::fail('Expected invalid source failure.');
        } catch (InvalidObservabilitySourceException $failure) {
            self::assertSame($expectedDiagnostic, $failure->diagnosticId());
            self::assertStringNotContainsString('secret-resource-value', (string) $failure);
            self::assertStringNotContainsString($root, (string) $failure);
            self::assertStringNotContainsString('secret-resource-value', json_encode($failure->diagnosticContext(), JSON_THROW_ON_ERROR));
        }
    }

    /** @return iterable<string, array{callable(string): (int|false)}> */
    public static function invalidSources(): iterable
    {
        yield 'empty' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', '')];
        yield 'bom' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', "\xEF\xBB\xBF" . self::source())];
        yield 'c0' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', self::source() . "\x01")];
        yield 'c1 direct' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('application', "secret\xC2\x85resource-value", self::source()))];
        yield 'c1 escaped' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('application', 'secret\\u0085resource-value', self::source()))];
        yield 'oversize' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_repeat('x', 32769))];
        yield 'trailing data' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', self::source() . '{}')];
        yield 'duplicate top key' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"version":1', '"version":1,"version":1', self::source()))];
        yield 'duplicate nested key' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"service_name":"application"', '"service_name":"application","service_name":"other"', self::source()))];
        yield 'unknown endpoint' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"profile":', '"endpoint":"https://secret.example","profile":', self::source()))];
        yield 'profile mismatch' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace(ObservabilitySourceReader::PROFILE, 'other', self::source()))];
        yield 'float capacity' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"queue_capacity":256', '"queue_capacity":256.0', self::source()))];
        yield 'boolean timeout' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"transport_timeout_milliseconds":1000', '"transport_timeout_milliseconds":true', self::source()))];
        yield 'batch exceeds capacity' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"maximum_batch_size":64', '"maximum_batch_size":257', self::source()))];
        yield 'empty service' => [static fn(string $root): int|false => file_put_contents($root . '/kongono.observability.json', str_replace('"service_name":"application"', '"service_name":""', self::source()))];
    }

    public function testCanonicalValidatorRejectsMalformedReorderedAndC1Artifacts(): void
    {
        $artifact = (new ObservabilityArtifactBuilder())->build($this->application(self::source()));
        $validator = new ObservabilityArtifactValidator();
        $decoded = json_decode($artifact->canonicalBytes(), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $reordered = ['version' => $decoded['version'], 'schema' => $decoded['schema']] + array_diff_key($decoded, ['version' => true, 'schema' => true]);
        $invalid = [
            "{}\n",
            str_replace("\n", "\r\n", $artifact->canonicalBytes()),
            " " . $artifact->canonicalBytes(),
            json_encode($reordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
            str_replace('application', "application\xC2\x85", $artifact->canonicalBytes()),
        ];
        foreach ($invalid as $bytes) {
            try {
                $validator->validateStructure($bytes);
                self::fail('Expected artifact rejection.');
            } catch (InvalidObservabilityArtifactException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testOutputMustBeExistingExactStrictDescendantAndRejectStreamSyntax(): void
    {
        $root = $this->application(self::source());
        $workflow = new ObservabilityBuildWorkflow();
        foreach ([dirname($root), $root, $root . '/missing', $root . '/var/build:secret'] as $invalid) {
            $this->expectWorkflowFailure(static fn(): PlannedObservabilityConfiguration => $workflow->inspect($root, $invalid));
        }
        self::assertSame([], self::files($root . '/var/build'));
    }

    public function testLinkedOrJunctionOutputIsRejectedWithoutPublication(): void
    {
        $root = $this->application(self::source());
        $target = $root . '/linked-target';
        $linked = $root . '/linked-output';
        self::assertTrue(mkdir($target, 0700));
        if (!self::createDirectoryLink($target, $linked)) {
            self::markTestSkipped('Directory links are unavailable in this environment.');
        }

        try {
            $this->expectWorkflowFailure(static fn(): PlannedObservabilityConfiguration => (new ObservabilityBuildWorkflow())->inspect($root, $linked));
            self::assertSame([], self::files($target));
        } finally {
            self::removeLink($linked);
            self::assertTrue(rmdir($target));
        }
    }

    public function testDiagnosticVocabularyIsContiguousFixedAndNondisclosing(): void
    {
        $root = $this->application(self::source());
        self::write($root . '/kongono.observability.json', '{"secret-resource-value":true}');
        try {
            (new ObservabilityBuildWorkflow())->inspect($root, $root . '/var/build');
            self::fail('Expected workflow failure.');
        } catch (ObservabilityWorkflowException $failure) {
            self::assertSame('KGN-OBSERVABILITY-BUILD-1007', $failure->diagnosticId());
            self::assertStringNotContainsString('secret-resource-value', (string) $failure);
            self::assertStringNotContainsString($root, json_encode($failure->diagnosticContext(), JSON_THROW_ON_ERROR));
        }
    }

    /** @return list<string> */
    /**
     * @template T of object
     * @param ReflectionClass<T> $class
     * @return list<string>
     */
    private function declaredPublicMethods(ReflectionClass $class, bool $includeConstructor = true): array
    {
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $class->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $class->getName(),
            ),
        );
        if (!$includeConstructor) {
            $methods = array_values(array_filter($methods, static fn(string $method): bool => '__construct' !== $method));
        }
        sort($methods, SORT_STRING);

        return $methods;
    }

    private function application(string $source): string
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-observability-build-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($root . '/var/build', 0700, true));
        self::write($root . '/kongono.observability.json', $source);
        $this->roots[] = $root;

        return $root;
    }

    private static function source(string $serviceName = 'application'): string
    {
        return '{"schema":"kongono.observability","version":1,"profile":"'
            . ObservabilitySourceReader::PROFILE
            . '","resource":{"service_name":"' . $serviceName
            . '","service_version":null,"deployment_environment_name":null},"batch":{"queue_capacity":256,"maximum_batch_size":64,"transport_timeout_milliseconds":1000}}'
            . "\n";
    }

    private static function sourceOrderedDifferently(): string
    {
        return '{"batch":{"transport_timeout_milliseconds":1000,"maximum_batch_size":64,"queue_capacity":256},"resource":{"deployment_environment_name":null,"service_version":null,"service_name":"application"},"profile":"'
            . ObservabilitySourceReader::PROFILE
            . '","version":1,"schema":"kongono.observability"}' . "\n";
    }

    private function expectWorkflowFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Workflow operation must fail closed.');
        } catch (ObservabilityWorkflowException $failure) {
            self::assertSame('KGN-OBSERVABILITY-BUILD-1007', $failure->diagnosticId());
        }
    }

    /** @return list<string> */
    private static function files(string $directory): array
    {
        $entries = scandir($directory);
        self::assertIsArray($entries);
        $files = [];
        foreach ($entries as $entry) {
            if ('.' !== $entry && '..' !== $entry) {
                $files[] = $directory . '/' . $entry;
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /** @return array<string, array{bytes:int,sha256:string,mtime:int,ctime:int}> */
    private static function treeIdentity(string $root): array
    {
        $identity = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $bytes = file_get_contents($path);
            $stat = stat($path);
            self::assertIsString($bytes);
            self::assertIsArray($stat);
            $identity[substr($path, strlen($root) + 1)] = [
                'bytes' => strlen($bytes),
                'sha256' => hash('sha256', $bytes),
                'mtime' => $stat['mtime'],
                'ctime' => $stat['ctime'],
            ];
        }
        ksort($identity, SORT_STRING);

        return $identity;
    }

    /** @return list<string> */
    private static function temporaryResidue(string $root): array
    {
        $residue = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && str_contains($file->getFilename(), '.kongono-observability-')) {
                $residue[] = $file->getPathname();
            }
        }

        return $residue;
    }

    private static function createDirectoryLink(string $target, string $link): bool
    {
        if ('Windows' !== PHP_OS_FAMILY) {
            return symlink($target, $link);
        }

        $command = "New-Item -ItemType Junction -Path '"
            . str_replace("'", "''", $link)
            . "' -Target '"
            . str_replace("'", "''", $target)
            . "' | Out-Null";
        $pipes = [];
        $process = proc_open(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', $command], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        if (!is_resource($process)) {
            return false;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return 0 === proc_close($process) && is_dir($link);
    }

    private static function removeLink(string $link): void
    {
        if ('Windows' === PHP_OS_FAMILY) {
            self::assertTrue(rmdir($link));

            return;
        }
        self::assertTrue(unlink($link));
    }

    private static function write(string $path, string $bytes): void
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
    }

    private static function removeTree(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $entries = scandir($root);
        self::assertIsArray($entries);
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $root . '/' . $entry;
            if (is_dir($path) && !is_link($path)) {
                self::removeTree($path);
            } else {
                self::assertTrue(unlink($path));
            }
        }
        self::assertTrue(rmdir($root));
    }
}
