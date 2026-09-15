<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Diagnostic\ViteAdapterDiagnostic;
use Throwable;

/** Reports invalid or changed explicit Vite launch artifacts. */
#[Api(ApiStatus::Experimental)]final class InvalidViteToolchainException extends ViteAdapterException
{
    private const array ALLOWED = ['path_grammar','version_grammar','hash_grammar','launch_not_ordinary','launch_changed','profile_mismatch','toolchain_mismatch'];
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(ViteAdapterDiagnostic::InvalidToolchain, ['violation' => in_array($violation, self::ALLOWED, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
