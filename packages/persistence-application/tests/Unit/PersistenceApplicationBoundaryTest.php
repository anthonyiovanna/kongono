<?php

declare(strict_types=1);

namespace Kongono\PersistenceApplication\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceApplication\Diagnostic\PersistenceApplicationDiagnostic;
use Kongono\PersistenceApplication\Exception\ExceptionInterface;
use Kongono\PersistenceApplication\Exception\PersistenceApplicationWorkflowException;
use Kongono\PersistenceApplication\Workflow\CurrentPersistenceApplicationBuild;
use Kongono\PersistenceApplication\Workflow\PersistenceApplicationBuildWorkflow;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class PersistenceApplicationBoundaryTest extends TestCase
{
    public function testPublicBoundaryIsExperimentalAndDiagnosticIsClosed(): void
    {
        foreach ([PersistenceApplicationBuildWorkflow::class, CurrentPersistenceApplicationBuild::class, PersistenceApplicationWorkflowException::class] as $class) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        }
        self::assertSame(['KGN-PERSISTENCE-APPLICATION-1001'], array_column(PersistenceApplicationDiagnostic::cases(), 'value'));
    }

    public function testFailurePresentationRetainsCauseWithoutDisclosingIt(): void
    {
        $previous = new RuntimeException('dsn=secret sql=secret parameter=secret');
        $failure = new PersistenceApplicationWorkflowException('persistence_load', $previous);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame($previous, $failure->getPrevious());
        self::assertSame('KGN-PERSISTENCE-APPLICATION-1001', $failure->diagnosticId());
        self::assertStringNotContainsString('secret', (string) $failure);
        self::assertStringNotContainsString('secret', $failure->getMessage());
    }

    public function testWorkflowSourcePreservesCorrectedLoadOrder(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Workflow/PersistenceApplicationBuildWorkflow.php');
        $prepare = strpos($source, 'prepareBuild(');
        $persistence = strpos($source, 'new GeneratedPersistenceLoader()');
        $data = strpos($source, 'loadPrepared(');
        $composition = strpos($source, 'new ApplicationBuildWorkflow()');
        self::assertIsInt($prepare);
        self::assertIsInt($persistence);
        self::assertIsInt($data);
        self::assertIsInt($composition);
        self::assertLessThan($persistence, $prepare);
        self::assertLessThan($data, $persistence);
        self::assertLessThan($composition, $data);
        self::assertSame(2, substr_count($source, 'new ApplicationBuildWorkflow()'));
    }
}
