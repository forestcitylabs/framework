<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Routing;

use FastRoute\DataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use Psr\Cache\CacheItemPoolInterface;

class DataLoader
{
    public function __construct(
        private MetadataProvider $metadata_provider,
        private CacheItemPoolInterface $cache_pool,
        private DataGenerator $data_generator,
        private RouteParser $route_parser
    ) {
    }

    public function loadRoutes()
    {
        $cache = $this->cache_pool->getItem('core.routes.data');
        if (!$cache->isHit()) {
            // Create a route collector to collect routes.
            $route_collector = new RouteCollector($this->route_parser, $this->data_generator);

            // Iterate over routes, adding to collector.
            foreach ($this->metadata_provider->getRoutes() as $route) {
                $route_collector->addRoute(
                    $route->getMethods(),
                    ($route->getPrefix() ? $route->getPrefix()->getPath() : '') . $route->getPath(),
                    [$route->getClassName(), $route->getMethodName()]
                );
            }

            // Cache the route data.
            $this->cache_pool->save($cache->set($route_collector->getData()));

            // Return the route data.
            return $route_collector->getData();
        }

        // Return the route data from the cache.
        return $cache->get();
    }
}
