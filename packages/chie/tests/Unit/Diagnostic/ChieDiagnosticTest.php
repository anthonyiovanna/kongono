<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Diagnostic;

use InvalidArgumentException;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\ArtifactStaleException;
use Kongono\Chie\Exception\ArtifactUnavailableException;
use Kongono\Chie\Exception\ExceptionInterface;
use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\InvalidChieRequestException;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Exception\ProposalSpecUnavailableException;
use Kongono\Chie\Exception\ProposalTargetStaleException;
use Kongono\Chie\Internal\CanonicalJson;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ChieDiagnosticTest extends TestCase
{
    public function testCasesHaveExactIdentifiers(): void
    {
        self::assertSame([
            'KGN-CHIE-1001', 'KGN-CHIE-1002', 'KGN-CHIE-1003', 'KGN-CHIE-1004', 'KGN-CHIE-1005',
            'KGN-CHIE-1006', 'KGN-CHIE-1007', 'KGN-CHIE-1008', 'KGN-CHIE-1009', 'KGN-CHIE-1010',
            'KGN-CHIE-1011', 'KGN-CHIE-1012', 'KGN-CHIE-1013', 'KGN-CHIE-1014', 'KGN-CHIE-1015',
            'KGN-CHIE-1016', 'KGN-CHIE-1017', 'KGN-CHIE-1018',
        ], array_map(static fn(ChieDiagnostic $diagnostic): string => $diagnostic->value, ChieDiagnostic::cases()));
    }

    public function testCasesHaveExactTitles(): void
    {
        self::assertSame([
            'Use a valid read-only Chie request',
            'Provide the explicit framework knowledge artifact',
            'Regenerate the framework knowledge artifact',
            'Validate the framework knowledge artifact',
            'Use a supported Chie topic',
            'Use an identifier present in the exact framework state',
            'Restore the exact cited knowledge source',
            'Restore the exact documented topic section',
            'Reduce the bounded Chie response',
            'Inspect the Chie command boundary',
            'Use a valid explicit Chie proposal request',
            'Provide the explicit proposal specification',
            'Use the canonical proposal specification grammar',
            'Use a current explanatory documentation target',
            'Regenerate the request from the current artifact source',
            'Provide one uniquely matching complete-line sequence',
            'Reduce the bounded candidate diff',
            'Restore the deterministic proposal invariants',
        ], array_map(static fn(ChieDiagnostic $diagnostic): string => $diagnostic->title(), ChieDiagnostic::cases()));
    }

    public function testEveryContextHasOneRequiredSafeViolationField(): void
    {
        $vocabulary = array_merge(...array_values(self::violations()));
        foreach (ChieDiagnostic::cases() as $diagnostic) {
            foreach (self::violations()[$diagnostic->value] as $violation) {
                $context = $diagnostic->context($violation);
                self::assertCount(1, $context->fields());
                self::assertSame('violation', $context->fields()[0]->key());
                self::assertSame($violation, $context->fields()[0]->value());
                self::assertSame(DiagnosticDisclosure::Safe, $context->fields()[0]->disclosure());
                self::assertFalse($context->fields()[0]->textWasTruncated());
            }
            foreach ($vocabulary as $violation) {
                if (in_array($violation, self::violations()[$diagnostic->value], true)) {
                    continue;
                }
                try {
                    $diagnostic->context($violation);
                    self::fail('Mismatched diagnostic violation was admitted.');
                } catch (InvalidArgumentException $exception) {
                    self::assertInstanceOf(InvalidChieValueException::class, $exception);
                    self::assertInstanceOf(InvalidArgumentException::class, $exception);
                    self::assertSame('Invalid Chie diagnostic violation.', $exception->getMessage());
                    self::assertStringNotContainsString($violation, (string) $exception);
                }
            }
        }

        try {
            ChieDiagnostic::InvalidRequest->context('attacker-secret-token');
            self::fail('Unknown diagnostic violation was admitted.');
        } catch (InvalidArgumentException $exception) {
            self::assertInstanceOf(InvalidChieValueException::class, $exception);
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertSame('Invalid Chie diagnostic violation.', $exception->getMessage());
            self::assertStringNotContainsString('attacker-secret-token', (string) $exception);
        }
    }

    public function testMetadataIsActionableAndBounded(): void
    {
        foreach (ChieDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->operation());
            self::assertLessThanOrEqual(160, strlen($diagnostic->operation()));
            self::assertNotSame('', $diagnostic->violatedInvariant());
            self::assertLessThanOrEqual(256, strlen($diagnostic->violatedInvariant()));
            self::assertNotSame('', $diagnostic->remediation());
        }
    }

    public function testConcreteExceptionsUseExactConstructorAndSafeStringification(): void
    {
        $valueFailures = [
            [InvalidChieValueException::citation(), 'Invalid Chie citation.', ['C:/private/secret.txt', '/private/secret.txt']],
            [InvalidChieValueException::claim(), 'Invalid Chie claim.', ['forbidden', 'arbitrary-observation', str_repeat('a', 32769)]],
            [InvalidChieValueException::response(), 'Invalid Chie response.', ['attacker-reason', str_repeat('A', 64)]],
            [InvalidChieValueException::diagnosticViolation(), 'Invalid Chie diagnostic violation.', ['artifact-path', 'attacker-secret-token']],
            [InvalidChieValueException::exceptionDiagnostic(), 'Invalid Chie exception diagnostic.', ['operation', 'native-attacker-secret']],
            [InvalidChieValueException::errorEnvelope(), 'Invalid Chie error.', ['attacker-secret', 'unexpected-failure']],
        ];
        foreach ($valueFailures as [$exception, $message, $rejectedValues]) {
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
            self::assertSame($message, $exception->getMessage());
            self::assertSame(InvalidChieValueException::class . ': ' . $message, (string) $exception);
            self::assertStringNotContainsString('Stack trace:', (string) $exception);
            self::assertStringNotContainsString('#0', (string) $exception);
            self::assertStringNotContainsString(__FILE__, (string) $exception);
            self::assertDoesNotMatchRegularExpression('/\d+/', (string) $exception);
            foreach ($rejectedValues as $rejectedValue) {
                self::assertStringNotContainsString($rejectedValue, (string) $exception);
            }
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
        }

        $allowed = [
            ArtifactUnavailableException::class => [[ChieDiagnostic::ArtifactUnavailable, 'artifact-read']],
            ArtifactStaleException::class => [[ChieDiagnostic::ArtifactStale, 'source-state-mismatch']],
            InvalidChieRequestException::class => [[ChieDiagnostic::InvalidRequest, 'operation'], [ChieDiagnostic::ResponseLimitExceeded, 'response-limit']],
            InvalidArtifactException::class => [
                [ChieDiagnostic::InvalidArtifact, 'artifact-shape'],
                [ChieDiagnostic::InvalidRequest, 'operation'],
                [ChieDiagnostic::CitationResolutionFailed, 'citation'],
                [ChieDiagnostic::SourceSectionInvalid, 'heading-missing'],
                [ChieDiagnostic::ResponseLimitExceeded, 'artifact-limit'],
            ],
            InvalidChieProposalException::class => [
                [ChieDiagnostic::InvalidProposalRequest, 'target-path'],
                [ChieDiagnostic::ProposalSpecInvalid, 'spec-shape'],
                [ChieDiagnostic::ProposalTargetUnsupported, 'target-authority'],
                [ChieDiagnostic::ProposalReplacementInvalid, 'match-count'],
                [ChieDiagnostic::ProposalDiffLimitExceeded, 'diff-limit'],
                [ChieDiagnostic::ProposalConstructionFailed, 'proposal-shape'],
            ],
            ProposalSpecUnavailableException::class => [[ChieDiagnostic::ProposalSpecUnavailable, 'spec-read']],
            ProposalTargetStaleException::class => [[ChieDiagnostic::ProposalTargetStale, 'target-sha']],
        ];
        foreach ($allowed as $type => $pairs) {
            foreach ($pairs as [$diagnostic, $violation]) {
                $native = new RuntimeException('native-secret');
                $exception = new $type($diagnostic, $violation, $native);
                self::assertInstanceOf(ExceptionInterface::class, $exception);
                self::assertSame($native, $exception->getPrevious());
                self::assertSame($diagnostic->value, $exception->diagnosticId());
                self::assertStringNotContainsString('native-secret', (string) $exception);
                self::assertStringNotContainsString($violation, (string) $exception);
            }
        }

        foreach (array_keys($allowed) as $type) {
            $allowedDiagnostics = array_map(static fn(array $pair): ChieDiagnostic => $pair[0], $allowed[$type]);
            foreach (ChieDiagnostic::cases() as $diagnostic) {
                if (in_array($diagnostic, $allowedDiagnostics, true)) {
                    continue;
                }
                $violation = self::violations()[$diagnostic->value][0];
                try {
                    new $type($diagnostic, $violation, new RuntimeException('native-attacker-secret'));
                    self::fail('Invalid exception/diagnostic pairing was admitted.');
                } catch (InvalidArgumentException $exception) {
                    self::assertInstanceOf(InvalidChieValueException::class, $exception);
                    self::assertInstanceOf(InvalidArgumentException::class, $exception);
                    self::assertSame('Invalid Chie exception diagnostic.', $exception->getMessage());
                    self::assertStringNotContainsString('native-attacker-secret', (string) $exception);
                    self::assertStringNotContainsString($violation, (string) $exception);
                }
            }
        }

        foreach ([[ChieDiagnostic::InvalidRequest, 'invalid-command-usage'], [ChieDiagnostic::UnexpectedCommandFailure, 'unexpected-failure']] as [$diagnostic, $uncertainty]) {
            $bytes = CanonicalJson::encodeError($diagnostic, $uncertainty);
            self::assertSame('{"schema":"kongono.chie-error","version":1,"role":"chie","status":"error","diagnostic_id":"' . $diagnostic->value . '","title":"' . $diagnostic->title() . '","uncertainty":"' . $uncertainty . '"}' . "\n", $bytes);
        }
        foreach ([[ChieDiagnostic::InvalidRequest, 'unexpected-failure'], [ChieDiagnostic::UnexpectedCommandFailure, 'invalid-command-usage'], [ChieDiagnostic::InvalidArtifact, 'attacker-secret']] as [$diagnostic, $uncertainty]) {
            try {
                CanonicalJson::encodeError($diagnostic, $uncertainty);
                self::fail('Invalid error-envelope pairing was admitted.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidChieValueException::class, $exception);
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
                self::assertSame('Invalid Chie error.', $exception->getMessage());
                self::assertStringNotContainsString($uncertainty, (string) $exception);
            }
        }
    }

    public function testNoDiagnosticSchemaContainsProtectedContext(): void
    {
        foreach (ChieDiagnostic::cases() as $diagnostic) {
            foreach ($diagnostic->context(self::violations()[$diagnostic->value][0])->fields() as $field) {
                self::assertNotSame(DiagnosticDisclosure::Protected, $field->disclosure());
            }
        }
    }

    /** @return array<string, list<string>> */
    private static function violations(): array
    {
        return [
            'KGN-CHIE-1001' => ['operation', 'topic-grammar', 'diagnostic-grammar', 'claim-class', 'claim-text', 'claim-citation', 'failure-status', 'invalid-command-usage'],
            'KGN-CHIE-1002' => ['artifact-path', 'artifact-read', 'framework-root', 'source-unavailable'],
            'KGN-CHIE-1003' => ['source-state-mismatch'],
            'KGN-CHIE-1004' => ['artifact-shape', 'artifact-name', 'artifact-metadata', 'topic-catalog'],
            'KGN-CHIE-1005' => ['topic-unsupported'],
            'KGN-CHIE-1006' => ['diagnostic-not-found'],
            'KGN-CHIE-1007' => ['source-missing', 'authority', 'citation'],
            'KGN-CHIE-1008' => ['heading-missing', 'heading-duplicate', 'section-limit'],
            'KGN-CHIE-1009' => ['artifact-limit', 'citation-limit', 'response-limit', 'line-limit', 'occurrence-limit'],
            'KGN-CHIE-1010' => ['unexpected-failure'],
            'KGN-CHIE-1011' => ['target-path', 'target-sha', 'before-shape', 'after-shape'],
            'KGN-CHIE-1012' => ['spec-path', 'spec-read'],
            'KGN-CHIE-1013' => ['spec-path', 'spec-shape', 'before-shape', 'after-shape'],
            'KGN-CHIE-1014' => ['target-path', 'target-authority', 'target-missing'],
            'KGN-CHIE-1015' => ['target-sha'],
            'KGN-CHIE-1016' => ['match-count', 'source-limit'],
            'KGN-CHIE-1017' => ['diff-limit'],
            'KGN-CHIE-1018' => ['response-limit', 'proposal-shape'],
        ];
    }
}
