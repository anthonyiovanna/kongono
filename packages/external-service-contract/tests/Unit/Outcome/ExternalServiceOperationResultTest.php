<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Outcome;

use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailure;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOperationResult;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use PHPUnit\Framework\TestCase;

final class ExternalServiceOperationResultTest extends TestCase
{
    public function testSuccessAndUncertaintyRelationsAreExact(): void
    {
        $success = self::operationResult(ExternalServiceLifecycleState::Retired, ExternalServiceOutcome::Succeeded, null);
        self::assertTrue($success->succeeded());
        self::assertTrue($success->equals(self::operationResult(ExternalServiceLifecycleState::Retired, ExternalServiceOutcome::Succeeded, null)));
        $uncertain = self::operationResult(ExternalServiceLifecycleState::Failed, ExternalServiceOutcome::UncertainEffect, self::failure());
        self::assertFalse($uncertain->succeeded());
        self::assertSame('operation.one', $uncertain->operationIdentity()->value());
        self::assertSame('binding.one', $uncertain->bindingIdentity()->value());
    }

    public function testIncoherentShapesFailClosed(): void
    {
        $cases = [
            [ExternalServiceLifecycleState::Prepared, ExternalServiceOutcome::Succeeded, null],
            [ExternalServiceLifecycleState::Failed, ExternalServiceOutcome::Succeeded, null],
            [ExternalServiceLifecycleState::Retired, ExternalServiceOutcome::FailedBeforeEffect, null],
            [ExternalServiceLifecycleState::Retired, ExternalServiceOutcome::UncertainEffect, self::failure()],
        ];
        foreach ($cases as [$state, $outcome, $failure]) {
            try {
                self::operationResult($state, $outcome, $failure);
                self::fail('Expected result rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                self::assertSame('KGN-EXTERNAL-SERVICE-CONTRACT-1606' === $exception->diagnosticId() || 'KGN-EXTERNAL-SERVICE-CONTRACT-1607' === $exception->diagnosticId(), true);
            }
        }
    }

    private static function operationResult(ExternalServiceLifecycleState $state, ExternalServiceOutcome $outcome, ?ExternalServiceFailure $failure): ExternalServiceOperationResult
    {
        return new ExternalServiceOperationResult(new ExternalServiceOperationIdentity('operation.one'), new ServiceBindingIdentity('binding.one'), $state, $outcome, $failure);
    }

    private static function failure(): ExternalServiceFailure
    {
        return new ExternalServiceFailure(ExternalServiceFailureKind::Invariant, 'state_unknown');
    }
}
