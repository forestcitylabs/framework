<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Routing;

use Cocur\Slugify\Slugify;
use ForestCityLabs\Framework\Routing\Attribute\Route;
use ForestCityLabs\Framework\Routing\Attribute\RoutePrefix;
use ForestCityLabs\Framework\Routing\Collection\RouteCollection;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class MetadataProvider
{
    private RouteCollection $routes;

    public function __construct(
        private array $controllers,
        private CacheItemPoolInterface $cache_pool,
        private LoggerInterface $logger,
        private Slugify $slugify
    ) {
        $cache = $this->cache_pool->getItem('core.routes.metadata');
        if (!$cache->isHit()) {
            $this->routes = new RouteCollection();

            // Parse the controllers into metadata.
            $this->parseControllers($controllers);

            // Cache the metadata.
            $this->cache_pool->save($cache->set($this->routes));
        } else {
            $this->routes = $cache->get();
        }
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function getRoute(string $name): ?Route
    {
        return $this->routes[$name] ?? null;
    }

    private function parseControllers(array $controllers): void
    {
        // Iterate over controllers.
        foreach ($controllers as $controller) {
            $reflection = new ReflectionClass($controller);

            // Controller has route prefix attributes.
            if (count($reflection->getAttributes(RoutePrefix::class)) > 0) {
                foreach ($reflection->getAttributes(RoutePrefix::class) as $prefix) {
                    $prefix = $prefix->newInstance();
                    $this->parseMethodRoutes($reflection, $prefix);
                }
            } else {
                // Allow unprefixed routes.
                $this->parseMethodRoutes($reflection);
            }
        }
    }

    private function parseMethodRoutes(ReflectionClass $reflection, ?RoutePrefix $prefix = null): void
    {
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                // Create the route.
                $route = $attribute->newInstance();
                $route->setClassName($reflection->getName());
                $route->setMethodName($method->getName());
                $route->setPrefix($prefix);

                // Reasonable defaults.
                $route->setName($route->getName() ?? $this->generateRouteName($route));

                // Add the route.
                $this->routes->offsetSet($route->getName(), $route);
            }
        }
    }

    private function generateRouteName(Route $route): string
    {
        return $this->slugify->slugify(
            str_replace('Controller', '', substr(
                $route->getClassName(),
                strrpos($route->getClassName(), '\\', 1)
            )) . '_' . $route->getMethodName(),
            '_'
        );
    }
}
