<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateValidator;
use Kongono\PersistenceBuild\Internal\GeneratedPersistenceStateEmitter;
use Kongono\PersistenceBuild\Internal\PersistenceGenerationImplementation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeneratedPersistenceStateValidatorTest extends TestCase
{
    private const SOURCE = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';

    public function testStructuralAndCurrentValidationAreSeparateSuccessfulProofs(): void
    {
        $root = dirname(__DIR__, 5);
        $persistence = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $generated = (new GeneratedPersistenceStateGenerator())->generate($root, self::SOURCE, $persistence->canonicalBytes());
        $validator = new GeneratedPersistenceStateValidator();

        self::assertSame($generated->canonicalBytes(), $validator->validateStructure($generated->canonicalBytes())->canonicalBytes());
        self::assertSame(
            $generated->canonicalBytes(),
            $validator->validateCurrent($root, self::SOURCE, $persistence->canonicalBytes(), $generated->canonicalBytes())->canonicalBytes(),
        );
    }

    public function testStructurallyValidButDifferentGeneratedEvidenceIsStale(): void
    {
        $root = dirname(__DIR__, 5);
        $persistence = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $stale = (new GeneratedPersistenceStateEmitter())->emit(
            $persistence->model(),
            str_repeat('0', 64),
            $persistence->modelSha256(),
            $persistence->builderImplementationSha256(),
            PersistenceGenerationImplementation::generatorSha256(),
        );
        $validator = new GeneratedPersistenceStateValidator();
        self::assertSame($stale, $validator->validateStructure($stale)->canonicalBytes());

        try {
            $validator->validateCurrent($root, self::SOURCE, $persistence->canonicalBytes(), $stale);
            self::fail('Expected stale generated evidence.');
        } catch (InvalidGeneratedPersistenceStateException $failure) {
            self::assertSame('KGN-PERSISTENCE-BUILD-1013', $failure->diagnosticId());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function malformedBytes(): iterable
    {
        yield 'empty' => [''];
        yield 'bom' => ["\xEF\xBB\xBF<?php\n"];
        yield 'crlf' => ["<?php\r\n"];
        yield 'nul' => ["<?php\0\n"];
        yield 'invalid utf8' => ["<?php\n\xFF\n"];
        yield 'missing terminal lf' => ['<?php'];
        yield 'excess terminal lf' => ["<?php\n\n"];
    }

    #[DataProvider('malformedBytes')]
    public function testMalformedByteShapesFailClosed(string $bytes): void
    {
        $this->expectException(InvalidGeneratedPersistenceStateException::class);
        (new GeneratedPersistenceStateValidator())->validateStructure($bytes);
    }

    /** @return iterable<string, array{string}> */
    public static function maliciousInsertions(): iterable
    {
        yield 'extra class' => ["\nfinal class Attacker {}\n"];
        yield 'extra method' => ["\nfunction attacker(): void {}\n"];
        yield 'eval' => ["\neval('1');\n"];
        yield 'include' => ["\ninclude 'private.php';\n"];
        yield 'require' => ["\nrequire 'private.php';\n"];
        yield 'reflection' => ["\nnew \\ReflectionClass('private');\n"];
        yield 'unserialize' => ["\nunserialize('private');\n"];
        yield 'global' => ["\nglobal \$private;\n"];
        yield 'environment' => ["\ngetenv('PRIVATE_TOKEN');\n"];
        yield 'process' => ["\nexec('private');\n"];
        yield 'database' => ["\npg_connect('private');\n"];
        yield 'exit' => ["\nexit;\n"];
        yield 'sleep' => ["\nsleep(1);\n"];
        yield 'unlink' => ["\nunlink('private');\n"];
        yield 'allowed call at top level' => ["\ncount([]);\n"];
    }

    #[DataProvider('maliciousInsertions')]
    public function testAttackerTokensAreRejectedWithoutExecutingInput(string $insertion): void
    {
        $root = dirname(__DIR__, 5);
        $persistence = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $generated = (new GeneratedPersistenceStateGenerator())->generate($root, self::SOURCE, $persistence->canonicalBytes());
        $marker = sys_get_temp_dir() . '/kongono-generated-validator-must-not-execute';
        if (file_exists($marker)) {
            self::assertTrue(unlink($marker));
        }
        $tampered = substr($generated->canonicalBytes(), 0, -1) . $insertion;

        try {
            (new GeneratedPersistenceStateValidator())->validateStructure($tampered);
            self::fail('Expected malicious generated input rejection.');
        } catch (InvalidGeneratedPersistenceStateException) {
            self::assertFileDoesNotExist($marker);
        }
    }

    public function testTopLevelWriteProbeIsRejectedWithoutExecution(): void
    {
        $root = dirname(__DIR__, 5);
        $persistence = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $generated = (new GeneratedPersistenceStateGenerator())->generate($root, self::SOURCE, $persistence->canonicalBytes());
        $marker = sys_get_temp_dir() . '/kongono-generated-validator-write-probe-' . bin2hex(random_bytes(8));
        $insertion = "\nfile_put_contents(" . var_export($marker, true) . ", 'executed');\n";
        $tampered = substr($generated->canonicalBytes(), 0, -1) . $insertion;

        try {
            (new GeneratedPersistenceStateValidator())->validateStructure($tampered);
            self::fail('Expected top-level write probe rejection.');
        } catch (InvalidGeneratedPersistenceStateException) {
            self::assertFileDoesNotExist($marker);
        }
    }

    public function testAuthenticatedGrammarMutationsFailClosed(): void
    {
        $root = dirname(__DIR__, 5);
        $persistence = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $generated = (new GeneratedPersistenceStateGenerator())->generate($root, self::SOURCE, $persistence->canonicalBytes());
        $bytes = $generated->canonicalBytes();
        $mutations = [
            'header identity' => str_replace(
                '"persistence_generator_implementation_sha256":"' . $generated->persistenceGeneratorImplementationSha256() . '"',
                '"persistence_generator_implementation_sha256":"' . str_repeat('0', 64) . '"',
                $bytes,
            ),
            'namespace' => str_replace('namespace ' . $generated->generatedNamespace() . ';', 'namespace Unexpected\\Generated;', $bytes),
            'class map' => preg_replace('/"state_adapter":"[^"]+EntityState_0001"/D', '"state_adapter":"Unexpected\\\\State"', $bytes, 1),
            'removed method' => str_replace('public static function deletion(', 'private static function deletion(', $bytes),
            'changed type' => preg_replace('/\): ChangeSet/', '): mixed', $bytes, 1),
            'non-final class' => preg_replace('/final class EntityState_0001/', 'class EntityState_0001', $bytes, 1),
        ];

        foreach ($mutations as $label => $mutated) {
            self::assertIsString($mutated, $label);
            self::assertNotSame($bytes, $mutated, $label);
            try {
                (new GeneratedPersistenceStateValidator())->validateStructure($mutated);
                self::fail('Expected structural rejection for ' . $label . '.');
            } catch (InvalidGeneratedPersistenceStateException $failure) {
                self::assertSame('KGN-PERSISTENCE-BUILD-1012', $failure->diagnosticId(), $label);
            }
        }
    }
}
