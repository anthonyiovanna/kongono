<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;

/** Checks exact adapter and final-result bindings without resolving collaborators. */
#[Api(ApiStatus::Internal)]
final class RuntimeGuard
{
    public static function validateAdapter(InvocationRuntimeRequest $request): void
    {
        if (!$request->source()->adapterIdentity()->equals($request->adapter()->identity())) {
            throw InvalidInvocationRuntimeException::invalid('adapter_identity');
        }
    }

    public static function validateResult(InvocationRuntimeRequest $request, ModelResult $result): int
    {
        $expected = $request->source()->request();
        if (
            !$result->invocationIdentity()->equals($expected->identity())
            || !$result->profile()->equals($expected->profile())
            || $result->mode() !== $expected->resultMode()
            || FinishReason::Cancelled === $result->finishReason()
        ) {
            throw InvalidInvocationRuntimeException::invalid('result_contract');
        }

        if (ResultMode::ActionSuggestion === $result->mode()) {
            $suggestion = $result->actionSuggestion();
            if (null === $suggestion) {
                throw InvalidInvocationRuntimeException::invalid('result_contract');
            }

            return strlen($suggestion->name()->value()) + strlen($suggestion->arguments()->value());
        }
        $text = $result->text();
        if (null === $text) {
            throw InvalidInvocationRuntimeException::invalid('result_contract');
        }

        return strlen($text->value());
    }

    private function __construct() {}
}
