<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Unit\Workflow;

use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationWorkflowException;
use Kongono\NetworkEgressApplication\Tests\Support\NetworkEgressApplicationFactory;
use Kongono\NetworkEgressApplication\Workflow\NetworkEgressApplicationBuildWorkflow;
use PHPUnit\Framework\TestCase;

final class NetworkEgressApplicationBuildWorkflowTest extends TestCase
{
    public function testBuildValidatesPublishesLoadsAndReturnsCurrentEvidence(): void
    {
        [$root, $output] = self::fixture();
        $source = NetworkEgressApplicationFactory::policy('workflow-policy');
        $build = (new NetworkEgressApplicationBuildWorkflow())->build($root, 'generated', $source, NetworkEgressApplicationFactory::artifactBytes($source));

        self::assertFileExists($build->publishedPath());
        self::assertSame($build->profile()->identity()->value(), $build->generatedWiring()->applicationProfileIdentity()->value());
        self::assertSame(hash('sha256', $build->generatedWiring()->canonicalBytes()), $build->generatedWiring()->generatedWiringSha256());
        self::assertFalse($build->loadedWiring()->started());

        @unlink($build->publishedPath());
        @rmdir($output);
        @rmdir($root);
    }

    public function testBuildRejectsStaleArtifactBeforePublication(): void
    {
        [$root, $output] = self::fixture();
        $before = scandir($output);
        try {
            (new NetworkEgressApplicationBuildWorkflow())->build(
                $root,
                'generated',
                NetworkEgressApplicationFactory::policy('current-policy'),
                NetworkEgressApplicationFactory::artifactBytes(NetworkEgressApplicationFactory::policy('stale-policy')),
            );
            self::fail('Stale policy artifact must fail before publication.');
        } catch (NetworkEgressApplicationWorkflowException) {
            self::assertSame($before, scandir($output));
        } finally {
            @rmdir($output);
            @rmdir($root);
        }
    }

    /** @return array{string,string} */
    private static function fixture(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir() . '/kongono-egress-workflow-' . bin2hex(random_bytes(8)));
        $output = $root . '/generated';
        self::assertTrue(mkdir($root));
        self::assertTrue(mkdir($output));

        return [$root, $output];
    }
}
