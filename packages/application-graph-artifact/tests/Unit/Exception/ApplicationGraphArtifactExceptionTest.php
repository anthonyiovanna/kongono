<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Exception;

use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactLoadException;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactPublicationException;
use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Exception\StaleApplicationGraphArtifactException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApplicationGraphArtifactExceptionTest extends TestCase
{
    public function testFamiliesOwnExactDiagnosticsAndRetainCause(): void
    {
        $cause = new RuntimeException('secret path C:\\private');
        $values = [InvalidApplicationGraphArtifactException::graph('graph_readmission_failed', 'graph', [], $cause),InvalidApplicationGraphArtifactException::artifact('invalid_json'),InvalidApplicationGraphArtifactException::limit('artifact_bytes', 'artifact', 1),StaleApplicationGraphArtifactException::graphStateMismatch(),ApplicationGraphArtifactPublicationException::failed('atomic_publish_failed'),ApplicationGraphArtifactLoadException::failed('file_unreadable')];
        self::assertSame(['KGN-GRAPH-1116','KGN-GRAPH-1117','KGN-GRAPH-1118','KGN-GRAPH-1119','KGN-GRAPH-1120','KGN-GRAPH-1121'], array_map(static fn($e): string => $e->diagnosticId(), $values));
        self::assertSame($cause, $values[0]->getPrevious());
        self::assertStringNotContainsString('private', (string) $values[0]);
    }
}
