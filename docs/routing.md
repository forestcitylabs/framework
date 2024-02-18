# Routing

The routing component consists of [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware that uses the [nikic/fast-route](https://packagist.org/packages/nikic/fast-route) package to route requests at specific paths to controllers.

Route mappings are defined using [PHP Attributes](https://www.php.net/manual/en/language.attributes.overview.php) on their relevant controllers.

## Requirements

* [nikic/fast-route](https://packagist.org/packages/nikic/fast-route)
* [cocur/slugify](https://packagist.org/packages/cocur/slugify)
* A container library implementing [PSR-11](https://www.php-fig.org/psr/psr-11).
* An event dispatcher library implementing [PSR-14](https://www.php-fig.org/psr/psr-14).
* A response factory library implementing [PSR-17](https://www.php-fig.org/psr/psr-17).
* A cache library implementing [PSR-6](https://www.php-fig.org/psr/psr-6) (check the caching section for implementations that this framework provides).

* A logging library implementing [PSR-3](https://www.php-fig.org/psr/psr-3).
* A [PSR-15](https://www.php-fig.org/psr/psr-15/) compliant request handler (see the Kernel class for a request handler that ships with this framework).

## Components

### Middleware

* Middleware\RoutingMiddleware

### Events

* Events\PreRouteDispatchEvent

### Services

* Routing\MetadataProvider

### Attributes

* Routing\Attribute\Route
* Routing\Attribute\RoutePrefix

### Collections

* Routing\Collection\RouteCollection
