<?php

declare(strict_types=1);

namespace Kongono\Kernel\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/**
 * Owns the fixed metadata for deliberate Kernel lifecycle failures.
 *
 * The backed identifier is the stable semantic identity. Titles and remediation
 * remain human diagnostic prose and carry no transport or disclosure policy.
 */
#[Api(ApiStatus::Internal)]
enum LifecycleDiagnostic: string
{
    case BootWhenNotUnbooted = 'KGN-KERNEL-1001';
    case ShutdownWhenNotBooted = 'KGN-KERNEL-1002';
    case ShutdownWithActiveOperations = 'KGN-KERNEL-1003';
    case BeginOperationWhenNotBooted = 'KGN-KERNEL-1004';
    case EndOperationWhenNotBooted = 'KGN-KERNEL-1005';
    case InactiveOrForeignOperationScope = 'KGN-KERNEL-1006';

    public function contextSchema(): DiagnosticContextSchema
    {
        return match ($this) {
            self::BootWhenNotUnbooted => new DiagnosticContextSchema(
                'Boot Kernel',
                'Boot requires Unbooted',
                [
                    new DiagnosticFieldSchema(
                        'actual_state',
                        'Actual state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'expected_state',
                        'Expected state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::ShutdownWhenNotBooted => new DiagnosticContextSchema(
                'Shut down Kernel',
                'Shutdown requires Booted',
                [
                    new DiagnosticFieldSchema(
                        'actual_state',
                        'Actual state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'expected_state',
                        'Expected state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::ShutdownWithActiveOperations => new DiagnosticContextSchema(
                'Shut down Kernel',
                'Shutdown requires zero active operations',
                [
                    new DiagnosticFieldSchema(
                        'active_operation_count',
                        'Active operation count',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'expected_active_operation_count',
                        'Expected active operation count',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::BeginOperationWhenNotBooted => new DiagnosticContextSchema(
                'Begin operation',
                'Beginning work requires Booted',
                [
                    new DiagnosticFieldSchema(
                        'actual_state',
                        'Actual state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'expected_state',
                        'Expected state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::EndOperationWhenNotBooted => new DiagnosticContextSchema(
                'End operation',
                'Ending a scope requires Booted',
                [
                    new DiagnosticFieldSchema(
                        'actual_state',
                        'Actual state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'expected_state',
                        'Expected state',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::InactiveOrForeignOperationScope => new DiagnosticContextSchema(
                'End operation',
                'Scope must be active and owned by this Kernel',
                [
                    new DiagnosticFieldSchema(
                        'scope_condition',
                        'Scope condition',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
        };
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::BootWhenNotUnbooted => 'Boot requested when the Kernel is not `Unbooted`',
            self::ShutdownWhenNotBooted => 'Shutdown requested when the Kernel is not `Booted`',
            self::ShutdownWithActiveOperations => 'Shutdown requested while operations remain active',
            self::BeginOperationWhenNotBooted => 'Operation begin requested when the Kernel is not `Booted`',
            self::EndOperationWhenNotBooted => 'Operation end requested when the Kernel is not `Booted`',
            self::InactiveOrForeignOperationScope => 'Operation scope is inactive, already ended, or owned by another Kernel',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::BootWhenNotUnbooted => 'Boot each Kernel instance exactly once from `Unbooted`',
            self::ShutdownWhenNotBooted => 'Shut down only a booted, non-shutdown Kernel',
            self::ShutdownWithActiveOperations => 'End every operation owned by that Kernel before shutdown',
            self::BeginOperationWhenNotBooted => 'Boot first and do not begin work after shutdown',
            self::EndOperationWhenNotBooted => 'End active scopes while their owning Kernel is booted',
            self::InactiveOrForeignOperationScope => 'Pass the exact active scope to the Kernel instance that created it',
        };
    }
}
