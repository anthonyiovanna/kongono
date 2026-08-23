<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit;

use InvalidArgumentException;
use Kongono\Chie\ChieCitation;
use Kongono\Chie\ChieClaim;
use Kongono\Chie\ChieClaimClass;
use Kongono\Chie\ChieOperation;
use Kongono\Chie\ChieResponse;
use Kongono\Chie\ChieStatus;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Internal\CanonicalJson;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;
use PHPUnit\Framework\TestCase;

final class ChieResponseTest extends TestCase
{
    public function testCanonicalResponseHasExactKeyOrderAndOneFinalLf(): void
    {
        $response = ChieResponse::unsupported(ChieOperation::Explain, str_repeat('a', 64), str_repeat('b', 64), 'topic-unsupported');
        $bytes = CanonicalJson::encodeResponse($response);

        self::assertSame('{"schema":"kongono.chie-response","version":1,"role":"chie","status":"unsupported","operation":"explain","framework_artifact_sha256":"' . str_repeat('a', 64) . '","source_set_sha256":"' . str_repeat('b', 64) . '","claims":[],"uncertainties":["topic-unsupported"]}' . "\n", $bytes);
        self::assertSame(1, substr_count($bytes, "\n"));
        self::assertStringEndsWith("\n", $bytes);
        self::assertStringStartsWith('{"schema":"kongono.chie-response","version":1,"role":"chie","status":"unsupported","operation":"explain","framework_artifact_sha256":', $bytes);
        $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['schema', 'version', 'role', 'status', 'operation', 'framework_artifact_sha256', 'source_set_sha256', 'claims', 'uncertainties'], array_keys($decoded));
    }

    public function testClaimsAndReturnedArraysAreDetached(): void
    {
        $observation = 'The caller supplied one Safe diagnostic identifier for read-only diagnosis.';
        $uncertainty = 'The requested information is unresolved in the exact validated framework state.';
        $claims = [new ChieClaim(ChieClaimClass::CurrentWorkspaceObservation, $observation)];
        $response = ChieResponse::answered(ChieOperation::Diagnose, str_repeat('a', 64), str_repeat('b', 64), $claims);
        $claims[] = new ChieClaim(ChieClaimClass::UnresolvedUncertainty, $uncertainty);
        $returned = $response->claims();
        $returned[] = $claims[1];

        self::assertCount(1, $response->claims());
        self::assertSame($observation, $response->claims()[0]->text());
        self::assertSame([], $response->claims()[0]->citations());
        self::assertSame($uncertainty, $claims[1]->text());
        self::assertSame([], $claims[1]->citations());
    }

    public function testCitationSerializesExactFieldOrder(): void
    {
        $sourceSet = str_repeat('b', 64);
        $reference = 'kgn-source-v1:' . $sourceSet . ':0001';
        $citation = new ChieCitation($reference, 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9);
        $claim = new ChieClaim(ChieClaimClass::AcceptedFrameworkFact, 'text', [$citation]);
        $data = $claim->toArray();

        self::assertSame(['class', 'text', 'citations'], array_keys($data));
        self::assertSame(['reference', 'path', 'authority', 'sha256', 'line_start', 'line_end'], array_keys($data['citations'][0]));
        self::assertSame('governing-authority', $data['citations'][0]['authority']);

        $invalid = [
            ['private-reference', 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            ['kgn-source-v1:' . strtoupper($sourceSet) . ':0001', 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            ['kgn-source-v1:' . $sourceSet . ':001', 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, '', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, str_repeat('a', 241), KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, 'C:/private/secret.txt', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, '/private/secret.txt', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, '../private/secret.txt', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, 'docs/../private.txt', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, 'docs\\private.txt', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, 'https://example.test/private', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, "docs/private\0.txt", KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, 'docs/privé.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 4, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::HistoricalEvidence, str_repeat('c', 64), 4, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::WorkspaceObservation, str_repeat('c', 64), 4, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::DerivedOrAdvisoryMaterial, str_repeat('c', 64), 4, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('C', 64), 4, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 63), 4, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 0, 9],
            [$reference, 'docs/example.md', KnowledgeSourceAuthority::GoverningAuthority, str_repeat('c', 64), 9, 4],
        ];
        foreach ($invalid as $arguments) {
            try {
                new ChieCitation(...$arguments);
                self::fail('Invalid citation was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie citation.', $exception->getMessage());
                self::assertStringNotContainsString('private', (string) $exception);
                self::assertStringNotContainsString('secret', (string) $exception);
            }
        }
    }

    public function testAllArtifactFailureStatesHideDigestsAndClaims(): void
    {
        foreach ([[ChieStatus::Unavailable, 'artifact-unavailable'], [ChieStatus::Stale, 'artifact-stale'], [ChieStatus::Blocked, 'artifact-invalid']] as [$status, $uncertainty]) {
            $response = ChieResponse::artifactFailure(ChieOperation::Explain, $status, $uncertainty);
            self::assertNull($response->frameworkArtifactSha256());
            self::assertNull($response->sourceSetSha256());
            self::assertSame([], $response->claims());
            self::assertSame([$uncertainty], $response->uncertainties());
        }

        foreach ([[ChieStatus::Unavailable, 'artifact-stale'], [ChieStatus::Stale, 'artifact-invalid'], [ChieStatus::Blocked, 'artifact-unavailable'], [ChieStatus::Answered, 'artifact-invalid']] as [$status, $uncertainty]) {
            try {
                ChieResponse::artifactFailure(ChieOperation::Explain, $status, $uncertainty);
                self::fail('Invalid artifact-failure mapping was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie response.', $exception->getMessage());
                self::assertStringNotContainsString($uncertainty, (string) $exception);
            }
        }

        foreach ([[ChieOperation::Explain, 'diagnostic-not-found'], [ChieOperation::Diagnose, 'topic-unsupported'], [ChieOperation::Explain, 'attacker-reason']] as [$operation, $uncertainty]) {
            try {
                ChieResponse::unsupported($operation, str_repeat('a', 64), str_repeat('b', 64), $uncertainty);
                self::fail('Invalid unsupported mapping was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie response.', $exception->getMessage());
                self::assertStringNotContainsString($uncertainty, (string) $exception);
            }
        }
    }

    public function testProhibitedPhase4DClaimClassesCannotBeEmitted(): void
    {
        foreach ([ChieClaimClass::Inference, ChieClaimClass::Recommendation, ChieClaimClass::GeneratedCandidate] as $class) {
            try {
                new ChieClaim($class, 'forbidden');
                self::fail('Prohibited claim class was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie claim.', $exception->getMessage());
                self::assertStringNotContainsString('forbidden', (string) $exception);
            }
        }

        $reference = 'kgn-source-v1:' . str_repeat('b', 64) . ':0001';
        $citation = new ChieCitation($reference, 'docs/example.md', KnowledgeSourceAuthority::ImplementationEvidence, str_repeat('c', 64), 1, 1);
        $invalid = [
            [ChieClaimClass::AcceptedFrameworkFact, 'uncited-attacker', []],
            [ChieClaimClass::AcceptedFrameworkFact, 'over-cited-attacker', [$citation, $citation]],
            [ChieClaimClass::CurrentWorkspaceObservation, 'arbitrary-observation', []],
            [ChieClaimClass::CurrentWorkspaceObservation, 'The caller supplied one Safe diagnostic identifier for read-only diagnosis.', [$citation]],
            [ChieClaimClass::UnresolvedUncertainty, 'arbitrary-uncertainty', []],
            [ChieClaimClass::UnresolvedUncertainty, 'The requested information is unresolved in the exact validated framework state.', [$citation]],
            [ChieClaimClass::AcceptedFrameworkFact, '', [$citation]],
            [ChieClaimClass::AcceptedFrameworkFact, str_repeat('a', 32769), [$citation]],
            [ChieClaimClass::AcceptedFrameworkFact, "invalid-utf8-\xFF", [$citation]],
            [ChieClaimClass::AcceptedFrameworkFact, "bom-\xEF\xBB\xBF", [$citation]],
            [ChieClaimClass::AcceptedFrameworkFact, "carriage\rreturn", [$citation]],
            [ChieClaimClass::AcceptedFrameworkFact, "nul\0byte", [$citation]],
        ];
        foreach ($invalid as $arguments) {
            try {
                new ChieClaim(...$arguments);
                self::fail('Invalid claim was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie claim.', $exception->getMessage());
                self::assertStringNotContainsString('attacker', (string) $exception);
                self::assertStringNotContainsString('arbitrary', (string) $exception);
            }
        }
    }

    public function testClaimAndResponseLimitsFailWithoutPartialResult(): void
    {
        $observation = new ChieClaim(ChieClaimClass::CurrentWorkspaceObservation, 'The caller supplied one Safe diagnostic identifier for read-only diagnosis.');
        $claims = array_fill(0, 10, $observation);

        foreach ([['', str_repeat('b', 64)], [str_repeat('A', 64), str_repeat('b', 64)], [str_repeat('a', 64), str_repeat('b', 63)]] as [$artifact, $sourceSet]) {
            try {
                ChieResponse::answered(ChieOperation::Diagnose, $artifact, $sourceSet, [$observation]);
                self::fail('Malformed digest was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie response.', $exception->getMessage());
                self::assertStringNotContainsString('AAAA', (string) $exception);
            }
        }

        $citation = new ChieCitation('kgn-source-v1:' . str_repeat('c', 64) . ':0001', 'docs/example.md', KnowledgeSourceAuthority::CurrentExplanatoryDocumentation, str_repeat('d', 64), 1, 2);
        try {
            ChieResponse::answered(ChieOperation::Explain, str_repeat('a', 64), str_repeat('b', 64), [new ChieClaim(ChieClaimClass::AcceptedFrameworkFact, 'cross-set-attacker', [$citation])]);
            self::fail('Cross-source-set citation was admitted.');
        } catch (InvalidArgumentException $exception) {
            self::assertInstanceOf(InvalidChieValueException::class, $exception);
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertSame('Invalid Chie response.', $exception->getMessage());
            self::assertStringNotContainsString('cross-set-attacker', (string) $exception);
        }

        try {
            ChieResponse::answered(ChieOperation::Diagnose, str_repeat('a', 64), str_repeat('b', 64), []);
            self::fail('Empty answered response was admitted.');
        } catch (InvalidArgumentException $exception) {
            self::assertInstanceOf(InvalidChieValueException::class, $exception);
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertSame('Invalid Chie response.', $exception->getMessage());
        }

        try {
            ChieResponse::answered(ChieOperation::Diagnose, str_repeat('a', 64), str_repeat('b', 64), $claims);
            self::fail('Over-bound answered response was admitted.');
        } catch (InvalidArgumentException $exception) {
            self::assertInstanceOf(InvalidChieValueException::class, $exception);
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertSame('Invalid Chie response.', $exception->getMessage());
        }
    }
}
