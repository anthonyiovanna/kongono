<?php

declare(strict_types=1);

namespace Kongono\Ai\Agent;

use Attribute;

/**
 * Marks a tool handler or method as requiring explicit human-in-the-loop approval
 * before an AI agent may execute it.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION)]
final readonly class RequiresApproval
{
    public function __construct(
        public string $reason = 'Explicit human approval required before executing this action.',
    ) {}
}
