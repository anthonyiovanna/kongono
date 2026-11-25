<?php

declare(strict_types=1);

namespace Kongono\Cli\Policy;

/**
 * Fluent PHP 8.5 DSL for declaring Executable Architecture & Security Invariants (`app/invariants.php`).
 *
 * Verified mathematically against Kongono's live `ApplicationGraph`, reflected attributes/DTOs,
 * and tokenized `src/` namespace dependencies via `kongo policy:check` and MCP `kongono_policy_check`.
 */
final readonly class Invariant
{
    /**
     * @param 'route'|'namespace' $kind
     * @param list<string>        $exceptPatterns
     * @param list<string>        $methods
     * @param 'require_auth'|'require_auth_or_csrf'|'require_attribute'|'require_dto'|'require_middleware'|'require_webhook_verification'|'forbid_dependency' $assertion
     */
    private function __construct(
        public string $id,
        public string $kind,
        public string $targetPattern,
        public string $assertion,
        public ?string $assertionArg = null,
        public array $exceptPatterns = [],
        public array $methods = [],
        public string $description = '',
    ) {}

    public static function routesMatching(string $globPattern, string $id = ''): self
    {
        return new self(
            id: '' !== $id ? $id : 'KGN-POLICY-ROUTE',
            kind: 'route',
            targetPattern: $globPattern,
            assertion: 'require_auth',
        );
    }

    public static function mutatingRoutes(string $id = 'KGN-POLICY-MUTATION'): self
    {
        return new self(
            id: $id,
            kind: 'route',
            targetPattern: '*',
            assertion: 'require_auth_or_csrf',
            methods: ['POST', 'PUT', 'PATCH', 'DELETE'],
        );
    }

    public static function namespace(string $sourceNamespacePrefix, string $id = 'KGN-POLICY-LAYER'): self
    {
        return new self(
            id: $id,
            kind: 'namespace',
            targetPattern: trim($sourceNamespacePrefix, '\\'),
            assertion: 'forbid_dependency',
        );
    }

    public function withId(string $id): self
    {
        return new self($id, $this->kind, $this->targetPattern, $this->assertion, $this->assertionArg, $this->exceptPatterns, $this->methods, $this->description);
    }

    public function except(string ...$globs): self
    {
        return new self($this->id, $this->kind, $this->targetPattern, $this->assertion, $this->assertionArg, [...$this->exceptPatterns, ...array_values($globs)], $this->methods, $this->description);
    }

    public function methods(string ...$methods): self
    {
        $upper = array_values(array_map('strtoupper', $methods));

        return new self($this->id, $this->kind, $this->targetPattern, $this->assertion, $this->assertionArg, $this->exceptPatterns, $upper, $this->description);
    }

    public function describe(string $description): self
    {
        return new self($this->id, $this->kind, $this->targetPattern, $this->assertion, $this->assertionArg, $this->exceptPatterns, $this->methods, $description);
    }

    public function mustRequireAuth(): self
    {
        return new self($this->id, 'route', $this->targetPattern, 'require_auth', null, $this->exceptPatterns, $this->methods, $this->description);
    }

    public function mustRequireAuthOrCsrf(): self
    {
        return new self($this->id, 'route', $this->targetPattern, 'require_auth_or_csrf', null, $this->exceptPatterns, $this->methods, $this->description);
    }

    /** @param class-string $attributeClass */
    public function mustHaveAttribute(string $attributeClass): self
    {
        return new self($this->id, 'route', $this->targetPattern, 'require_attribute', $attributeClass, $this->exceptPatterns, $this->methods, $this->description);
    }

    public function mustBindInputDto(): self
    {
        return new self($this->id, 'route', $this->targetPattern, 'require_dto', null, $this->exceptPatterns, $this->methods, $this->description);
    }

    public function mustHaveRouteMiddleware(string $middlewareClassOrSuffix): self
    {
        return new self($this->id, 'route', $this->targetPattern, 'require_middleware', $middlewareClassOrSuffix, $this->exceptPatterns, $this->methods, $this->description);
    }

    public function mustVerifyWebhookSignature(): self
    {
        return new self($this->id, 'route', $this->targetPattern, 'require_webhook_verification', null, $this->exceptPatterns, $this->methods, $this->description);
    }

    public function mustNotDependOn(string $forbiddenNamespacePrefix): self
    {
        return new self(
            $this->id,
            'namespace',
            $this->targetPattern,
            'forbid_dependency',
            trim($forbiddenNamespacePrefix, '\\'),
            $this->exceptPatterns,
            [],
            $this->description,
        );
    }
}
