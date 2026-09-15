<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Diagnostic\AssetAdapterContractDiagnostic;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;
use Throwable;

/** Reports invalid request, process, or translated-output evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidAssetAdapterContractException extends AssetAdapterContractException
{
    private const array REQUEST = ['adapter_profile_grammar', 'toolchain_identity_grammar', 'build_identity_grammar', 'output_identity_grammar', 'sha256_grammar', 'limit_range', 'contract_version', 'source_set_relation'];
    private const array PROCESS = ['arguments_not_list', 'argument_count', 'argument_grammar', 'working_directory_grammar', 'environment_shape', 'environment_grammar', 'result_relation', 'failure_reason'];
    private const array OUTPUT = ['producer_key_grammar', 'outputs_not_list', 'bindings_not_list', 'dependencies_not_list', 'invalid_output_entry', 'invalid_binding_entry', 'invalid_dependency_entry', 'duplicate_output', 'duplicate_binding', 'duplicate_dependency', 'missing_output', 'entry_binding_relation', 'self_dependency', 'unreachable_output', 'dependency_cycle', 'dependency_depth', 'output_metadata_relation', 'output_count'];

    public static function request(string $violation, ?Throwable $previous = null): self
    {
        return new self(AssetAdapterContractDiagnostic::InvalidRequest, ['violation' => AdapterContractGuard::token($violation, self::REQUEST)], $previous);
    }
    public static function process(string $violation, ?int $entryCount = null, ?int $limit = null, ?Throwable $previous = null): self
    {
        return new self(AssetAdapterContractDiagnostic::InvalidProcess, self::context($violation, self::PROCESS, $entryCount, $limit), $previous);
    }
    public static function output(string $violation, ?int $entryCount = null, ?int $limit = null, ?Throwable $previous = null): self
    {
        return new self(AssetAdapterContractDiagnostic::InvalidOutput, self::context($violation, self::OUTPUT, $entryCount, $limit), $previous);
    }
    /**
     * @param list<string> $allowed
     *
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, array $allowed, ?int $entryCount, ?int $limit): array
    {
        $context = ['violation' => AdapterContractGuard::token($violation, $allowed)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        return $context;
    }
}
