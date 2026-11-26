<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Attribute;
use Kongono\Core\Container;
use Kongono\Core\Features\FeatureManager;
use Kongono\Http\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Declarative feature-flag gate attribute for controllers or route methods.
 * Returns 404 Not Found (fail-closed) when the feature is inactive.
 *
 * Example: `#[Feature('beta_api')]`
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
final readonly class Feature implements MiddlewareAttributeInterface
{
    public function __construct(
        public string $name,
        public int $inactiveStatus = 404,
        public bool $allowScopeHeader = false,
    ) {}

    public function toMiddleware(Container $container): MiddlewareInterface
    {
        $manager = $container->has(FeatureManager::class)
            ? $container->make(FeatureManager::class)
            : new FeatureManager();

        $featureName = $this->name;
        $inactiveStatus = $this->inactiveStatus;
        $allowScopeHeader = $this->allowScopeHeader;

        return new readonly class ($manager, $featureName, $inactiveStatus, $allowScopeHeader) implements MiddlewareInterface {
            public function __construct(
                private FeatureManager $features,
                private string $featureName,
                private int $inactiveStatus,
                private bool $allowScopeHeader,
            ) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                // Resolve scope strictly from server-side authenticated context (never trust client X-Feature-Scope by default)
                $scope = $this->resolveScope($request);
                if (!$this->features->active($this->featureName, $scope)) {
                    throw new HttpException($this->inactiveStatus, sprintf('Feature "%s" is not enabled.', $this->featureName));
                }

                return $handler->handle($request);
            }

            private function resolveScope(ServerRequestInterface $request): ?string
            {
                $explicit = $request->getAttribute('_feature_scope');
                if (is_string($explicit) && '' !== trim($explicit)) {
                    return trim($explicit);
                }

                $principal = $request->getAttribute('Kongono\\Security\\Principal');
                if (is_object($principal) && property_exists($principal, 'id') && is_string($principal->id) && '' !== $principal->id) {
                    return $principal->id;
                }

                if ($this->allowScopeHeader) {
                    $scopeHeader = trim($request->getHeaderLine('X-Feature-Scope'));

                    return '' !== $scopeHeader ? $scopeHeader : null;
                }

                return null;
            }
        };
    }
}
