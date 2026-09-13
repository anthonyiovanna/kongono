<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Failure;

use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewContract\Failure\RenderFailureKind;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Lifecycle\RenderOperationState;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RenderFailureTest extends TestCase
{
    public function testFailureKindsAndLifecycleVocabularyAreExact(): void
    {
        self::assertSame([
            'invalid_request',
            'schema_mismatch',
            'stale_artifact',
            'unsupported_output_context',
            'bound_exceeded',
            'rendering_failed',
            'retirement_failed',
        ], array_column(RenderFailureKind::cases(), 'value'));
        self::assertSame(['requested', 'rendering', 'completed', 'failed', 'retired'], array_column(RenderOperationState::cases(), 'value'));
    }

    public function testFailureIsImmutableExactFailedStateEvidence(): void
    {
        $failure = self::failure(RenderFailureKind::SchemaMismatch);
        self::assertSame(ContractVersion::Version1, $failure->contractVersion());
        self::assertSame(1, $failure->contractVersion()->value);
        self::assertSame(RenderFailureKind::SchemaMismatch, $failure->kind());
        self::assertSame(RenderOperationState::Failed, $failure->state());
        self::assertTrue($failure->equals(self::failure(RenderFailureKind::SchemaMismatch)));
        self::assertFalse($failure->equals(self::failure(RenderFailureKind::RenderingFailed)));

        $properties = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), (new ReflectionClass($failure))->getProperties());
        foreach (['message', 'throwable', 'path', 'bytes', 'response', 'request'] as $forbidden) {
            self::assertNotContains($forbidden, $properties);
        }
    }

    private static function failure(RenderFailureKind $kind): RenderFailure
    {
        return new RenderFailure(
            ContractVersion::Version1,
            new RenderOperationIdentity('render-failure-1'),
            new LogicalViewIdentity('account.profile'),
            new CompiledViewIdentity(str_repeat('d', 64)),
            $kind,
        );
    }
}
