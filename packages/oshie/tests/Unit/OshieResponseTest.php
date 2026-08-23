<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit;

use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Oshie\Exception\InvalidOshieValueException;
use Kongono\Oshie\Internal\CanonicalJson;
use Kongono\Oshie\Oshie;
use Kongono\Oshie\OshieRequest;
use Kongono\Oshie\OshieResponse;
use PHPUnit\Framework\TestCase;

final class OshieResponseTest extends TestCase
{
    public function testCanonicalResponseHasExactTopLevelOrderAndLfOnlyTermination(): void
    {
        $root = dirname(__DIR__, 4);
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($root);
        $response = (new Oshie())->respondCurrent($root, $artifact->canonicalBytes(), OshieRequest::catalog());
        $bytes = CanonicalJson::encodeResponse($response);
        self::assertStringEndsWith("\n", $bytes);
        self::assertStringNotContainsString("\r", $bytes);
        $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(['schema', 'version', 'role', 'status', 'operation', 'curriculum_id', 'framework_artifact_sha256', 'source_set_sha256', 'curriculum', 'lesson', 'exercise', 'attempt', 'uncertainties'], array_keys($decoded));
    }

    public function testImpossibleCatalogAndFailureShapesAreRejected(): void
    {
        try {
            OshieResponse::available(str_repeat('a', 64), str_repeat('b', 64), []);
            self::fail('Empty catalog was admitted.');
        } catch (InvalidOshieValueException $exception) {
            self::assertSame('Invalid Oshie response.', $exception->getMessage());
        }
        $this->expectException(InvalidOshieValueException::class);
        OshieResponse::artifactFailure(\Kongono\Oshie\OshieOperation::Catalog, \Kongono\Oshie\OshieStatus::Correct, 'artifact-invalid');
    }
}
