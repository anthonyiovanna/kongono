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
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Feature implements MiddlewareAttributeInterface
{
    public function __construct(
        public string $name,
        public int $inactiveStatus = 404,
    ) {}

    public function toMiddleware(Container $container): MiddlewareInterface
    {
        $manager = $container->has(FeatureManager::class)
            ? $container->make(FeatureManager::class)
            : new FeatureManager();

        $featureName = $this->name;
        $inactiveStatus = $this->inactiveStatus;

        return new readonly class ($manager, $featureName, $inactiveStatus) implements MiddlewareInterface {
            public function __construct(
                private FeatureManager $features,
                private string $featureName,
                private int $inactiveStatus,
            ) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $scopeHeader = trim($request->getHeaderLine('X-Feature-Scope'));
                $scope = '' !== $scopeHeader ? $scopeHeader : null;
                if (!$this->features->active($this->featureName, $scope)) {
                    throw new HttpException($this->inactiveStatus, sprintf('Feature "%s" is not enabled.', $this->featureName));
                }

                return $handler->handle($request);
            }
        };
    }
}
