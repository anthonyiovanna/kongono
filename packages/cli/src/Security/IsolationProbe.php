<?php

declare(strict_types=1);

namespace Kongono\Cli\Security;

use Kongono\Security\Principal;

/**
 * Single adversarial cross-tenant BOLA / IDOR proof scenario for `TenantIsolationProver` (`app/isolation.php`).
 */
final readonly class IsolationProbe
{
    /**
     * @param list<string>          $canaries Secret strings belonging to Tenant A that MUST NEVER appear in Tenant B or unauthenticated responses
     * @param array<string, string> $headers
     * @param array<string, mixed>  $body
     */
    public function __construct(
        public string $name,
        public string $method,
        public string $path,
        public Principal $ownerPrincipal,
        public Principal $attackerPrincipal,
        public array $canaries = [],
        public array $headers = [],
        public array $body = [],
    ) {}
}
