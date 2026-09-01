<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Tests\Unit\Workflow;

use Kongono\AgentApplication\Exception\AgentApplicationWorkflowException;
use Kongono\AgentApplication\Tests\Support\AgentApplicationFactory;
use Kongono\AgentApplication\Workflow\AgentApplicationBuildWorkflow;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use PHPUnit\Framework\TestCase;

final class AgentApplicationBuildWorkflowTest extends TestCase
{
    public function testBuildSnapshotsPublishesLoadsAndReturnsCurrentEvidence(): void
    {
        [$root, $output, $artifactPath] = self::fixture('calendar.workflow');
        $declaration = AgentApplicationFactory::declaration('calendar.workflow');
        $build = (new AgentApplicationBuildWorkflow())->build($root, $artifactPath, $declaration, AgentRuntimeFactory::plan($declaration), $output);

        self::assertFileExists($build->generatedWiringPath());
        self::assertSame(hash('sha256', $build->generatedWiringBytes()), $build->generatedWiringSha256());
        self::assertSame($build->profile()->identity()->value(), $build->generatedWiring()->profileIdentity()->value());
        self::assertFalse($build->loadedWiring()->started());

        @unlink($build->generatedWiringPath());
        @unlink($artifactPath);
        @rmdir($output);
        @rmdir($root);
    }

    public function testCheckIsWriteFreeAndRequiresExactExpectedOutput(): void
    {
        [$root, $output, $artifactPath] = self::fixture('calendar.check');
        $declaration = AgentApplicationFactory::declaration('calendar.check');
        $before = scandir($output);
        try {
            (new AgentApplicationBuildWorkflow())->check($root, $artifactPath, $declaration, AgentRuntimeFactory::plan($declaration), $output);
            self::fail('Check must require the expected generated file.');
        } catch (AgentApplicationWorkflowException) {
            self::assertSame($before, scandir($output));
        } finally {
            @unlink($artifactPath);
            @rmdir($output);
            @rmdir($root);
        }
    }

    /** @return array{string,string,string} */
    private static function fixture(string $capability): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir() . '/kongono-agent-workflow-' . bin2hex(random_bytes(8)));
        $output = $root . '/generated';
        self::assertTrue(mkdir($root));
        self::assertTrue(mkdir($output));
        $declaration = AgentApplicationFactory::declaration($capability);
        $artifactPath = $root . '/declaration.json';
        file_put_contents($artifactPath, (new AgentDeclarationArtifactBuilder())->build($declaration)->canonicalBytes());

        return [$root, $output, $artifactPath];
    }
}
