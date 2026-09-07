<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Unit\Workflow;

use Kongono\ModelInvocationApplication\Exception\ModelInvocationApplicationWorkflowException;
use Kongono\ModelInvocationApplication\Tests\Support\ModelInvocationApplicationFactory;
use Kongono\ModelInvocationApplication\Workflow\ModelInvocationApplicationBuildWorkflow;
use PHPUnit\Framework\TestCase;

final class ModelInvocationApplicationBuildWorkflowTest extends TestCase
{
    public function testBuildSnapshotsPublishesLoadsAndReturnsCurrentEvidence(): void
    {
        [$root, $output, $artifact, $source] = self::fixture();
        $build = (new ModelInvocationApplicationBuildWorkflow())->build($root, $artifact, $source, $output);

        self::assertFileExists($build->generatedWiringPath());
        self::assertSame(hash('sha256', $build->generatedWiringBytes()), $build->generatedWiringSha256());
        self::assertSame($build->profile()->identity()->value(), $build->generatedWiring()->applicationProfileIdentity()->value());
        self::assertFalse($build->loadedWiring()->started());

        @unlink($build->generatedWiringPath());
        @unlink($artifact);
        @rmdir($output);
        @rmdir($root);
    }

    public function testCheckIsWriteFreeAndRequiresExactExpectedOutput(): void
    {
        [$root, $output, $artifact, $source] = self::fixture();
        $before = scandir($output);
        try {
            (new ModelInvocationApplicationBuildWorkflow())->check($root, $artifact, $source, $output);
            self::fail('Check must require the expected generated file.');
        } catch (ModelInvocationApplicationWorkflowException) {
            self::assertSame($before, scandir($output));
        } finally {
            @unlink($artifact);
            @rmdir($output);
            @rmdir($root);
        }
    }

    /** @return array{string,string,string,\Kongono\ModelInvocationBuild\Plan\InvocationPlanSource} */
    private static function fixture(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir() . '/kongono-model-workflow-' . bin2hex(random_bytes(8)));
        $output = $root . '/generated';
        self::assertTrue(mkdir($root));
        self::assertTrue(mkdir($output));
        $source = ModelInvocationApplicationFactory::source('workflow-private-message');
        $artifact = $root . '/invocation-plan.json';
        file_put_contents($artifact, ModelInvocationApplicationFactory::artifactBytes($source));

        return [$root, $output, $artifact, $source];
    }
}
