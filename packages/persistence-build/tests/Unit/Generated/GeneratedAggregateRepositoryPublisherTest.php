<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryPublisher;
use PHPUnit\Framework\TestCase;

final class GeneratedAggregateRepositoryPublisherTest extends TestCase
{
    public function testPublicationIsContentAddressedIdempotentAndNoClobber(): void
    {
        $root = dirname(__DIR__, 5);
        $output = sys_get_temp_dir() . '/kongono-repository-publish-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($output));
        $target = null;
        try {
            $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
            $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
            $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
            $artifact = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $persistence->canonicalBytes());
            $publisher = new GeneratedAggregateRepositoryPublisher();
            $target = $publisher->publish($artifact, $output);
            self::assertSame($target, $publisher->publish($artifact, $output));
            self::assertSame($artifact->canonicalBytes(), file_get_contents($target));
            self::assertGreaterThan(0, file_put_contents($target, '<?php // conflict'));
            try {
                $publisher->publish($artifact, $output);
                self::fail('Expected no-clobber rejection.');
            } catch (InvalidPersistenceSourceException $failure) {
                self::assertSame('KGN-PERSISTENCE-BUILD-1019', $failure->diagnosticId());
            }
            $temporaryFiles = glob($output . '/.kongono-persistence-repositories-*');
            self::assertIsArray($temporaryFiles);
            self::assertSame([], $temporaryFiles);
        } finally {
            if (is_string($target) && is_file($target)) {
                unlink($target);
            }
            if (is_dir($output)) {
                rmdir($output);
            }
        }
    }
}
