<?php

declare(strict_types=1);

namespace Kongono\Authorization\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\Diagnostic\AuthorizationDiagnostic;
use Throwable;

/** Wraps one evaluator failure without exposing it as authorization evidence. */
#[Api(ApiStatus::Experimental)]
final class AuthorizationEvaluationException extends AuthorizationException
{
    private function __construct(Throwable $failure)
    {
        parent::__construct(AuthorizationDiagnostic::Evaluation, $failure);
    }

    public static function from(Throwable $failure): self
    {
        return new self($failure);
    }
}
