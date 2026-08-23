<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Topic;

use Kongono\Chie\ChieClaimClass;
use Kongono\Chie\ChieOperation;
use Kongono\Chie\ChieRequest;
use Kongono\Chie\ChieStatus;
use Kongono\Chie\ChieTopic;
use Kongono\Chie\DiagnosticObservation;
use Kongono\Chie\Exception\InvalidChieRequestException;
use PHPUnit\Framework\TestCase;

final class ChieTopicTest extends TestCase
{
    public function testTopicEnumContainsExactlySixTokens(): void
    {
        self::assertSame([
            'developer-intelligence', 'error-handling', 'knowledge-artifact',
            'knowledge-foundation', 'phase-4-roadmap', 'php-support',
        ], array_map(static fn(ChieTopic $topic): string => $topic->value, ChieTopic::cases()));
    }

    public function testExplanationRequestAdmitsKnownAndUnknownGrammarWithoutSubstitution(): void
    {
        $known = ChieRequest::explain('php-support');
        $unknown = ChieRequest::explain('valid-but-unknown');

        self::assertSame(ChieOperation::Explain, $known->operation());
        self::assertSame('php-support', $known->topic());
        self::assertSame('valid-but-unknown', $unknown->topic());
        self::assertNull($unknown->observation());
    }

    public function testInvalidTopicBytesFailWithoutEcho(): void
    {
        $rejected = 'Invalid Secret Topic';
        try {
            ChieRequest::explain($rejected);
            self::fail('Invalid topic was admitted.');
        } catch (InvalidChieRequestException $exception) {
            self::assertStringNotContainsString($rejected, $exception->getMessage());
            self::assertStringNotContainsString($rejected, (string) $exception);
        }
    }

    public function testDiagnosticObservationCarriesOnlyOneAdmittedIdentifier(): void
    {
        $observation = new DiagnosticObservation('KGN-KNOWLEDGE-1001');
        $request = ChieRequest::diagnose($observation);

        self::assertSame('KGN-KNOWLEDGE-1001', $observation->identifier());
        self::assertSame(ChieOperation::Diagnose, $request->operation());
        self::assertSame($observation, $request->observation());
        self::assertNull($request->topic());
    }

    public function testInvalidDiagnosticInputFailsWithoutEcho(): void
    {
        $rejected = 'KGN-bad-secret-1001';
        try {
            new DiagnosticObservation($rejected);
            self::fail('Invalid diagnostic identifier was admitted.');
        } catch (InvalidChieRequestException $exception) {
            self::assertStringNotContainsString($rejected, $exception->getMessage());
            self::assertStringNotContainsString($rejected, (string) $exception);
        }
    }

    public function testClosedEnumsModelStatesButNoLaterRoleAuthority(): void
    {
        self::assertSame(['explain', 'diagnose'], array_map(static fn(ChieOperation $operation): string => $operation->value, ChieOperation::cases()));
        self::assertSame(['answered', 'unsupported', 'unavailable', 'stale', 'blocked'], array_map(static fn(ChieStatus $status): string => $status->value, ChieStatus::cases()));
        self::assertSame(['accepted-framework-fact', 'current-workspace-observation', 'inference', 'recommendation', 'generated-candidate', 'unresolved-uncertainty'], array_map(static fn(ChieClaimClass $class): string => $class->value, ChieClaimClass::cases()));
        self::assertNotContains('pass', array_map(static fn(ChieStatus $status): string => $status->value, ChieStatus::cases()));
        self::assertNotContains('fail', array_map(static fn(ChieStatus $status): string => $status->value, ChieStatus::cases()));
    }
}
