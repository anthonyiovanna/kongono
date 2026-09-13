<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewContract\Failure\RenderFailureKind;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewRuntime\Artifact\LoadedCompiledViewArtifact;
use Kongono\ViewRuntime\Exception\InvalidRenderRequestException;
use Kongono\ViewRuntime\Exception\RenderBoundExceededException;
use Kongono\ViewRuntime\Exception\RenderRetirementException;
use Kongono\ViewRuntime\Exception\ViewRenderingException;
use Throwable;

/** Coordinates one synchronous render and its unconditional operation-local retirement. */
#[Api(ApiStatus::Internal)]
final class RenderOperation
{
    public function run(LoadedCompiledViewArtifact $artifact, RenderRequest $request): RenderResult|RenderFailure
    {
        $sink = new BufferedRenderSink($request->limits());
        $primary = null;
        try {
            $failureKind = $this->validate($artifact, $request);
            if (null !== $failureKind) {
                $sink->fail();

                return $this->failure($request, $failureKind);
            }
            $sink->begin();
            try {
                $artifact->emit($request->logicalViewIdentity(), $request->context(), $sink);
                [$output, $metadata] = $sink->complete();

                return new RenderResult($request->contractVersion(), $request->operationIdentity(), $request->logicalViewIdentity(), $request->compiledViewIdentity(), $output, $metadata);
            } catch (RenderBoundExceededException $failure) {
                $primary = $failure;
                $sink->fail();

                return $this->failure($request, RenderFailureKind::BoundExceeded);
            } catch (ViewRenderingException $failure) {
                $primary = $failure;
                $sink->fail();
                $kind = 'KGN-VIEW-RUNTIME-1834' === $failure->diagnosticId() ? RenderFailureKind::UnsupportedOutputContext : RenderFailureKind::RenderingFailed;

                return $this->failure($request, $kind);
            } catch (Throwable $failure) {
                $primary = ViewRenderingException::failed('generated_throwable', previous: $failure);
                $sink->fail();

                return $this->failure($request, RenderFailureKind::RenderingFailed);
            }
        } finally {
            try {
                $sink->retire();
            } catch (Throwable $retirementFailure) {
                throw RenderRetirementException::failed($this->primaryKind($primary), $primary ?? $retirementFailure);
            }
        }
    }

    private function validate(LoadedCompiledViewArtifact $artifact, RenderRequest $request): ?RenderFailureKind
    {
        if (!$artifact->compiledViewIdentity()->equals($request->compiledViewIdentity())) {
            return RenderFailureKind::StaleArtifact;
        }
        try {
            if (!$artifact->hasView($request->logicalViewIdentity())) {
                return RenderFailureKind::InvalidRequest;
            }
            if (!$artifact->accepts($request->logicalViewIdentity(), $request->contextSchema())) {
                return RenderFailureKind::SchemaMismatch;
            }
            $request->contextSchema()->validate($request->context());
        } catch (Throwable) {
            return RenderFailureKind::SchemaMismatch;
        }

        return null;
    }

    private function failure(RenderRequest $request, RenderFailureKind $kind): RenderFailure
    {
        return new RenderFailure($request->contractVersion(), $request->operationIdentity(), $request->logicalViewIdentity(), $request->compiledViewIdentity(), $kind);
    }

    private function primaryKind(?Throwable $primary): string
    {
        return match (true) {
            $primary instanceof RenderBoundExceededException => 'bound_exceeded',
            $primary instanceof InvalidRenderRequestException => 'invalid_request',
            $primary instanceof ViewRenderingException && 'KGN-VIEW-RUNTIME-1834' === $primary->diagnosticId() => 'unsupported_output_context',
            $primary instanceof ViewRenderingException => 'rendering_failed',
            null === $primary => 'success',
            default => 'rendering_failed',
        };
    }
}
