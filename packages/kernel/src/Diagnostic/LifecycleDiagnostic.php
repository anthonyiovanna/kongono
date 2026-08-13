<?php

declare(strict_types=1);

namespace Kongono\Kernel\Diagnostic;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

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
