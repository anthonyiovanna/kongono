<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Unit;

use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Composition\Build\CompositionArtifactPublisher;
use Kongono\Composition\Build\CompositionArtifactValidator;
use Kongono\Composition\Exception\CompositionLifecycleException;
use Kongono\Composition\Exception\CompositionPublicationException;
use Kongono\Composition\Exception\GeneratedWiringLoadException;
use Kongono\Composition\Exception\GeneratedWiringPublicationException;
use Kongono\Composition\Exception\InvalidCompositionArtifactException;
use Kongono\Composition\Exception\InvalidCompositionSourceException;
use Kongono\Composition\Exception\InvalidGeneratedWiringException;
use Kongono\Composition\Exception\RetiredCompositionBoundaryException;
use Kongono\Composition\Exception\StaleCompositionArtifactException;
use Kongono\Composition\Generated\GeneratedWiringArtifact;
use Kongono\Composition\Generated\GeneratedWiringGenerator;
use Kongono\Composition\Generated\GeneratedWiringPublisher;
use Kongono\Composition\Generated\GeneratedWiringValidator;
use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Composition\Tests\Fixture\ApplicationService;
use Kongono\Composition\Tests\Fixture\ConfigInput;
use Kongono\Composition\Tests\Fixture\DefaultLogger;
use Kongono\Composition\Tests\Fixture\HandlerCollection;
use Kongono\Composition\Tests\Fixture\LifecycleRecorder;
use Kongono\Composition\Tests\Fixture\OperationService;
use Kongono\Composition\Tests\Fixture\TrackedApplicationRoot;
use Kongono\Composition\Tests\Fixture\TrackedOperationRoot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * @phpstan-type SourceService array{class: string, lifetime: string, arguments: list<array<string, mixed>>, cleanup: string|null}
 * @phpstan-type Source array{
 *     schema: string,
 *     version: int,
 *     inputs: list<array{contract: string}>,
 *     services: list<SourceService>,
 *     bindings: list<array{contract: string, service: string}>,
 *     roots: list<array{kind: string, contract: string}>
 * }
 * @phpstan-type SourceProvenance array{path: string, bytes: int, sha256: string}
 * @phpstan-type ArtifactSource array{path: string, bytes: int, sha256: string}
 */
final class CompositionArtifactTest extends TestCase
{
    private static string $applicationRoot;
    private static string $outputDirectory;

    public static function setUpBeforeClass(): void
    {
        self::$applicationRoot = self::temporaryDirectory('application');
        self::$outputDirectory = self::temporaryDirectory('output');
        self::makeDirectory(self::$applicationRoot . '/src');
        foreach ([
            'ConfigInput.php', 'Logger.php', 'Handler.php', 'OperationDependency.php',
            'EmptyApplication.php', 'EmptyOperation.php', 'DefaultLogger.php',
            'HandlerA.php', 'HandlerB.php', 'HandlerCollection.php', 'ApplicationService.php', 'OperationService.php',
            'OperationDependencyService.php', 'ApplicationDependsOnOperation.php',
            'CycleA.php', 'CycleB.php', 'InvalidCleanup.php', 'ScalarConstructor.php',
            'SecondOperation.php',
            'LifecycleRecorder.php', 'TrackedApplicationLeaf.php', 'TrackedApplicationRoot.php',
            'TrackedOperationLeaf.php', 'TrackedTransient.php', 'TrackedOperationRoot.php',
            'FailingApplicationRoot.php', 'FailingOperationRoot.php',
        ] as $fixtureName) {
            $fixture = dirname(__DIR__) . '/Fixture/' . $fixtureName;
            $target = self::$applicationRoot . '/src/' . $fixtureName;
            self::assertTrue(copy($fixture, $target));
            require $target;
        }
        self::writeFile(self::$applicationRoot . '/composer.json', "{\"name\":\"example/application\"}\n");
        self::writeFile(self::$applicationRoot . '/composer.lock', "{\"packages\":[]}\n");
        self::writeSource(self::validSource());
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTree(self::$applicationRoot);
        self::removeTree(self::$outputDirectory);
    }

    protected function tearDown(): void
    {
        self::writeFile(self::$applicationRoot . '/composer.json', "{\"name\":\"example/application\"}\n");
        self::writeFile(self::$applicationRoot . '/composer.lock', "{\"packages\":[]}\n");
        self::writeSource(self::validSource());
        foreach (self::matchedFiles(self::$outputDirectory . '/*') as $path) {
            if (is_file($path) || is_link($path)) {
                self::assertTrue(unlink($path));
            }
        }
    }

    public function testExactBuildValidationAndPublicationAreDeterministic(): void
    {
        $builder = new CompositionArtifactBuilder();
        $first = $builder->build(self::$applicationRoot);
        $second = $builder->build(self::$applicationRoot);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertSame($first->modelSha256(), $second->modelSha256());
        self::assertSame($first->sourceSha256(), $second->sourceSha256());
        self::assertSame($first->sourceSetSha256(), $second->sourceSetSha256());
        self::assertSame($first->builderImplementationSha256(), $second->builderImplementationSha256());

        $validator = new CompositionArtifactValidator();
        self::assertSame($first->canonicalBytes(), $validator->validateStructure($first->canonicalBytes())->canonicalBytes());
        self::assertSame($first->canonicalBytes(), $validator->validateCurrent(self::$applicationRoot, $first->canonicalBytes())->canonicalBytes());

        $publisher = new CompositionArtifactPublisher();
        $path = $publisher->publish($first, self::$outputDirectory);
        self::assertSame($path, $publisher->publish($second, self::$outputDirectory));
        self::assertSame($first->canonicalBytes(), file_get_contents($path));
        self::assertStringEndsWith('kongono-composition-v1-' . $first->artifactSha256() . '.json', $path);
        self::assertCount(1, self::matchedFiles(self::$outputDirectory . '/*'));
    }

    public function testSemanticOrderInvariancePreservesRawProvenanceDifference(): void
    {
        $builder = new CompositionArtifactBuilder();
        $sourceA = self::validSource();
        self::writeSource($sourceA);
        $artifactA = $builder->build(self::$applicationRoot);
        $decodedA = self::decode($artifactA->canonicalBytes());

        $sourceB = $sourceA;
        $sourceB['services'] = array_reverse($sourceB['services']);
        self::writeSource($sourceB);
        $artifactB = $builder->build(self::$applicationRoot);
        $decodedB = self::decode($artifactB->canonicalBytes());

        self::assertSame(self::canonicalModelBytes($decodedA['model']), self::canonicalModelBytes($decodedB['model']));
        self::assertSame($artifactA->modelSha256(), $artifactB->modelSha256());
        self::assertNotSame($artifactA->sourceSha256(), $artifactB->sourceSha256());
        self::assertNotSame($artifactA->canonicalBytes(), $artifactB->canonicalBytes());
        self::assertNotSame($artifactA->artifactSha256(), $artifactB->artifactSha256());
        $provenanceA = self::sourceProvenance($decodedA);
        $provenanceB = self::sourceProvenance($decodedB);
        self::assertSame(strlen(self::sourceBytes($sourceA)), $provenanceA['bytes']);
        self::assertSame(hash('sha256', self::sourceBytes($sourceA)), $provenanceA['sha256']);
        self::assertSame(strlen(self::sourceBytes($sourceB)), $provenanceB['bytes']);
        self::assertSame(hash('sha256', self::sourceBytes($sourceB)), $provenanceB['sha256']);

        $validator = new CompositionArtifactValidator();
        self::assertSame($artifactB->canonicalBytes(), $validator->validateCurrent(self::$applicationRoot, $artifactB->canonicalBytes())->canonicalBytes());
        try {
            $validator->validateCurrent(self::$applicationRoot, $artifactA->canonicalBytes());
            self::fail('The artifact for the other raw source must be stale.');
        } catch (StaleCompositionArtifactException $exception) {
            self::assertSame('KGN-COMPOSITION-1011', $exception->diagnosticId());
        }

        self::writeSource($sourceA);
        self::expectException(StaleCompositionArtifactException::class);
        $validator->validateCurrent(self::$applicationRoot, $artifactB->canonicalBytes());
    }

    /** @return iterable<string, array{string}> */
    public static function malformedSourceProvider(): iterable
    {
        yield 'BOM' => ["\xEF\xBB\xBF{}\n"];
        yield 'CR' => ["{}\r\n"];
        yield 'NUL' => ["{\0}\n"];
        yield 'missing final LF' => ['{}'];
        yield 'multiple final LF' => ["{}\n\n"];
        yield 'invalid UTF-8' => ["{\"x\":\"\xFF\"}\n"];
        yield 'duplicate nested member' => ["{\"schema\":\"kongono.composition\",\"version\":1,\"inputs\":[{\"contract\":\"A\",\"contract\":\"B\"}],\"services\":[],\"bindings\":[],\"roots\":[]}\n"];
        yield 'unknown top-level member' => ["{\"schema\":\"kongono.composition\",\"version\":1,\"inputs\":[],\"services\":[],\"bindings\":[],\"roots\":[],\"unknown\":true}\n"];
    }

    #[DataProvider('malformedSourceProvider')]
    public function testMalformedSourceBytesAndJsonFailWithoutRetainedState(string $bytes): void
    {
        self::writeFile(self::$applicationRoot . '/kongono.composition.json', $bytes);
        try {
            (new CompositionArtifactBuilder())->build(self::$applicationRoot);
            self::fail('Malformed source must fail.');
        } catch (InvalidCompositionSourceException $exception) {
            self::assertStringStartsWith('KGN-COMPOSITION-', $exception->diagnosticId());
            self::assertStringNotContainsString($bytes, (string) $exception);
        }

        self::writeSource(self::validSource());
        self::assertNotSame('', (new CompositionArtifactBuilder())->build(self::$applicationRoot)->canonicalBytes());
    }

    public function testLimitsGraphLifetimeAndCleanupFailuresRecover(): void
    {
        $tooManyInputs = self::validSource();
        $tooManyInputs['inputs'] = [];
        for ($index = 0; $index <= 256; ++$index) {
            $tooManyInputs['inputs'][] = ['contract' => 'Example\\Input' . $index];
        }
        self::writeSource($tooManyInputs);
        $this->assertSourceFailure('KGN-COMPOSITION-1006');

        $invalidLifetime = self::validSource();
        $invalidService = $invalidLifetime['services'][0];
        $invalidService['lifetime'] = 'invalid';
        $invalidLifetime['services'][0] = $invalidService;
        self::writeSource($invalidLifetime);
        $this->assertSourceFailure('KGN-COMPOSITION-1007');

        $invalidGraph = self::validSource();
        $invalidGraph['services'][] = [
            'class' => 'Kongono\\Composition\\Tests\\Fixture\\OperationDependencyService',
            'lifetime' => 'operation',
            'arguments' => [],
            'cleanup' => null,
        ];
        $invalidGraph['services'][] = [
            'class' => 'Kongono\\Composition\\Tests\\Fixture\\ApplicationDependsOnOperation',
            'lifetime' => 'application',
            'arguments' => [[
                'parameter' => 'dependency',
                'service' => 'Kongono\\Composition\\Tests\\Fixture\\OperationDependency',
            ]],
            'cleanup' => null,
        ];
        $invalidGraph['bindings'][] = [
            'contract' => 'Kongono\\Composition\\Tests\\Fixture\\OperationDependency',
            'service' => 'Kongono\\Composition\\Tests\\Fixture\\OperationDependencyService',
        ];
        $invalidGraph['roots'][] = [
            'kind' => 'application',
            'contract' => 'Kongono\\Composition\\Tests\\Fixture\\ApplicationDependsOnOperation',
        ];
        self::writeSource($invalidGraph);
        $this->assertSourceFailure('KGN-COMPOSITION-1008');

        self::writeSource(self::validSource());
        self::assertNotSame('', (new CompositionArtifactBuilder())->build(self::$applicationRoot)->canonicalBytes());
    }

    public function testTamperingReorderingAndStaleProjectStateFailClosed(): void
    {
        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $decoded = self::decode($artifact->canonicalBytes());
        $decoded['model_sha256'] = str_repeat('0', 64);
        $tampered = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->expectException(InvalidCompositionArtifactException::class);
        (new CompositionArtifactValidator())->validateStructure($tampered);
    }

    public function testChangedComposerStateMakesArtifactStaleAndHealthyBuildFollows(): void
    {
        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        self::writeFile(self::$applicationRoot . '/composer.lock', "{\"packages\":[\"changed\"]}\n");
        try {
            (new CompositionArtifactValidator())->validateCurrent(self::$applicationRoot, $artifact->canonicalBytes());
            self::fail('Changed project state must be stale.');
        } catch (StaleCompositionArtifactException $exception) {
            self::assertSame('KGN-COMPOSITION-1011', $exception->diagnosticId());
        }
        $current = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        self::assertNotSame($artifact->artifactSha256(), $current->artifactSha256());
    }

    public function testPublicationConflictNeverClobbersExistingTarget(): void
    {
        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $target = self::$outputDirectory . '/kongono-composition-v1-' . $artifact->artifactSha256() . '.json';
        self::writeFile($target, "conflict\n");
        try {
            (new CompositionArtifactPublisher())->publish($artifact, self::$outputDirectory);
            self::fail('Conflicting target must fail.');
        } catch (CompositionPublicationException $exception) {
            self::assertSame('KGN-COMPOSITION-1012', $exception->diagnosticId());
            self::assertSame("conflict\n", file_get_contents($target));
        }
        self::assertCount(1, self::matchedFiles(self::$outputDirectory . '/*'));
    }

    public function testEmptyDependencyApplicationAndOperationRootsAreAdmitted(): void
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';
        $source = self::validSource();
        $source['inputs'] = [];
        $source['services'] = [
            ['class' => $namespace . 'EmptyApplication', 'lifetime' => 'application', 'arguments' => [], 'cleanup' => null],
            ['class' => $namespace . 'EmptyOperation', 'lifetime' => 'operation', 'arguments' => [], 'cleanup' => null],
        ];
        $source['bindings'] = [];
        $source['roots'] = [
            ['kind' => 'application', 'contract' => $namespace . 'EmptyApplication'],
            ['kind' => 'operation', 'contract' => $namespace . 'EmptyOperation'],
        ];
        self::writeSource($source);

        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        self::assertSame($artifact->canonicalBytes(), (new CompositionArtifactValidator())->validateCurrent(self::$applicationRoot, $artifact->canonicalBytes())->canonicalBytes());
    }

    public function testSupersededServicesListFailsInSourceAndDetachedArtifact(): void
    {
        $builder = new CompositionArtifactBuilder();
        $source = self::validSource();
        $services = $source['services'];
        $application = $services[4];
        $arguments = $application['arguments'];
        $arguments[2] = [
            'parameter' => 'handlers',
            'services' => [
                'Kongono\\Composition\\Tests\\Fixture\\HandlerA',
                'Kongono\\Composition\\Tests\\Fixture\\HandlerB',
            ],
        ];
        $application['arguments'] = $arguments;
        $services[4] = $application;
        $source['services'] = $services;
        self::writeSource($source);
        $this->assertSourceFailure('KGN-COMPOSITION-1007');

        self::writeSource(self::validSource());
        $artifact = $builder->build(self::$applicationRoot);
        $decoded = self::decode($artifact->canonicalBytes());
        $model = $decoded['model'] ?? null;
        self::assertIsArray($model);
        $modelServices = $model['services'] ?? null;
        self::assertIsArray($modelServices);
        $modelService = $modelServices[0] ?? null;
        self::assertIsArray($modelService);
        $modelArguments = $modelService['arguments'] ?? null;
        self::assertIsArray($modelArguments);
        $modelArguments[2] = $arguments[2];
        $modelService['arguments'] = $modelArguments;
        $modelServices[0] = $modelService;
        $model['services'] = $modelServices;
        $decoded['model'] = $model;
        $decoded['model_sha256'] = hash('sha256', self::canonicalModelBytes($model));
        $this->assertArtifactFailure(self::encoded($decoded));
    }

    public function testSourceByteLimitAcceptsMaximumAndRejectsMaximumPlusOne(): void
    {
        $payload = substr(self::sourceBytes(self::validSource()), 0, -1);
        $maximum = $payload . str_repeat(' ', 1048576 - strlen($payload) - 1) . "\n";
        self::assertSame(1048576, strlen($maximum));
        self::writeFile(self::$applicationRoot . '/kongono.composition.json', $maximum);
        self::assertNotSame('', (new CompositionArtifactBuilder())->build(self::$applicationRoot)->canonicalBytes());

        self::writeFile(self::$applicationRoot . '/kongono.composition.json', substr($maximum, 0, -1) . " \n");
        $this->assertSourceFailure('KGN-COMPOSITION-1006');
    }

    public function testDeclarationGraphCycleCleanupAndConstructorFailuresRecover(): void
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';

        $missingBinding = self::validSource();
        $missingBinding['bindings'] = [];
        self::writeSource($missingBinding);
        $this->assertSourceFailure('KGN-COMPOSITION-1008');

        $duplicateBinding = self::validSource();
        $duplicateBinding['bindings'][] = $duplicateBinding['bindings'][0];
        self::writeSource($duplicateBinding);
        $this->assertSourceFailure('KGN-COMPOSITION-1007');

        $scalarConstructor = self::validSource();
        $scalarConstructor['services'][] = [
            'class' => $namespace . 'ScalarConstructor',
            'lifetime' => 'transient',
            'arguments' => [['parameter' => 'value', 'service' => $namespace . 'Logger']],
            'cleanup' => null,
        ];
        self::writeSource($scalarConstructor);
        $this->assertSourceFailure('KGN-COMPOSITION-1007');

        $invalidCleanup = self::validSource();
        $invalidCleanup['services'][] = ['class' => $namespace . 'InvalidCleanup', 'lifetime' => 'application', 'arguments' => [], 'cleanup' => 'close'];
        $invalidCleanup['roots'][] = ['kind' => 'application', 'contract' => $namespace . 'InvalidCleanup'];
        self::writeSource($invalidCleanup);
        $this->assertSourceFailure('KGN-COMPOSITION-1007');

        $cycle = self::validSource();
        $cycle['services'][] = ['class' => $namespace . 'CycleA', 'lifetime' => 'application', 'arguments' => [['parameter' => 'dependency', 'service' => $namespace . 'CycleB']], 'cleanup' => null];
        $cycle['services'][] = ['class' => $namespace . 'CycleB', 'lifetime' => 'application', 'arguments' => [['parameter' => 'dependency', 'service' => $namespace . 'CycleA']], 'cleanup' => null];
        $cycle['roots'][] = ['kind' => 'application', 'contract' => $namespace . 'CycleA'];
        self::writeSource($cycle);
        $this->assertSourceFailure('KGN-COMPOSITION-1008');

        self::writeSource(self::validSource());
        self::assertNotSame('', (new CompositionArtifactBuilder())->build(self::$applicationRoot)->canonicalBytes());
    }

    public function testFirstDeclarationFailureIsIndependentOfServiceOrder(): void
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';
        $source = self::validSource();
        $source['services'][] = ['class' => $namespace . 'InvalidCleanup', 'lifetime' => 'application', 'arguments' => [], 'cleanup' => 'close'];
        $source['services'][] = ['class' => $namespace . 'ScalarConstructor', 'lifetime' => 'transient', 'arguments' => [['parameter' => 'value', 'service' => $namespace . 'Logger']], 'cleanup' => null];
        self::writeSource($source);
        $first = $this->captureSourceFailure();
        $source['services'] = array_reverse($source['services']);
        self::writeSource($source);
        $second = $this->captureSourceFailure();

        self::assertSame($first->diagnosticId(), $second->diagnosticId());
        self::assertSame($first->diagnosticContext()->fields()[0]->value(), $second->diagnosticContext()->fields()[0]->value());
    }

    public function testArtifactPartialReorderedMalformedAndOversizedFormsFailClosed(): void
    {
        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $decoded = self::decode($artifact->canonicalBytes());

        $partial = $decoded;
        unset($partial['builder']);
        $this->assertArtifactFailure(self::encoded($partial));

        $reordered = [
            'version' => $decoded['version'],
            'schema' => $decoded['schema'],
            'source' => $decoded['source'],
            'project' => $decoded['project'],
            'builder' => $decoded['builder'],
            'symbol_state' => $decoded['symbol_state'],
            'model_sha256' => $decoded['model_sha256'],
            'model' => $decoded['model'],
        ];
        $this->assertArtifactFailure(self::encoded($reordered));
        $this->assertArtifactFailure("{}\n");
        $this->assertArtifactFailure(str_repeat(' ', 4194304) . "\n");

        self::assertSame($artifact->canonicalBytes(), (new CompositionArtifactValidator())->validateStructure($artifact->canonicalBytes())->canonicalBytes());
    }

    public function testSourceSetOrderingAndBuilderImplementationIdentityAreExact(): void
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';
        $source = self::validSource();
        $source['services'][] = ['class' => $namespace . 'SecondOperation', 'lifetime' => 'operation', 'arguments' => [], 'cleanup' => null];
        $source['roots'][] = ['kind' => 'operation', 'contract' => $namespace . 'SecondOperation'];
        self::writeSource($source);
        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $decoded = self::decode($artifact->canonicalBytes());
        $sources = self::artifactSources($decoded);
        $sourcePaths = array_column($sources, 'path');
        $sortedPaths = $sourcePaths;
        sort($sortedPaths, SORT_STRING);
        self::assertSame($sortedPaths, $sourcePaths);
        self::assertContains('src/SecondOperation.php', $sourcePaths);
        self::assertCount(10, $sourcePaths);
        $records = array_map(static fn(array $record): string => implode("\t", [$record['path'], (string) $record['bytes'], $record['sha256']]), $sources);
        self::assertSame(hash('sha256', implode("\n", $records)), $artifact->sourceSetSha256());

        $implementationPaths = [
            'src/Build/CompositionArtifact.php', 'src/Build/CompositionArtifactBuilder.php', 'src/Build/CompositionArtifactPublisher.php', 'src/Build/CompositionArtifactValidator.php',
            'src/Diagnostic/CompositionDiagnostic.php', 'src/Exception/CompositionException.php', 'src/Exception/CompositionLifecycleException.php', 'src/Exception/CompositionPublicationException.php',
            'src/Exception/ExceptionInterface.php', 'src/Exception/GeneratedWiringPublicationException.php', 'src/Exception/InvalidCompositionArtifactException.php',
            'src/Exception/InvalidCompositionSourceException.php', 'src/Exception/InvalidGeneratedWiringException.php', 'src/Exception/RetiredCompositionBoundaryException.php',
            'src/Exception/StaleCompositionArtifactException.php', 'src/Generated/GeneratedWiringArtifact.php', 'src/Generated/GeneratedWiringGenerator.php',
            'src/Generated/GeneratedWiringPublisher.php', 'src/Generated/GeneratedWiringValidator.php', 'src/Internal/CanonicalJson.php',
            'src/Internal/CompositionModelCompiler.php', 'src/Internal/GeneratedWiringEmitter.php', 'src/Internal/GeneratedWiringImplementation.php',
            'src/Internal/PathGuard.php', 'src/Internal/StrictJsonDecoder.php', 'src/Runtime/GeneratedApplicationBoundary.php',
            'src/Runtime/GeneratedApplicationState.php', 'src/Runtime/GeneratedOperationBoundary.php', 'src/Runtime/LifecycleFailureComposer.php',
        ];
        $implementationRecords = [];
        $packageRoot = dirname(__DIR__, 2);
        foreach ($implementationPaths as $path) {
            $bytes = file_get_contents($packageRoot . '/' . $path);
            self::assertIsString($bytes);
            $implementationRecords[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }
        self::assertSame(hash('sha256', implode("\n", $implementationRecords)), $artifact->builderImplementationSha256());
    }

    public function testUnsafeRootOutputAndOptionalSourceLinkFailWithoutMutation(): void
    {
        $sourcePath = self::$applicationRoot . '/kongono.composition.json';
        $sourceBytes = file_get_contents($sourcePath);
        self::assertIsString($sourceBytes);
        try {
            (new CompositionArtifactBuilder())->build(self::$applicationRoot . '/missing');
            self::fail('A missing root must fail.');
        } catch (InvalidCompositionSourceException $exception) {
            self::assertSame('KGN-COMPOSITION-1001', $exception->diagnosticId());
        }

        $artifact = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        try {
            (new CompositionArtifactPublisher())->publish($artifact, self::$outputDirectory . '/missing');
            self::fail('A missing output directory must fail.');
        } catch (CompositionPublicationException $exception) {
            self::assertSame('KGN-COMPOSITION-1012', $exception->diagnosticId());
        }
        self::assertSame($sourceBytes, file_get_contents($sourcePath));
        self::assertSame([], self::matchedFiles(self::$outputDirectory . '/*'));

        $ordinary = self::$applicationRoot . '/source-link-target.json';
        self::assertTrue(rename($sourcePath, $ordinary));
        if (@symlink($ordinary, $sourcePath)) {
            $this->assertSourceFailure('KGN-COMPOSITION-1002');
            self::assertTrue(unlink($sourcePath));
        }
        self::assertTrue(rename($ordinary, $sourcePath));
    }

    public function testExceptionStringAndContextDoNotDiscloseRejectedValues(): void
    {
        $sentinel = 'SECRET-SOURCE-' . bin2hex(random_bytes(8));
        self::writeFile(self::$applicationRoot . '/kongono.composition.json', "{\"$sentinel\":true}\n");
        try {
            (new CompositionArtifactBuilder())->build(self::$applicationRoot);
            self::fail('Rejected source must fail.');
        } catch (InvalidCompositionSourceException $exception) {
            self::assertStringNotContainsString($sentinel, (string) $exception);
            self::assertStringNotContainsString($sentinel, json_encode($exception->diagnosticContext(), JSON_THROW_ON_ERROR));
            self::assertSame('KGN-COMPOSITION-1005', $exception->diagnosticId());
        }
    }

    public function testGeneratedWiringIsDeterministicBoundAndPublishedWithoutClobber(): void
    {
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $generator = new GeneratedWiringGenerator();
        $first = $generator->generate(self::$applicationRoot, $composition->canonicalBytes());
        $second = $generator->generate(self::$applicationRoot, $composition->canonicalBytes());

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->wiringSha256(), $second->wiringSha256());
        self::assertSame($composition->artifactSha256(), $first->compositionArtifactSha256());
        self::assertSame($composition->modelSha256(), $first->modelSha256());
        self::assertSame('KongonoGenerated\\Composition\\V1\\A' . $composition->artifactSha256(), $first->generatedNamespace());
        self::assertSame($first->generatedNamespace() . '\\ApplicationAssembly', $first->applicationClass());
        self::assertSame($first->generatedNamespace() . '\\OperationAssembly', $first->operationClass());
        foreach (['Reflection', 'DependencyResolver', 'class_exists', 'interface_exists', 'method_exists', 'call_user_func', 'eval(', 'services:', 'iterable ', 'array $handlers', '...$'] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $first->canonicalBytes());
        }
        self::assertNotEmpty(token_get_all($first->canonicalBytes(), TOKEN_PARSE));
        self::assertStringContainsString('public static function start(', $first->canonicalBytes());
        self::assertStringContainsString('ConfigInput $input1', $first->canonicalBytes());
        self::assertStringContainsString('public function applicationRoot1(): \\Kongono\\Composition\\Tests\\Fixture\\ApplicationService', $first->canonicalBytes());
        self::assertStringContainsString('public function operationRoot1(): \\Kongono\\Composition\\Tests\\Fixture\\OperationService', $first->canonicalBytes());
        self::assertStringContainsString('$applicationService3 = new \\Kongono\\Composition\\Tests\\Fixture\\HandlerCollection(', $first->canonicalBytes());
        self::assertStringContainsString('first: $transient1,', $first->canonicalBytes());
        self::assertStringContainsString('second: $transient2,', $first->canonicalBytes());
        self::assertStringContainsString('static function () use ($applicationService1): void {', $first->canonicalBytes());
        self::assertStringContainsString('$applicationService1->shutdown();', $first->canonicalBytes());
        self::assertStringContainsString('static function () use ($applicationService2): void {', $first->canonicalBytes());
        self::assertStringContainsString('$applicationService2->close();', $first->canonicalBytes());

        $validator = new GeneratedWiringValidator();
        self::assertSame($first->canonicalBytes(), $validator->validateStructure($first->canonicalBytes())->canonicalBytes());
        self::assertSame($first->canonicalBytes(), $validator->validateCurrent(self::$applicationRoot, $composition->canonicalBytes(), $first->canonicalBytes())->canonicalBytes());
        $tampered = str_replace($composition->artifactSha256(), str_repeat('0', 64), $first->canonicalBytes());
        try {
            $validator->validateCurrent(self::$applicationRoot, $composition->canonicalBytes(), $tampered);
            self::fail('Tampered generated metadata must fail current validation.');
        } catch (InvalidGeneratedWiringException $exception) {
            self::assertSame('KGN-COMPOSITION-1013', $exception->diagnosticId());
        }

        $publisher = new GeneratedWiringPublisher();
        $path = $publisher->publish($first, self::$outputDirectory);
        self::assertSame($path, $publisher->publish($second, self::$outputDirectory));
        self::assertSame($first->canonicalBytes(), file_get_contents($path));
        self::assertStringEndsWith('kongono-wiring-v1-' . $first->wiringSha256() . '.php', $path);
        self::assertTrue(unlink($path));
        self::writeFile($path, "conflict\n");
        try {
            $publisher->publish($first, self::$outputDirectory);
            self::fail('A generated-wiring publication conflict must fail.');
        } catch (GeneratedWiringPublicationException $exception) {
            self::assertSame('KGN-COMPOSITION-1014', $exception->diagnosticId());
            self::assertSame("conflict\n", file_get_contents($path));
        }
    }

    public function testGeneratedWiringAllowsExplicitApplicationGraphTypeWithoutWeakeningForbiddenConstructs(): void
    {
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $generated = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $bytes = str_replace(
            '\\Kongono\\Composition\\Tests\\Fixture\\ConfigInput',
            '\\Kongono\\ApplicationGraph\\Model\\ApplicationGraph',
            $generated->canonicalBytes(),
        );
        self::assertNotSame($generated->canonicalBytes(), $bytes);
        self::assertStringContainsString('\\Kongono\\ApplicationGraph\\Model\\ApplicationGraph $input1', $bytes);
        self::assertSame($bytes, (new GeneratedWiringValidator())->validateStructure($bytes)->canonicalBytes());

        foreach (['ReflectionClass', 'DependencyResolver', 'class_exists', 'eval('] as $forbidden) {
            try {
                (new GeneratedWiringValidator())->validateStructure(str_replace(
                    'public static function start(',
                    $forbidden . "\n    public static function start(",
                    $bytes,
                ));
                self::fail('A genuine discovery, service-location, or dynamic-execution construct must remain forbidden.');
            } catch (InvalidGeneratedWiringException $exception) {
                self::assertSame('KGN-COMPOSITION-1013', $exception->diagnosticId());
                self::assertSame('forbidden_construct', $exception->diagnosticContext()->fields()[0]->value());
            }
        }
    }

    public function testEmptyOperationEmissionOmitsUnusedMachineryAndRetainsLeaseLifecycle(): void
    {
        $source = self::validSource();
        $source['services'] = array_values(array_filter(
            $source['services'],
            static fn(array $service): bool => 'operation' !== $service['lifetime'],
        ));
        $source['roots'] = array_values(array_filter(
            $source['roots'],
            static fn(array $root): bool => 'application' === $root['kind'],
        ));
        self::writeSource($source);

        $builder = new CompositionArtifactBuilder();
        $firstComposition = $builder->build(self::$applicationRoot);
        $secondComposition = $builder->build(self::$applicationRoot);
        $generator = new GeneratedWiringGenerator();
        $first = $generator->generate(self::$applicationRoot, $firstComposition->canonicalBytes());
        $second = $generator->generate(self::$applicationRoot, $secondComposition->canonicalBytes());
        self::assertSame($firstComposition->canonicalBytes(), $secondComposition->canonicalBytes());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());

        $operationEnd = strpos($first->canonicalBytes(), "\nfinal class ApplicationAssembly");
        self::assertIsInt($operationEnd);
        $operationBytes = substr($first->canonicalBytes(), 0, $operationEnd);
        self::assertStringNotContainsString('$construct = function (): void', $operationBytes);
        self::assertStringNotContainsString('$construct();', $operationBytes);
        self::assertStringNotContainsString('private function assertOpen(): void', $operationBytes);
        self::assertStringNotContainsString('public function operationRoot', $operationBytes);
        self::assertGeneratedPhpPassesExternalGates($first);

        $application = self::startGenerated($first, new class implements ConfigInput {});
        $operation = $application->beginOperation();
        try {
            $application->shutdown();
            self::fail('The acquired empty-operation lease must block shutdown until close.');
        } catch (RetiredCompositionBoundaryException $exception) {
            self::assertSame('KGN-COMPOSITION-1016', $exception->diagnosticId());
            self::assertFalse($application->isRetired());
        }
        self::assertFalse($operation->isClosed());
        $operation->close();
        self::assertTrue($operation->isClosed());
        $operation->close();
        self::assertTrue($operation->isClosed());
        $application->shutdown();
        self::assertTrue($application->isRetired());
    }

    public function testExactRawArtifactIdentityChangesGeneratedNamespaceForEqualModel(): void
    {
        $builder = new CompositionArtifactBuilder();
        $generator = new GeneratedWiringGenerator();
        $source = self::validSource();
        self::writeSource($source);
        $compositionA = $builder->build(self::$applicationRoot);
        $wiringA = $generator->generate(self::$applicationRoot, $compositionA->canonicalBytes());

        $source['services'] = array_reverse($source['services']);
        self::writeSource($source);
        $compositionB = $builder->build(self::$applicationRoot);
        $wiringB = $generator->generate(self::$applicationRoot, $compositionB->canonicalBytes());

        self::assertSame($compositionA->modelSha256(), $compositionB->modelSha256());
        self::assertNotSame($compositionA->artifactSha256(), $compositionB->artifactSha256());
        self::assertNotSame($wiringA->generatedNamespace(), $wiringB->generatedNamespace());
        self::assertNotSame($wiringA->canonicalBytes(), $wiringB->canonicalBytes());
        self::assertNotSame($wiringA->wiringSha256(), $wiringB->wiringSha256());
    }

    public function testGeneratedOperationRetainsOnlyRootsBehindTypedConstructionBoundaries(): void
    {
        self::writeSource(self::lifecycleSource(false, false));
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $generator = new GeneratedWiringGenerator();
        $first = $generator->generate(self::$applicationRoot, $composition->canonicalBytes());
        $second = $generator->generate(self::$applicationRoot, $composition->canonicalBytes());
        $bytes = $first->canonicalBytes();

        self::assertSame($first->wiringSha256(), $second->wiringSha256());
        self::assertSame($bytes, $second->canonicalBytes());
        self::assertStringContainsString('private \\Kongono\\Composition\\Tests\\Fixture\\TrackedOperationRoot $operationService2;', $bytes);
        self::assertStringNotContainsString('private \\Kongono\\Composition\\Tests\\Fixture\\TrackedOperationLeaf $operationService1;', $bytes);
        self::assertStringContainsString('$operationService1 = new \\Kongono\\Composition\\Tests\\Fixture\\TrackedOperationLeaf(', $bytes);
        self::assertStringContainsString('static function () use ($operationService1): void {', $bytes);
        self::assertStringContainsString('$this->operationService2 = $operationService2;', $bytes);
        self::assertStringContainsString('$construct = function (): void {', $bytes);
        self::assertStringContainsString('            $construct();', $bytes);
        self::assertStringContainsString('        } catch (Throwable $primaryFailure) {', $bytes);
        self::assertStringContainsString("LifecycleFailureComposer::throwIfNeeded('operation-construction', \$primaryFailure, \$cleanupFailures);", $bytes);
        self::assertStringContainsString('    private function assertOpen(): void', $bytes);
        self::assertStringContainsString('$construct = static function () use ($assembly, $input1): void {', $bytes);
        self::assertSame(
            $bytes,
            (new GeneratedWiringValidator())->validateCurrent(
                self::$applicationRoot,
                $composition->canonicalBytes(),
                $bytes,
            )->canonicalBytes(),
        );
        self::assertGeneratedPhpPassesExternalGates($first);

        $recorder = new LifecycleRecorder();
        $application = self::startGenerated($first, $recorder);
        $operation = $application->beginOperation();
        $root = self::invokeRoot($first->operationClass(), 'operationRoot1', $operation);
        self::assertInstanceOf(TrackedOperationRoot::class, $root);
        [, $firstLeaf, $secondLeaf] = $root->dependencies();
        self::assertSame($firstLeaf, $secondLeaf);
        $operation->close();
        self::assertSame(1, substr_count($recorder->eventLog(), 'operation-leaf'));
        $application->shutdown();
    }

    public function testGeneratedTypedAggregateAndLifetimeSharingAreExact(): void
    {
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $sentinel = 'SECRET-INPUT-' . bin2hex(random_bytes(8));
        $config = new class ($sentinel) implements ConfigInput {
            public function __construct(public readonly string $secret) {}
        };
        self::assertStringNotContainsString($sentinel, $wiring->canonicalBytes());
        $application = self::startGenerated($wiring, $config);
        $applicationRoot = self::invokeRoot($wiring->applicationClass(), 'applicationRoot1', $application);
        self::assertInstanceOf(ApplicationService::class, $applicationRoot);
        [$actualConfig, $logger, $handlers, $fallback] = $applicationRoot->dependencies();
        self::assertSame($config, $actualConfig);
        self::assertInstanceOf(DefaultLogger::class, $fallback);
        self::assertInstanceOf(HandlerCollection::class, $handlers);
        self::assertSame('HandlerA', (new ReflectionClass($handlers->first()))->getShortName());
        self::assertSame('HandlerB', (new ReflectionClass($handlers->second()))->getShortName());
        self::assertNotSame($handlers->first(), $handlers->second());

        $operationA = $application->beginOperation();
        $operationB = $application->beginOperation();
        $operationRootA = self::invokeRoot($wiring->operationClass(), 'operationRoot1', $operationA);
        $operationRootB = self::invokeRoot($wiring->operationClass(), 'operationRoot1', $operationB);
        self::assertInstanceOf(OperationService::class, $operationRootA);
        self::assertInstanceOf(OperationService::class, $operationRootB);
        self::assertSame($logger, $operationRootA->logger());
        self::assertSame($logger, $operationRootB->logger());
        self::assertNotSame($operationRootA, $operationRootB);
        $operationA->close();
        $operationB->close();
        try {
            self::invokeRoot($wiring->operationClass(), 'operationRoot1', $operationA);
            self::fail('A closed operation must reject root access.');
        } catch (RetiredCompositionBoundaryException $exception) {
            self::assertSame('KGN-COMPOSITION-1016', $exception->diagnosticId());
            self::assertStringNotContainsString($sentinel, (string) $exception);
        }
        foreach ((new ReflectionClass($application))->getProperties() as $property) {
            $value = $property->getValue($application);
            self::assertNotSame($operationA, $value);
            self::assertNotSame($operationB, $value);
            self::assertNotSame($operationRootA, $value);
            self::assertNotSame($operationRootB, $value);
        }
        $operationC = $application->beginOperation();
        $operationRootC = self::invokeRoot($wiring->operationClass(), 'operationRoot1', $operationC);
        self::assertNotSame($operationRootA, $operationRootC);
        $operationC->close();
        $application->shutdown();
    }

    public function testGeneratedLifecycleLeasesCleanupOrderAndFailureCompositionAreExact(): void
    {
        self::writeSource(self::lifecycleSource(false, false));
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $recorder = new LifecycleRecorder();
        $application = self::startGenerated($wiring, $recorder);
        $applicationRoot = self::invokeRoot($wiring->applicationClass(), 'applicationRoot1', $application);
        self::assertInstanceOf(TrackedApplicationRoot::class, $applicationRoot);
        $operation = $application->beginOperation();
        $operationRoot = self::invokeRoot($wiring->operationClass(), 'operationRoot1', $operation);
        self::assertInstanceOf(TrackedOperationRoot::class, $operationRoot);
        [$sharedApplication, $firstLeaf, $secondLeaf, $firstTransient, $secondTransient] = $operationRoot->dependencies();
        self::assertSame($applicationRoot, $sharedApplication);
        self::assertSame($firstLeaf, $secondLeaf);
        self::assertNotSame($firstTransient, $secondTransient);

        try {
            $application->shutdown();
            self::fail('An active operation lease must block shutdown.');
        } catch (RetiredCompositionBoundaryException $exception) {
            self::assertSame('KGN-COMPOSITION-1016', $exception->diagnosticId());
            self::assertFalse($application->isRetired());
            self::assertSame('', $recorder->eventLog());
        }

        $operation->close();
        $eventsAfterClose = $recorder->eventLog();
        self::assertMatchesRegularExpression('/\Aoperation-root\ntransient-[0-9]+\ntransient-[0-9]+\noperation-leaf\z/D', $eventsAfterClose);
        self::assertDoesNotMatchRegularExpression('/transient-([0-9]+)\ntransient-\1/D', $eventsAfterClose);
        $operation->close();
        self::assertSame($eventsAfterClose, $recorder->eventLog());
        self::assertTrue($operation->isClosed());
        $application->shutdown();
        self::assertStringEndsWith("application-root\napplication-leaf", $recorder->eventLog());
        $afterShutdown = $recorder->eventLog();
        $application->shutdown();
        self::assertSame($afterShutdown, $recorder->eventLog());
        self::assertTrue($application->isRetired());
        $this->expectException(RetiredCompositionBoundaryException::class);
        self::invokeRoot($wiring->applicationClass(), 'applicationRoot1', $application);
    }

    public function testGeneratedCleanupFailuresRetainExactPrimaryAndOrder(): void
    {
        self::writeSource(self::lifecycleSource(false, false));
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $firstCleanup = new RuntimeException('first cleanup secret');
        $secondCleanup = new RuntimeException('second cleanup secret');
        $recorder = new LifecycleRecorder();
        $recorder->failCleanup('operation-root', $firstCleanup);
        $recorder->failCleanup('operation-leaf', $secondCleanup);
        $application = self::startGenerated($wiring, $recorder);
        $operation = $application->beginOperation();
        try {
            $operation->close();
            self::fail('Multiple cleanup failures must be combined.');
        } catch (CompositionLifecycleException $exception) {
            self::assertSame('KGN-COMPOSITION-1015', $exception->diagnosticId());
            self::assertSame('operation', $exception->boundary());
            self::assertSame($firstCleanup, $exception->primaryFailure());
            self::assertSame($firstCleanup, $exception->getPrevious());
            self::assertSame([$firstCleanup, $secondCleanup], $exception->cleanupFailures());
            self::assertStringNotContainsString('secret', (string) $exception);
        }
        self::assertTrue($operation->isClosed());
        $eventsAfterFailedClose = $recorder->eventLog();
        $operation->close();
        self::assertSame($eventsAfterFailedClose, $recorder->eventLog());

        $primary = new RuntimeException('primary secret');
        $applicationCleanup = new RuntimeException('application cleanup secret');
        $recorder->failCleanup('application-root', $applicationCleanup);
        try {
            $application->shutdown($primary);
            self::fail('Primary plus cleanup failure must be combined.');
        } catch (CompositionLifecycleException $exception) {
            self::assertSame('application', $exception->boundary());
            self::assertSame($primary, $exception->primaryFailure());
            self::assertSame([$applicationCleanup], $exception->cleanupFailures());
            self::assertSame($primary, $exception->getPrevious());
        }
        self::assertTrue($application->isRetired());
        $eventsAfterFailedShutdown = $recorder->eventLog();
        $application->shutdown();
        self::assertSame($eventsAfterFailedShutdown, $recorder->eventLog());
        try {
            $application->beginOperation();
            self::fail('A retired application must reject new operations.');
        } catch (RetiredCompositionBoundaryException $exception) {
            self::assertSame('KGN-COMPOSITION-1016', $exception->diagnosticId());
        }
    }

    public function testGeneratedExactPrimaryAndSingleCleanupFailureAreRethrown(): void
    {
        self::writeSource(self::lifecycleSource(false, false));
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $recorder = new LifecycleRecorder();
        $application = self::startGenerated($wiring, $recorder);
        $operation = $application->beginOperation();
        $primary = new RuntimeException('primary only');
        try {
            $operation->close($primary);
            self::fail('The exact primary failure must escape.');
        } catch (Throwable $failure) {
            self::assertSame($primary, $failure);
        }

        $cleanup = new RuntimeException('cleanup only');
        $recorder->failCleanup('application-root', $cleanup);
        try {
            $application->shutdown();
            self::fail('The exact single cleanup failure must escape.');
        } catch (Throwable $failure) {
            self::assertSame($cleanup, $failure);
        }
        self::assertTrue($application->isRetired());
    }

    public function testGeneratedValidationRejectsStaleMalformedAndMixedEvidence(): void
    {
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $validator = new GeneratedWiringValidator();
        $bytes = $wiring->canonicalBytes();

        foreach ([
            "<?php\n",
            str_repeat('x', GeneratedWiringGenerator::MAX_GENERATED_BYTES + 1),
            str_replace('final class OperationAssembly', 'final class MixedOperationAssembly', $bytes),
            str_replace($wiring->generatorImplementationSha256(), str_repeat('0', 64), $bytes),
        ] as $invalidBytes) {
            try {
                $validator->validateStructure($invalidBytes);
                self::fail('Invalid generated evidence must fail structural validation.');
            } catch (InvalidGeneratedWiringException $exception) {
                self::assertSame('KGN-COMPOSITION-1013', $exception->diagnosticId());
            }
        }

        $metadataStart = strpos($bytes, '// {');
        self::assertIsInt($metadataStart);
        $lineEnd = strpos($bytes, "\n", $metadataStart);
        self::assertIsInt($lineEnd);
        $metadataLine = substr($bytes, $metadataStart + 3, $lineEnd - $metadataStart - 3);
        $metadata = self::decode($metadataLine);
        $reordered = ['version' => $metadata['version'], 'schema' => $metadata['schema']] + $metadata;
        $reorderedBytes = str_replace('// ' . $metadataLine, '// ' . json_encode($reordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $bytes);
        try {
            $validator->validateStructure($reorderedBytes);
            self::fail('Reordered generated metadata must fail.');
        } catch (InvalidGeneratedWiringException $exception) {
            self::assertSame('KGN-COMPOSITION-1013', $exception->diagnosticId());
        }

        self::writeFile(self::$applicationRoot . '/composer.lock', "{\"packages\":[{\"name\":\"changed\"}]}\n");
        try {
            $validator->validateCurrent(self::$applicationRoot, $composition->canonicalBytes(), $bytes);
            self::fail('Stale Composition evidence must fail generated current validation.');
        } catch (StaleCompositionArtifactException $exception) {
            self::assertSame('KGN-COMPOSITION-1011', $exception->diagnosticId());
        }
    }

    public function testGeneratedPartialConstructionCleansAndReleasesLease(): void
    {
        $primary = new RuntimeException('construction primary');
        self::writeSource(self::lifecycleSource(false, true));
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $recorder = new LifecycleRecorder();
        $recorder->failConstruction($primary);
        $application = self::startGenerated($wiring, $recorder);
        try {
            $application->beginOperation();
            self::fail('Operation construction must fail.');
        } catch (Throwable $failure) {
            self::assertSame($primary, $failure);
            self::assertSame('operation-leaf', $recorder->eventLog());
        }
        $application->shutdown();
        self::assertTrue($application->isRetired());

        self::writeSource(self::lifecycleSource(true, false));
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $recorder = new LifecycleRecorder();
        $recorder->failConstruction($primary);
        try {
            self::startGenerated($wiring, $recorder);
            self::fail('Application construction must fail.');
        } catch (Throwable $failure) {
            self::assertSame($primary, $failure);
            self::assertSame('application-leaf', $recorder->eventLog());
        }
    }

    public function testExactCurrentGeneratedFileLoadsAndStartsOnlyOnce(): void
    {
        self::writeFile(self::$applicationRoot . '/composer.lock', "{\"packages\":[],\"phase5f\":true}\n");
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $correctPath = self::$applicationRoot . '/kongono-wiring-v1-' . $wiring->wiringSha256() . '.php';
        $wrongPath = self::$applicationRoot . '/wrong-name.php';
        self::writeFile($correctPath, $wiring->canonicalBytes());
        self::writeFile($wrongPath, $wiring->canonicalBytes());

        try {
            try {
                (new GeneratedWiringLoader())->loadCurrent(self::$applicationRoot, $composition->canonicalBytes(), $wrongPath);
                self::fail('A wrong generated basename must fail before require.');
            } catch (GeneratedWiringLoadException $exception) {
                self::assertSame('KGN-COMPOSITION-1017', $exception->diagnosticId());
                self::assertFalse(class_exists($wiring->applicationClass(), false));
            }

            $loaded = (new GeneratedWiringLoader())->loadCurrent(
                self::$applicationRoot,
                $composition->canonicalBytes(),
                $correctPath,
            );
            self::assertSame($wiring->wiringSha256(), $loaded->wiringSha256());
            self::assertSame($wiring->applicationClass(), $loaded->applicationClass());
            self::assertSame($wiring->operationClass(), $loaded->operationClass());
            self::assertFalse($loaded->isStarted());

            try {
                $loaded->start(new \stdClass());
                self::fail('Wrong input type must fail before generated start.');
            } catch (GeneratedWiringLoadException $exception) {
                self::assertSame('input_type', $exception->diagnosticContext()->fields()[0]->value());
            }
            self::assertFalse($loaded->isStarted());

            $boundary = $loaded->start(new class implements ConfigInput {});
            self::assertTrue($loaded->isStarted());
            self::assertInstanceOf(GeneratedApplicationBoundary::class, $boundary);
            try {
                (new GeneratedWiringLoader())->loadCurrent(self::$applicationRoot, $composition->canonicalBytes(), $correctPath);
                self::fail('A pre-existing exact generated class must fail before another require.');
            } catch (GeneratedWiringLoadException $exception) {
                self::assertSame('class_collision', $exception->diagnosticContext()->fields()[0]->value());
                self::assertStringNotContainsString($correctPath, (string) $exception);
            }
            try {
                $loaded->start(new class implements ConfigInput {});
                self::fail('A loaded assembly must start at most once.');
            } catch (GeneratedWiringLoadException $exception) {
                self::assertSame('wiring_already_started', $exception->diagnosticContext()->fields()[0]->value());
            }
            $boundary->shutdown();
        } finally {
            foreach ([$correctPath, $wrongPath] as $path) {
                if (is_file($path)) {
                    self::assertTrue(unlink($path));
                }
            }
        }
    }

    public function testGeneratedLoaderRejectsUnsafeAndNonCurrentEvidenceBeforeExecution(): void
    {
        self::writeFile(self::$applicationRoot . '/composer.lock', "{\"packages\":[],\"phase5f\":\"invalid\"}\n");
        $composition = (new CompositionArtifactBuilder())->build(self::$applicationRoot);
        $wiring = (new GeneratedWiringGenerator())->generate(self::$applicationRoot, $composition->canonicalBytes());
        $path = self::$applicationRoot . '/kongono-wiring-v1-' . $wiring->wiringSha256() . '.php';
        $loader = new GeneratedWiringLoader();

        foreach ([
            self::$applicationRoot . '/missing.php',
            self::$outputDirectory . '/outside.php',
            'data://text/plain,<?php',
        ] as $unsafePath) {
            try {
                $loader->loadCurrent(self::$applicationRoot, $composition->canonicalBytes(), $unsafePath);
                self::fail('Unsafe generated paths must fail closed.');
            } catch (GeneratedWiringLoadException $exception) {
                self::assertSame('KGN-COMPOSITION-1017', $exception->diagnosticId());
                self::assertStringNotContainsString($unsafePath, (string) $exception);
            }
        }

        try {
            foreach ([
                substr($wiring->canonicalBytes(), 0, -16),
                str_replace('final class OperationAssembly', 'final class TamperedOperationAssembly', $wiring->canonicalBytes()),
                str_repeat('x', GeneratedWiringGenerator::MAX_GENERATED_BYTES + 1),
            ] as $invalidBytes) {
                self::writeFile($path, $invalidBytes);
                try {
                    $loader->loadCurrent(self::$applicationRoot, $composition->canonicalBytes(), $path);
                    self::fail('Non-current generated bytes must fail before execution.');
                } catch (GeneratedWiringLoadException $exception) {
                    self::assertSame('KGN-COMPOSITION-1017', $exception->diagnosticId());
                    self::assertFalse(class_exists($wiring->applicationClass(), false));
                }
            }
        } finally {
            if (is_file($path)) {
                self::assertTrue(unlink($path));
            }
        }
    }

    private function assertSourceFailure(string $diagnosticId): void
    {
        try {
            (new CompositionArtifactBuilder())->build(self::$applicationRoot);
            self::fail('Invalid source must fail.');
        } catch (InvalidCompositionSourceException $exception) {
            self::assertSame($diagnosticId, $exception->diagnosticId());
        }
    }

    private function captureSourceFailure(): InvalidCompositionSourceException
    {
        try {
            (new CompositionArtifactBuilder())->build(self::$applicationRoot);
            self::fail('Invalid source must fail.');
        } catch (InvalidCompositionSourceException $exception) {
            return $exception;
        }
    }

    private function assertArtifactFailure(string $bytes): void
    {
        try {
            (new CompositionArtifactValidator())->validateStructure($bytes);
            self::fail('Invalid artifact must fail.');
        } catch (InvalidCompositionArtifactException $exception) {
            self::assertSame('KGN-COMPOSITION-1010', $exception->diagnosticId());
        }
    }

    /** @return Source */
    private static function validSource(): array
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';

        return [
            'schema' => 'kongono.composition',
            'version' => 1,
            'inputs' => [['contract' => $namespace . 'ConfigInput']],
            'services' => [
                ['class' => $namespace . 'DefaultLogger', 'lifetime' => 'application', 'arguments' => [], 'cleanup' => 'close'],
                ['class' => $namespace . 'HandlerA', 'lifetime' => 'transient', 'arguments' => [['parameter' => 'logger', 'service' => $namespace . 'Logger']], 'cleanup' => null],
                ['class' => $namespace . 'HandlerB', 'lifetime' => 'transient', 'arguments' => [['parameter' => 'logger', 'service' => $namespace . 'Logger']], 'cleanup' => null],
                [
                    'class' => $namespace . 'HandlerCollection',
                    'lifetime' => 'application',
                    'arguments' => [
                        ['parameter' => 'first', 'service' => $namespace . 'HandlerA'],
                        ['parameter' => 'second', 'service' => $namespace . 'HandlerB'],
                    ],
                    'cleanup' => null,
                ],
                [
                    'class' => $namespace . 'ApplicationService',
                    'lifetime' => 'application',
                    'arguments' => [
                        ['parameter' => 'config', 'input' => $namespace . 'ConfigInput'],
                        ['parameter' => 'logger', 'service' => $namespace . 'Logger'],
                        ['parameter' => 'handlers', 'service' => $namespace . 'Handler'],
                        ['parameter' => 'fallback', 'default' => true],
                    ],
                    'cleanup' => 'shutdown',
                ],
                ['class' => $namespace . 'OperationService', 'lifetime' => 'operation', 'arguments' => [['parameter' => 'logger', 'service' => $namespace . 'Logger']], 'cleanup' => null],
            ],
            'bindings' => [
                ['contract' => $namespace . 'Handler', 'service' => $namespace . 'HandlerCollection'],
                ['contract' => $namespace . 'Logger', 'service' => $namespace . 'DefaultLogger'],
            ],
            'roots' => [
                ['kind' => 'application', 'contract' => $namespace . 'ApplicationService'],
                ['kind' => 'operation', 'contract' => $namespace . 'OperationService'],
            ],
        ];
    }

    /** @return Source */
    private static function lifecycleSource(bool $failingApplication, bool $failingOperation): array
    {
        $namespace = 'Kongono\\Composition\\Tests\\Fixture\\';
        $applicationRoot = $failingApplication ? $namespace . 'FailingApplicationRoot' : $namespace . 'TrackedApplicationRoot';
        $operationRoot = $failingOperation ? $namespace . 'FailingOperationRoot' : $namespace . 'TrackedOperationRoot';

        return [
            'schema' => 'kongono.composition',
            'version' => 1,
            'inputs' => [['contract' => $namespace . 'LifecycleRecorder']],
            'services' => [
                [
                    'class' => $namespace . 'TrackedApplicationLeaf',
                    'lifetime' => 'application',
                    'arguments' => [['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder']],
                    'cleanup' => 'close',
                ],
                [
                    'class' => $namespace . 'TrackedApplicationRoot',
                    'lifetime' => 'application',
                    'arguments' => [
                        ['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder'],
                        ['parameter' => 'leaf', 'service' => $namespace . 'TrackedApplicationLeaf'],
                    ],
                    'cleanup' => 'close',
                ],
                [
                    'class' => $namespace . 'FailingApplicationRoot',
                    'lifetime' => 'application',
                    'arguments' => [
                        ['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder'],
                        ['parameter' => 'leaf', 'service' => $namespace . 'TrackedApplicationLeaf'],
                    ],
                    'cleanup' => null,
                ],
                [
                    'class' => $namespace . 'TrackedOperationLeaf',
                    'lifetime' => 'operation',
                    'arguments' => [['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder']],
                    'cleanup' => 'close',
                ],
                [
                    'class' => $namespace . 'TrackedTransient',
                    'lifetime' => 'transient',
                    'arguments' => [['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder']],
                    'cleanup' => 'close',
                ],
                [
                    'class' => $namespace . 'TrackedOperationRoot',
                    'lifetime' => 'operation',
                    'arguments' => [
                        ['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder'],
                        ['parameter' => 'application', 'service' => $namespace . 'TrackedApplicationRoot'],
                        ['parameter' => 'firstOperationLeaf', 'service' => $namespace . 'TrackedOperationLeaf'],
                        ['parameter' => 'secondOperationLeaf', 'service' => $namespace . 'TrackedOperationLeaf'],
                        ['parameter' => 'firstTransient', 'service' => $namespace . 'TrackedTransient'],
                        ['parameter' => 'secondTransient', 'service' => $namespace . 'TrackedTransient'],
                    ],
                    'cleanup' => 'close',
                ],
                [
                    'class' => $namespace . 'FailingOperationRoot',
                    'lifetime' => 'operation',
                    'arguments' => [
                        ['parameter' => 'recorder', 'input' => $namespace . 'LifecycleRecorder'],
                        ['parameter' => 'leaf', 'service' => $namespace . 'TrackedOperationLeaf'],
                    ],
                    'cleanup' => null,
                ],
            ],
            'bindings' => [],
            'roots' => [
                ['kind' => 'application', 'contract' => $applicationRoot],
                ['kind' => 'operation', 'contract' => $operationRoot],
            ],
        ];
    }

    private static function startGenerated(GeneratedWiringArtifact $artifact, object ...$inputs): GeneratedApplicationBoundary
    {
        self::loadGenerated($artifact);
        $reflection = new ReflectionClass(self::loadedClass($artifact->applicationClass()));
        $application = $reflection->getMethod('start')->invoke(null, ...$inputs);
        self::assertInstanceOf(GeneratedApplicationBoundary::class, $application);

        return $application;
    }

    private static function loadGenerated(GeneratedWiringArtifact $artifact): void
    {
        if (class_exists($artifact->applicationClass(), false)) {
            return;
        }
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-wiring-load-' . bin2hex(random_bytes(12)) . '.php';
        self::writeFile($path, $artifact->canonicalBytes());
        try {
            require $path;
        } finally {
            self::assertTrue(unlink($path));
        }
    }

    private static function invokeRoot(string $class, string $method, object $boundary): mixed
    {
        return (new ReflectionClass(self::loadedClass($class)))->getMethod($method)->invoke($boundary);
    }

    private static function assertGeneratedPhpPassesExternalGates(GeneratedWiringArtifact $artifact): void
    {
        $temporaryRoot = self::temporaryDirectory('generated-analysis');
        $generatedPath = $temporaryRoot . '/kongono-wiring-v1-' . $artifact->wiringSha256() . '.php';
        self::writeFile($generatedPath, $artifact->canonicalBytes());
        $repository = str_replace('\\', '/', dirname(__DIR__, 4));

        try {
            foreach ([
                'lint' => [PHP_BINARY, '-l', $generatedPath],
                'phpstan' => [
                    PHP_BINARY,
                    $repository . '/vendor/bin/phpstan',
                    'analyse',
                    '--configuration=' . $repository . '/phpstan.neon.dist',
                    '--level=max',
                    '--no-progress',
                    '--debug',
                    '--autoload-file=' . $repository . '/vendor/autoload.php',
                    $generatedPath,
                ],
                'style' => [
                    PHP_BINARY,
                    $repository . '/vendor/bin/php-cs-fixer',
                    'fix',
                    '--config=' . $repository . '/.php-cs-fixer.dist.php',
                    '--dry-run',
                    '--diff',
                    '--using-cache=no',
                    $generatedPath,
                ],
            ] as $gate => $command) {
                $result = self::runProcess($command, $temporaryRoot);
                self::assertSame(0, $result['exit'], $gate . " failed:\n" . $result['stdout'] . $result['stderr']);
            }
        } finally {
            self::removeTree($temporaryRoot);
        }
        self::assertDirectoryDoesNotExist($temporaryRoot);
    }

    /**
     * @param list<string> $command
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private static function runProcess(array $command, string $workingDirectory): array
    {
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $workingDirectory);
        if (!is_resource($process)) {
            throw new RuntimeException('Generated-output validation process could not start.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if (!is_string($stdout) || !is_string($stderr)) {
            throw new RuntimeException('Generated-output validation process output was unavailable.');
        }

        return ['exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    /** @return class-string */
    private static function loadedClass(string $class): string
    {
        if (!class_exists($class)) {
            throw new RuntimeException('Expected generated class is unavailable.');
        }

        return $class;
    }

    /** @param Source $source */
    private static function writeSource(array $source): void
    {
        self::writeFile(self::$applicationRoot . '/kongono.composition.json', self::sourceBytes($source));
    }

    /** @param Source $source */
    private static function sourceBytes(array $source): string
    {
        return json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    /** @return array<mixed> */
    private static function decode(string $bytes): array
    {
        $value = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($value);

        return $value;
    }

    /** @param array<mixed> $value */
    private static function encoded(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    /**
     * @param array<mixed> $artifact
     * @return list<ArtifactSource>
     */
    private static function artifactSources(array $artifact): array
    {
        $symbolState = $artifact['symbol_state'] ?? null;
        self::assertIsArray($symbolState);
        $records = $symbolState['sources'] ?? null;
        self::assertIsArray($records);
        $sources = [];
        foreach ($records as $record) {
            self::assertIsArray($record);
            $path = $record['path'] ?? null;
            $bytes = $record['bytes'] ?? null;
            $sha256 = $record['sha256'] ?? null;
            self::assertIsString($path);
            self::assertIsInt($bytes);
            self::assertIsString($sha256);
            $sources[] = ['path' => $path, 'bytes' => $bytes, 'sha256' => $sha256];
        }

        return $sources;
    }

    /**
     * @param array<mixed> $artifact
     * @return SourceProvenance
     */
    private static function sourceProvenance(array $artifact): array
    {
        $source = $artifact['source'] ?? null;
        self::assertIsArray($source);
        $path = $source['path'] ?? null;
        $bytes = $source['bytes'] ?? null;
        $sha256 = $source['sha256'] ?? null;
        self::assertIsString($path);
        self::assertIsInt($bytes);
        self::assertIsString($sha256);

        return ['path' => $path, 'bytes' => $bytes, 'sha256' => $sha256];
    }

    private static function canonicalModelBytes(mixed $model): string
    {
        self::assertIsArray($model);

        return json_encode($model, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    /** @return list<string> */
    private static function matchedFiles(string $pattern): array
    {
        $paths = glob($pattern);

        return false === $paths ? [] : $paths;
    }

    private static function temporaryDirectory(string $purpose): string
    {
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-composition-' . $purpose . '-' . bin2hex(random_bytes(12));
        self::makeDirectory($path);

        return $path;
    }

    private static function makeDirectory(string $path): void
    {
        self::assertTrue(mkdir($path, 0700, true));
    }

    private static function writeFile(string $path, string $bytes): void
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
    }

    private static function removeTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $entries = scandir($directory);
        self::assertIsArray($entries);
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_dir($path) && !is_link($path)) {
                self::removeTree($path);
            } else {
                self::assertTrue(unlink($path));
            }
        }
        self::assertTrue(rmdir($directory));
    }
}
